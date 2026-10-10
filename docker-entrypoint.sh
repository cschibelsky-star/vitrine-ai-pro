#!/bin/sh
set -eu

DATA_DIR="/var/www/html/storage/data"
DEFAULT_DIR="/opt/conheca-defaults/data"

mkdir -p "$DATA_DIR"

for f in items.json events.json settings.json; do
  if [ ! -s "$DATA_DIR/$f" ] && [ -f "$DEFAULT_DIR/$f" ]; then
    cp "$DEFAULT_DIR/$f" "$DATA_DIR/$f"
  fi
done

chown -R www-data:www-data "$DATA_DIR" 2>/dev/null || true
exec docker-php-entrypoint "$@"
