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
CMD ["php","artisan","serve","--host=0.0.0.0","--port=8080"]
