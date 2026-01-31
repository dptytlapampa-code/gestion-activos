#!/bin/sh
set -e

APP_DIR="/var/www/app"

mkdir -p "${APP_DIR}/storage" "${APP_DIR}/bootstrap/cache"
chown -R www-data:www-data "${APP_DIR}/storage" "${APP_DIR}/bootstrap/cache"

cd "${APP_DIR}"

if [ ! -f "${APP_DIR}/.env" ]; then
    cp "${APP_DIR}/.env.example" "${APP_DIR}/.env"
fi

if [ -z "${APP_KEY}" ]; then
    CURRENT_KEY=$(grep "^APP_KEY=" "${APP_DIR}/.env" | cut -d= -f2- || true)
    if [ -z "${CURRENT_KEY}" ]; then
        php artisan key:generate --force
    fi
fi

if [ ! -L "${APP_DIR}/public/storage" ]; then
    php artisan storage:link || true
fi

if [ "${RUN_MIGRATIONS}" = "true" ]; then
    php artisan migrate --force --seed
fi

exec php-fpm
