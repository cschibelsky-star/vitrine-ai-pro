#!/bin/bash
set -e
ROOT="$(pwd)"
echo "Aplicando Patch 2.0.2 Enterprise UI em: $ROOT"
mkdir -p storage/backups/patch_2_0_2_enterprise_ui
[ -f app/Filament/Pages/Dashboard.php ] && cp app/Filament/Pages/Dashboard.php storage/backups/patch_2_0_2_enterprise_ui/Dashboard.php.bak || true
cp -R files/* ./
php artisan optimize:clear || true
php artisan filament:clear-cached-components || true
php artisan view:clear || true
php artisan cache:clear || true
php artisan config:clear || true
composer dump-autoload || true
echo "Patch 2.0.2 Enterprise UI aplicado. Recarregue o navegador com Ctrl+F5."
