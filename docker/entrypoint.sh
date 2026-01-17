#!/bin/sh
set -e

APP_DIR="/var/www/app"
APP_SRC="/var/www/app-src"

if [ ! -f "${APP_DIR}/artisan" ]; then
    mkdir -p "${APP_DIR}"
    cp -a "${APP_SRC}/." "${APP_DIR}/"
fi

chown -R www-data:www-data "${APP_DIR}/storage" "${APP_DIR}/bootstrap/cache"

cd "${APP_DIR}"

if [ ! -L "${APP_DIR}/public/storage" ]; then
    php artisan storage:link
fi

php artisan migrate --force --seed

exec php-fpm
