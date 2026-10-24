#!/usr/bin/env bash
# G0 isolated backup verification: read-only against saved artifacts and runtime.
set -euo pipefail
BACKUP="${1:?Usage: bash scripts/assessor/verify-g0-backup.sh /absolute/backup/path}"
[[ -d "$BACKUP" ]] || { echo "FAIL: backup directory missing"; exit 1; }
for f in database.sqlite storage.tar.gz SHA256SUMS; do
  [[ -f "$BACKUP/$f" ]] || { echo "FAIL: missing $f"; exit 1; }
done
( cd "$BACKUP" && sha256sum -c SHA256SUMS )
tmp="$(mktemp -d /tmp/assessor-g0-verify-XXXXXX)"
trap 'rm -rf "$tmp"' EXIT
cp "$BACKUP/database.sqlite" "$tmp/database.sqlite"
mkdir "$tmp/storage"
tar -xzf "$BACKUP/storage.tar.gz" -C "$tmp/storage"
tar -tzf "$BACKUP/storage.tar.gz" >/dev/null
docker run --rm --network none -v "$tmp:/restore:ro" python:3.12-alpine python -c '
import sqlite3
p="file:/restore/database.sqlite?mode=ro"
db=sqlite3.connect(p,uri=True)
integrity=db.execute("PRAGMA integrity_check").fetchone()[0]
tables=[r[0] for r in db.execute("SELECT name FROM sqlite_master WHERE type=\x27table\x27 ORDER BY name")]
print("SQLITE_INTEGRITY="+integrity)
print("SQLITE_TABLE_COUNT="+str(len(tables)))
print("SQLITE_TABLES="+",".join(tables))
db.close()
if integrity!="ok": raise SystemExit(2)
'
echo "G0_BACKUP_FILES=OK"
echo "G0_APPLICATION_RESTORE=NOT_TESTED"
echo "G0_GATE=PARTIAL"
