FROM node:20.12-alpine AS node-build

WORKDIR /app
COPY backend/package.json backend/package-lock.json ./
COPY backend/resources ./resources
COPY backend/public ./public
COPY backend/vite.config.js backend/tailwind.config.js backend/postcss.config.js ./
RUN if [ ! -f package-lock.json ]; then \
        echo "ERROR: backend/package-lock.json missing. Run npm install to generate it." >&2; \
        exit 1; \
    fi
RUN npm ci --no-audit --no-fund
RUN npm run build

FROM php:8.3-fpm-bookworm AS app

WORKDIR /var/www/app

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libpq-dev \
        libzip-dev \
    && docker-php-ext-install pdo_pgsql zip \
    && rm -rf /var/lib/apt/lists/*

COPY backend /var/www/app
COPY --from=node-build /app/public/build /var/www/app/public/build

RUN if [ ! -f /var/www/app/composer.lock ]; then \
        echo "ERROR: backend/composer.lock missing. Run composer install to generate it." >&2; \
        exit 1; \
    fi \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --working-dir=/var/www/app \
    && mkdir -p /var/www/app/storage /var/www/app/bootstrap/cache \
    && chown -R www-data:www-data /var/www/app/storage /var/www/app/bootstrap/cache

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

FROM nginx:1.25-alpine AS nginx

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY --from=app /var/www/app/public /var/www/app/public
