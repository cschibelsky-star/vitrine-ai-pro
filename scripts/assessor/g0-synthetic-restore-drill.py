#!/usr/bin/env python3
"""G0 synthetic SQLite backup/restore drill. Only uses an ephemeral temp directory."""
import hashlib
import pathlib
import shutil
import sqlite3
import tarfile
import tempfile

with tempfile.TemporaryDirectory(prefix="assessor-g0-drill-") as root:
    base = pathlib.Path(root)
    source = base / "source"
    backup = base / "backup"
    restored = base / "restored"
    for folder in (source, backup, restored):
        folder.mkdir()
    database = source / "database.sqlite"
    with sqlite3.connect(database) as connection:
        connection.execute("CREATE TABLE gabinete_protocolos (id INTEGER PRIMARY KEY, codigo TEXT UNIQUE NOT NULL, estado TEXT NOT NULL)")
        connection.executemany("INSERT INTO gabinete_protocolos (codigo, estado) VALUES (?, ?)", [
            ("HML-0001", "aberto"), ("HML-0002", "concluido")
        ])
        connection.commit()
        with sqlite3.connect(backup / "database.sqlite") as snapshot:
            connection.backup(snapshot)
    storage = source / "storage"
    storage.mkdir()
    (storage / "evidencia.txt").write_text("G0 synthetic restore test only\n", encoding="utf-8")
    with tarfile.open(backup / "storage.tar.gz", "w:gz") as archive:
        archive.add(storage, arcname="storage")
    hashes = {}
    for name in ("database.sqlite", "storage.tar.gz"):
        hashes[name] = hashlib.sha256((backup / name).read_bytes()).hexdigest()
    for name, expected in hashes.items():
        assert hashlib.sha256((backup / name).read_bytes()).hexdigest() == expected
    shutil.copy2(backup / "database.sqlite", restored / "database.sqlite")
    with tarfile.open(backup / "storage.tar.gz", "r:gz") as archive:
        members = archive.getmembers()
        for member in members:
            path = pathlib.PurePosixPath(member.name)
            assert not path.is_absolute() and ".." not in path.parts
            assert member.isfile() or member.isdir()
        archive.extractall(restored, filter="data")
    with sqlite3.connect(f"file:{restored / 'database.sqlite'}?mode=ro", uri=True) as connection:
        integrity = connection.execute("PRAGMA integrity_check").fetchone()[0]
        records = connection.execute("SELECT codigo, estado FROM gabinete_protocolos ORDER BY id").fetchall()
    assert integrity == "ok", integrity
    assert records == [("HML-0001", "aberto"), ("HML-0002", "concluido")], records
    assert (restored / "storage" / "evidencia.txt").read_text(encoding="utf-8") == "G0 synthetic restore test only\n"
    print("G0_SYNTHETIC_BACKUP_RESTORE=PASS")
    print("G0_SYNTHETIC_RECORDS=2")
    print("G0_REAL_APPLICATION_RESTORE=NOT_TESTED")
    print("G0_GATE=PARTIAL")
