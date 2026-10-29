"""Fail-closed acceptance harness for a disposable, offline Linux container.

This image contains the synthetic operator fixture solely for tests. It is not
an approval service or proof of remote Cockpit/agent separation.
"""
import os
from pathlib import Path
import sys
import offline_hml

CREDENTIALS = ('INVISION_API_KEY', 'ROTEIA_API_KEY', 'OPENROUTER_API_KEY',
               'LAB_APPROVAL_SECRET', 'LAB_ACCESS_TOKEN')


def validate_container():
    if os.geteuid() == 0:
        raise RuntimeError('container_nonroot_required')
    status = dict(line.split(':', 1) for line in Path('/proc/self/status').read_text().splitlines() if ':' in line)
    if int(status['CapEff'].strip(), 16) != 0:
        raise RuntimeError('container_capabilities_must_be_empty')
    if status['NoNewPrivs'].strip() != '1':
        raise RuntimeError('container_no_new_privileges_required')
    mounts = [line.split() for line in Path('/proc/mounts').read_text().splitlines()]
    root = next((entry for entry in mounts if entry[1] == '/'), None)
    if root is None or 'ro' not in root[3].split(','):
        raise RuntimeError('container_readonly_root_required')
    if set(os.listdir('/sys/class/net')) != {'lo'}:
        raise RuntimeError('container_loopback_only_required')
    if any(os.environ.get(name) for name in CREDENTIALS):
        raise RuntimeError('container_real_credentials_forbidden')


def main():
    validate_container()
    print('Container guards passed: nonroot, readonly root, no capabilities, no-new-privileges, loopback-only, no credentials.', flush=True)
    return offline_hml.main()


if __name__ == '__main__':
    sys.exit(main())
