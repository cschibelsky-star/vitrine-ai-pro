FROM php:8.3-apache
RUN a2enmod rewrite headers expires
COPY . /var/www/html/
WORKDIR /var/www/html
RUN set -eu; \
    grep -q "4.3.0-ECONOMIC-HML" index.php; \
    grep -q "Sumaré produz, conecta e cresce." index.php; \
    grep -q 'href="/empresas"' index.php; \
    grep -q "Shopping ParkCity Sumaré" index.php; \
    grep -q "f-business-more" index.php; \
    grep -q "e-main" assets/style.css; \
    test "$(wc -c < assets/style.css)" -gt 50000; \
    grep -q "empresas" .htaccess; \
    grep -q "cadastro-empresa" .htaccess; \
    grep -q "event_action" admin/index.php; \
    php -l index.php >/dev/null; \
    php -l admin/index.php >/dev/null; \
    php -l admin/login.php >/dev/null; \
    php -l app/bootstrap.php >/dev/null; \
    php -r '$d=json_decode(file_get_contents("storage/data/events.json"),true,512,JSON_THROW_ON_ERROR); if(!is_array($d)){exit(1);}'; \
    echo "CONHECA_SUMARE_REGRESSION_GUARD: OK"
RUN chown -R www-data:www-data /var/www/html
EXPOSE 80
