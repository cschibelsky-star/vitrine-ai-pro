#!/usr/bin/env python3
"""Create and verify a self-contained SQLite+storage backup without touching active runtime.

Usage: python3 scripts/assessor/sqlite-storage-backup.py backup SOURCE_DB SOURCE_STORAGE BACKUP_DIR
       python3 scripts/assessor/sqlite-storage-backup.py verify BACKUP_DIR

Backup uses SQLite online backup API for consistency and never copies an open database file.
The source storage tree is read-only; destination must not already exist.
"""
import argparse
import hashlib
import json
import os
import pathlib
import sqlite3
import tarfile
import tempfile


def digest(path):
    h = hashlib.sha256()
    with path.open("rb") as stream:
        for block in iter(lambda: stream.read(1024 * 1024), b""):
            h.update(block)
    return h.hexdigest()


def validate_archive(path):
    with tarfile.open(path, "r:gz") as archive:
        for member in archive:
            parts = pathlib.PurePosixPath(member.name)
            if parts.is_absolute() or ".." in parts.parts or not (member.isfile() or member.isdir()):
                raise ValueError("Unsafe archive entry")
            if not member.name.startswith("storage/") and member.name != "storage":
                raise ValueError("Unexpected archive entry")


def verify(destination):
    manifest = json.loads((destination / "manifest.json").read_text(encoding="utf-8"))
    if manifest.get("version") != 1:
        raise ValueError("Unsupported manifest")
    for name in ("database.sqlite", "storage.tar.gz"):
        if digest(destination / name) != manifest["sha256"][name]:
            raise ValueError("Checksum mismatch: " + name)
    validate_archive(destination / "storage.tar.gz")
    with sqlite3.connect("file:" + str(destination / "database.sqlite") + "?mode=ro", uri=True) as db:
        integrity = db.execute("PRAGMA integrity_check").fetchone()[0]
        tables = db.execute("SELECT count(*) FROM sqlite_master WHERE type='table'").fetchone()[0]
    if integrity != "ok":
        raise ValueError("SQLite integrity check failed")
    print("BACKUP_VERIFY=PASS")
    print("SQLITE_TABLE_COUNT=" + str(tables))
    return tables


def backup(db_path, storage_path, destination):
    if not db_path.is_file() or not storage_path.is_dir():
        raise ValueError("Database or storage source missing")
    if destination.exists():
        raise ValueError("Backup destination already exists")
    destination.parent.mkdir(parents=True, exist_ok=True)
    with tempfile.TemporaryDirectory(prefix=".assessor-backup-", dir=destination.parent) as temp:
        work = pathlib.Path(temp)
        with sqlite3.connect("file:" + str(db_path) + "?mode=ro", uri=True) as source:
            with sqlite3.connect(work / "database.sqlite") as snapshot:
                source.backup(snapshot)
        with tarfile.open(work / "storage.tar.gz", "w:gz", dereference=False) as archive:
            for item in sorted(storage_path.rglob("*")):
                if item.is_symlink():
                    raise ValueError("Storage symlink not supported: " + str(item))
                if not (item.is_file() or item.is_dir()):
                    raise ValueError("Unsupported storage entry")
                archive.add(item, arcname=str(pathlib.PurePosixPath("storage") / item.relative_to(storage_path)), recursive=False)
        hashes = {name: digest(work / name) for name in ("database.sqlite", "storage.tar.gz")}
        (work / "manifest.json").write_text(json.dumps({"version": 1, "sha256": hashes}, sort_keys=True), encoding="utf-8")
        verify(work)
        os.rename(work, destination)
    print("BACKUP_CREATE=PASS")


def main():
    parser = argparse.ArgumentParser()
    sub = parser.add_subparsers(dest="operation", required=True)
    b = sub.add_parser("backup")
    b.add_argument("database", type=pathlib.Path)
    b.add_argument("storage", type=pathlib.Path)
    b.add_argument("destination", type=pathlib.Path)
    v = sub.add_parser("verify")
    v.add_argument("destination", type=pathlib.Path)
    args = parser.parse_args()
    if args.operation == "backup":
        backup(args.database, args.storage, args.destination)
    else:
        verify(args.destination)


if __name__ == "__main__":
    main()
