#!/usr/bin/env python3
"""Offline integration test for SQLite online backup and storage bundle."""
import importlib.util
import pathlib
import sqlite3
import tarfile
import tempfile

script = pathlib.Path(__file__).with_name("sqlite-storage-backup.py")
spec = importlib.util.spec_from_file_location("assessor_backup", script)
module = importlib.util.module_from_spec(spec)
spec.loader.exec_module(module)

with tempfile.TemporaryDirectory(prefix="assessor-backup-test-") as root:
    base = pathlib.Path(root)
    source = base / "source.sqlite"
    storage = base / "storage"
    storage.mkdir()
    (storage / "evidence.txt").write_text("isolated test\n", encoding="utf-8")
    with sqlite3.connect(source) as db:
        db.execute("CREATE TABLE assessor_trial_requests (id TEXT PRIMARY KEY, status TEXT)")
        db.execute("INSERT INTO assessor_trial_requests VALUES ('synthetic-id', 'requested')")
        db.commit()
    target = base / "archive"
    module.backup(source, storage, target)
    assert module.verify(target) == 1
    restored = base / "restored"
    restored.mkdir()
    with tarfile.open(target / "storage.tar.gz", "r:gz") as archive:
        module.validate_archive(target / "storage.tar.gz")
        archive.extractall(restored, filter="data")
    with sqlite3.connect(target / "database.sqlite") as db:
        assert db.execute("SELECT status FROM assessor_trial_requests").fetchone()[0] == "requested"
    assert (restored / "storage" / "evidence.txt").read_text(encoding="utf-8") == "isolated test\n"
    (target / "storage.tar.gz").write_bytes(b"tampered")
    try:
        module.verify(target)
    except ValueError as exc:
        assert "Checksum mismatch" in str(exc)
    else:
        raise AssertionError("Corrupted archive not detected")
    print("ASSESSOR_SQLITE_STORAGE_BACKUP_TEST=PASS")
