#!/bin/sh
set -e

if [ ! -f /var/www/html/artisan ]; then
    mkdir -p /var/www/html
    cp -a /var/www/app/. /var/www/html/
fi

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

cd /var/www/html

php artisan migrate --force --seed

exec php-fpm
