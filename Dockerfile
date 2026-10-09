FROM php:8.3-apache
RUN a2enmod rewrite headers expires
COPY app/ /var/www/html/app/
COPY admin/ /var/www/html/admin/
COPY assets/ /var/www/html/assets/
COPY index.php health.php manifest.json service-worker.js .htaccess /var/www/html/
RUN mkdir -p /var/www/html/storage/data /var/www/html/storage/image-cache && chown -R www-data:www-data /var/www/html
RUN printf '<Directory /var/www/html/storage>\nRequire all denied\n</Directory>\n' > /etc/apache2/conf-available/conheca-storage.conf && a2enconf conheca-storage
EXPOSE 80
