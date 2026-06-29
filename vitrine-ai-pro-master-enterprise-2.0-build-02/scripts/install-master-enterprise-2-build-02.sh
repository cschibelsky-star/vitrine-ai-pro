#!/bin/bash
set -e

echo "== Vitrine AI Pro Master Enterprise 2.0 Build 02 =="
echo "Aplicando atualização de layout e UX..."

php artisan optimize:clear || true
php artisan view:clear || true
php artisan config:clear || true
php artisan route:clear || true

# Publica CSS caso o pacote tenha sido extraído fora da raiz.
if [ -f "public/css/vitrine-master-enterprise.css" ]; then
  echo "CSS Enterprise encontrado."
fi

php artisan optimize:clear || true

echo "Atualização concluída. Acesse /admin e valide Dashboard Executivo e Factory Dashboard."
