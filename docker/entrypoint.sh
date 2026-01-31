#!/bin/sh
set -e

APP_DIR="/var/www/app"

mkdir -p "${APP_DIR}/storage" "${APP_DIR}/bootstrap/cache"
chown -R www-data:www-data "${APP_DIR}/storage" "${APP_DIR}/bootstrap/cache"

cd "${APP_DIR}"

if [ ! -L "${APP_DIR}/public/storage" ]; then
    php artisan storage:link || true
fi

if [ "${RUN_MIGRATIONS}" = "true" ]; then
    php artisan migrate --force --seed
fi

exec php-fpm
