#!/usr/bin/env bash
set -e

# Execute dentro da raiz do projeto Laravel:
# cd /home1/cris1649/vitrine-ai-pro

cp -R app/Filament/Pages/* ./app/Filament/Pages/
mkdir -p ./resources/views/filament/pages
cp -R resources/views/filament/pages/* ./resources/views/filament/pages/
mkdir -p ./resources/css/filament/admin
cp -R resources/css/filament/admin/* ./resources/css/filament/admin/
cp -R config/* ./config/

php artisan optimize:clear
php artisan filament:upgrade || true
php artisan view:clear
php artisan route:clear
php artisan config:clear

echo "Vitrine AI Pro Master Enterprise 2.0 instalado."
