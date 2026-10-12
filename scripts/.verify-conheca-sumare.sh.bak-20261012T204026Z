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

php -l index.php >/dev/null
php -l admin/index.php >/dev/null
php -l admin/login.php >/dev/null
php -l app/bootstrap.php >/dev/null
php -r '$d=json_decode(file_get_contents("storage/data/events.json"),true,512,JSON_THROW_ON_ERROR); if(!is_array($d)){exit(1);}'

echo "CONHECA_SUMARE_REGRESSION_GUARD: OK"
