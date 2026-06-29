#!/bin/bash
set -e
cd "$(dirname "$0")"
php artisan optimize:clear || true
php artisan filament:clear-cached-components || true
php artisan view:clear || true
php artisan cache:clear || true
php artisan config:clear || true
composer dump-autoload || true
echo "PATCH 2.0.1 REAL aplicado: limpezas executadas."
