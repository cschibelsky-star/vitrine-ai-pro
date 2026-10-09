#!/usr/bin/env python3
"""Read-only operational collector. Run under a dedicated operator account.
Config and signing key live outside the application checkout. Never accepts flags
such as dns_ok, tests_passed or isolated from a request or a release manifest.
"""
import argparse
import base64
import hashlib
import json
import os
from pathlib import Path
import stat
import subprocess
import tarfile
import time
from cryptography.hazmat.primitives.asymmetric.ed25519 import Ed25519PrivateKey


def protected(path):
    path = Path(path)
    info = path.stat()
    if info.st_mode & (stat.S_IWGRP | stat.S_IWOTH):
        raise RuntimeError('operator_file_writable_by_others')
    return path


def command(*args):
    return subprocess.check_output(args, text=True, timeout=30).strip()


def digest(path):
    result = hashlib.sha256()
    with Path(path).open('rb') as stream:
        for chunk in iter(lambda: stream.read(1024 * 1024), b''):
            result.update(chunk)
    return result.hexdigest()


def inspect_runtime(spec, checkout=False):
    # Narrow Docker projection: never request Config.Env or secret contents.
    raw = command('docker', 'inspect', '--format',
        '{{json .Id}}|{{json .State.Running}}|{{json .Mounts}}|{{json .Config.Labels}}|{{json .NetworkSettings.Networks}}', spec['container'])
    container, running, mounts, labels, networks = map(json.loads, raw.split('|', 4))
    labels = labels or {}
    def sources(kind):
        targets = spec[kind + '_targets']
        found = [mount['Source'] for mount in mounts if mount['Destination'] in targets]
        if not targets or len(found) != len(targets):
            raise RuntimeError('isolation_sources_unproven')
        return sorted(found)
    result = {'container_id': container, 'running': running,
        'data_sources': sources('data'), 'secret_sources': sources('secret'),
        'image_revision': labels.get('org.opencontainers.image.revision', ''), 'networks': sorted(networks or {})}
    if checkout:
        root = Path(spec['checkout']).resolve()
        result.update(checkout_sha=command('git', '-C', str(root), 'rev-parse', 'HEAD'),
            checkout_clean=command('git', '-C', str(root), 'status', '--porcelain', '--untracked-files=all') == '',
            repository=command('git', '-C', str(root), 'remote', 'get-url', 'origin')
                .removeprefix('https://github.com/').removesuffix('.git'))
        # Bind-mounted code must be the measured checkout, otherwise label+Git
        # alone could attest a different tree from what the container serves.
        code = [m['Source'] for m in mounts if m['Destination'] == spec['code_target']]
        if len(code) != 1 or Path(code[0]).resolve() != root:
            raise RuntimeError('served_checkout_unproven')
    return result


def collect(config):
    hml = inspect_runtime(config['hml'], checkout=True)
    prod = inspect_runtime(config['production'])
    backup = protected(config['backup_archive'])
    # Read every member, detecting truncation/corruption, without extraction.
    with tarfile.open(backup) as archive:
        count = 0
        for member in archive:
            if member.isfile():
                with archive.extractfile(member) as content:
                    for chunk in iter(lambda: content.read(1024 * 1024), b''):
                        pass
                count += 1
        if count == 0:
            raise RuntimeError('empty_backup')
    if backup.stat().st_mtime < time.time() - 86400:
        raise RuntimeError('backup_too_old')
    backup_hash = digest(backup)
    rollback = protected(config['rollback_script'])
    if not os.access(rollback, os.X_OK):
        raise RuntimeError('rollback_script_not_executable')
    return {key: config[key] for key in ['project_id', 'repository', 'hml_url', 'production_url', 'executor']} | {
        'observed_at': int(time.time()), 'hml': hml, 'production': prod,
        'backup': {'archive_valid': True, 'sha256': backup_hash, 'observed_at': int(time.time())},
        'rollback': {'script_sha256': digest(rollback), 'backup_sha256': backup_hash}}


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('--config', required=True)
    parser.add_argument('--key', required=True)
    parser.add_argument('--output', required=True)
    args = parser.parse_args()
    config = json.loads(protected(args.config).read_text())
    payload = json.dumps(collect(config), sort_keys=True, separators=(',', ':')).encode()
    key = Ed25519PrivateKey.from_private_bytes(base64.b64decode(protected(args.key).read_text().strip(), validate=True))
    envelope = {'payload': base64.b64encode(payload).decode(), 'signature': base64.b64encode(key.sign(payload)).decode()}
    output = Path(args.output)
    temporary = output.with_suffix('.tmp')
    with temporary.open('w') as stream:
        json.dump(envelope, stream)
    temporary.chmod(0o640)
    temporary.replace(output)


if __name__ == '__main__':
    main()
