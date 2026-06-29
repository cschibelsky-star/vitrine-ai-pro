#!/usr/bin/env bash
set -e

PATCH_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(pwd)"
BACKUP_DIR="$PROJECT_DIR/storage/app/backups/patch_2_0_1_$(date +%Y%m%d_%H%M%S)"

echo "== Vitrine AI Pro Master Enterprise 2.0.1 =="
echo "Projeto: $PROJECT_DIR"
echo "Patch: $PATCH_DIR"
echo "Backup: $BACKUP_DIR"

mkdir -p "$BACKUP_DIR"

cd "$PATCH_DIR/files"
find . -type f | while read -r file; do
  target="$PROJECT_DIR/${file#./}"
  backup_target="$BACKUP_DIR/${file#./}"
  mkdir -p "$(dirname "$target")" "$(dirname "$backup_target")"
  if [ -f "$target" ]; then
    cp "$target" "$backup_target"
  fi
  cp "$file" "$target"
done

cd "$PROJECT_DIR"
php artisan optimize:clear || true

echo "Patch 2.0.1 aplicado. Revise o menu lateral em /admin."
