#!/usr/bin/env bash
# Isolated HML backup: SQLite online snapshot + Laravel storage archive.
# Does not stop services, change volumes, or restore into the running HML.
set -Eeuo pipefail
umask 077

PROJECT="assessor-online-trial-hml"
PRIVATE_VOLUME="${PROJECT}_assessor_private"
STORAGE_VOLUME="${PROJECT}_assessor_storage"
BACKUP_ROOT="${BACKUP_ROOT:-/srv/vitrine/backups/assessor-online-trial-hml}"
STAMP="$(date -u +%Y%m%dT%H%M%SZ)"
DEST="${BACKUP_ROOT}/${STAMP}"
PYTHON_IMAGE="${ASSESSOR_BACKUP_PYTHON_IMAGE:-python:3.12-alpine}"
BUSYBOX_IMAGE="${ASSESSOR_BACKUP_BUSYBOX_IMAGE:-busybox:1.36}"

command -v docker >/dev/null
for volume in "$PRIVATE_VOLUME" "$STORAGE_VOLUME"; do
  if ! docker volume ls --format '{{.Name}}' | grep -Fxq "$volume"; then
    echo "Missing Docker volume: $volume" >&2
    exit 1
  fi
done
if [ -e "$DEST" ]; then echo "Backup destination exists: $DEST" >&2; exit 1; fi
mkdir -p "$DEST"
trap 'echo "Backup interrupted; inspect incomplete destination: $DEST" >&2' ERR

# Python sqlite3.backup() gives a consistent online copy, even if the source uses WAL.
docker run --rm --network none -i \
  -v "$PRIVATE_VOLUME:/source:ro" \
  -v "$DEST:/backup" \
  "$PYTHON_IMAGE" python - <<'PY'
import os, sqlite3
src = "/source/database.sqlite"
dst = "/backup/database.sqlite"
if not os.path.isfile(src):
    raise SystemExit("SQLite source missing: " + src)
source = sqlite3.connect("file:" + src + "?mode=ro", uri=True)
target = sqlite3.connect(dst)
try:
    source.backup(target)
    if target.execute("PRAGMA integrity_check").fetchone()[0] != "ok":
        raise SystemExit("SQLite integrity_check failed")
finally:
    target.close()
    source.close()
PY

# Archive storage without modifying the mounted source volume.
docker run --rm --network none \
  -v "$STORAGE_VOLUME:/source:ro" \
  -v "$DEST:/backup" \
  "$BUSYBOX_IMAGE" sh -ec 'cd /source && tar -czf /backup/storage.tar.gz .'

# Validate the backup artifacts and archive readability, not the running application.
docker run --rm --network none -v "$DEST:/backup:ro" \
  "$PYTHON_IMAGE" python - <<'PY'
import os, sqlite3, tarfile
db = "/backup/database.sqlite"
archive = "/backup/storage.tar.gz"
assert os.path.getsize(db) > 0, "Empty SQLite backup"
assert os.path.getsize(archive) > 0, "Empty storage archive"
conn = sqlite3.connect("file:" + db + "?mode=ro", uri=True)
try:
    assert conn.execute("PRAGMA integrity_check").fetchone()[0] == "ok"
finally:
    conn.close()
with tarfile.open(archive, "r:gz") as tar:
    for member in tar:
        if member.name.startswith("/") or ".." in member.name.split("/"):
            raise RuntimeError("Unsafe archive member")
print("Backup verified: SQLite integrity and storage archive readable")
PY

(cd "$DEST" && sha256sum database.sqlite storage.tar.gz > SHA256SUMS)
echo "OK: $DEST"
echo "Note: application-level restore drill must be executed separately in isolated HML."
