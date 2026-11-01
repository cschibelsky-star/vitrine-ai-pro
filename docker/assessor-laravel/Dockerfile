FROM php:8.3-cli
RUN apt-get update && apt-get install -y --no-install-recommends git unzip libonig-dev libzip-dev libxml2-dev libicu-dev && docker-php-ext-install pdo_sqlite mbstring zip intl && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN mkdir -p bootstrap/cache storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs && composer install --no-dev --no-interaction --prefer-dist --no-progress --no-scripts --no-autoloader
COPY . .
RUN composer dump-autoload --no-dev --optimize --no-scripts && chown -R www-data:www-data storage bootstrap/cache
USER www-data
EXPOSE 8080
CMD ["sh","-lc","mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs && touch storage/assessor.sqlite && php artisan migrate --path=database/migrations/2024_01_01_000001_create_users_table.php --force && php artisan migrate --path=database/migrations/2026_10_20_000001_create_assessor_core_tables.php --force && php artisan migrate --path=database/migrations/2026_10_22_000002_create_assessor_memberships_table.php --force && php artisan serve --host=0.0.0.0 --port=8080"]
