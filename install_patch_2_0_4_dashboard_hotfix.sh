#!/usr/bin/env bash
set -e
ROOT="$(pwd)"
STAMP="$(date +%Y%m%d_%H%M%S)"
BACKUP_DIR="$ROOT/backup_patch_2_0_4_dashboard_hotfix_$STAMP"
mkdir -p "$BACKUP_DIR/app/Filament/Pages"

if [ -f "$ROOT/app/Filament/Pages/Dashboard.php" ]; then
  cp "$ROOT/app/Filament/Pages/Dashboard.php" "$BACKUP_DIR/app/Filament/Pages/Dashboard.php"
fi

cp -f "$ROOT/app/Filament/Pages/Dashboard.php.patch_2_0_4" "$ROOT/app/Filament/Pages/Dashboard.php"

php artisan optimize:clear || true
php artisan view:clear || true
php artisan cache:clear || true
php artisan config:clear || true
php artisan filament:clear-cached-components || true
composer dump-autoload || true

echo "Patch 2.0.4 aplicado. Backup em: $BACKUP_DIR"
