#!/bin/bash
set -e
cd "$(dirname "$0")"
ROOT="$(pwd)"
STAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="$ROOT/backups/patch_2_0_3_$STAMP"
mkdir -p "$BACKUP_DIR"
echo "Aplicando Patch 2.0.3 Enterprise Real UI..."
for file in \
  app/Filament/Pages/Dashboard.php \
  app/Factory/Filament/Pages/FactoryDashboard.php \
  resources/views/filament/pages/dashboard-enterprise-real.blade.php \
  resources/views/factory/filament/pages/factory-dashboard.blade.php \
  resources/views/filament/pages/marketplace-enterprise.blade.php \
  public/css/vitrine-enterprise-real.css
 do
  if [ -f "$ROOT/$file" ]; then
    mkdir -p "$BACKUP_DIR/$(dirname "$file")"
    cp "$ROOT/$file" "$BACKUP_DIR/$file"
  fi
 done
cp -R files/* "$ROOT/"
php artisan optimize:clear || true
php artisan filament:clear-cached-components || true
php artisan view:clear || true
php artisan cache:clear || true
php artisan config:clear || true
composer dump-autoload || true
echo "Patch 2.0.3 aplicado. Abra /admin/dashboard e use Ctrl+F5. Backup em: $BACKUP_DIR"
