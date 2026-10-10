#!/bin/sh
set -eu
mkdir -p /var/www/html/storage/calendar
if [ ! -f /var/www/html/storage/calendar/events.json ]; then
  cp /var/www/html/hml/seed-events.json /var/www/html/storage/calendar/events.json
fi
chown -R www-data:www-data /var/www/html/storage/calendar
exec apache2-foreground
