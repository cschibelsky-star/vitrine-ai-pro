FROM php:8.3-fpm
RUN apt-get update && apt-get install -y git unzip libpng-dev libonig-dev libxml2-dev libzip-dev libicu-dev libsqlite3-dev \
    && docker-php-ext-install pdo_sqlite mbstring bcmath gd zip intl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --no-dev --no-scripts --no-autoloader
COPY app ./app
COPY bootstrap ./bootstrap
COPY config ./config
COPY routes ./routes
COPY resources ./resources
COPY public ./public
COPY database ./database
COPY artisan ./artisan
COPY docker/assessor/php/entrypoint.sh /usr/local/bin/assessor-entrypoint
RUN mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache /var/lib/assessor \
    && composer dump-autoload --optimize --no-dev --no-scripts \
    && chmod +x /usr/local/bin/assessor-entrypoint \
    && chown -R www-data:www-data storage bootstrap/cache /var/lib/assessor
USER www-data
ENTRYPOINT ["/usr/local/bin/assessor-entrypoint"]
CMD ["php-fpm", "-F"]
