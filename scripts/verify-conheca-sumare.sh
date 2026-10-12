#!/bin/sh
set -eu

fail() {
  echo "CONHECA_SUMARE_REGRESSION_GUARD: $1" >&2
  exit 1
}

grep -q "4.3.0-ECONOMIC-HML" index.php || fail "canonical release marker missing"
grep -q "Sumaré produz, conecta e cresce." index.php || fail "economic hub hero missing"
grep -q 'href="/empresas"' index.php || fail "business route missing"
grep -q "Shopping ParkCity Sumaré" index.php || fail "approved business content missing"
grep -q "f-business-more" index.php || fail "approved business CTA missing"
grep -q "e-main" assets/style.css || fail "economic styles missing"
[ "$(wc -c < assets/style.css)" -gt 50000 ] || fail "stylesheet is unexpectedly small"
grep -q "empresas" .htaccess || fail "business rewrite missing"
grep -q "cadastro-empresa" .htaccess || fail "legacy business redirect missing"
grep -q "event_action" admin/index.php || fail "event review controls missing"
! grep -q "conhecasumare.com.br" docker-compose.hml.yml || fail "production hostname leaked into HML compose"
grep -q "EVENTS_DATA_DIR" docker-compose.hml.yml || fail "isolated events directory missing from HML compose"
grep -q "EVENTS_DATA_DIR" app/bootstrap.php || fail "events storage contract missing"
grep -q "events_write" admin/index.php || fail "admin is not writing through isolated events storage"
grep -q "icon-192.png" manifest.json || fail "PWA 192 icon missing"
grep -q "icon-512.png" manifest.json || fail "PWA 512 icon missing"
grep -q "Você está offline" service-worker.js || fail "offline fallback missing"
grep -q "logo-vitrine-ai-pro.png" index.php || fail "official Vitrine IA Pro logo missing"
grep -q "/?img=shopping-parkcity-sumare" assets/style.css || fail "ParkCity controlled image route missing"
grep -q "/?img=coca-cola-femsa" assets/style.css || fail "FEMSA controlled image route missing"

php -l index.php >/dev/null
php -l admin/index.php >/dev/null
php -l admin/login.php >/dev/null
php -l app/bootstrap.php >/dev/null
php -r '$d=json_decode(file_get_contents("storage/data/events.json"),true,512,JSON_THROW_ON_ERROR); if(!is_array($d)){exit(1);}'

echo "CONHECA_SUMARE_REGRESSION_GUARD: OK"
