FROM node:20.12-alpine AS node-build

WORKDIR /app
COPY backend/package.json backend/package-lock.json* ./
COPY backend/resources ./resources
COPY backend/vite.config.js backend/tailwind.config.js backend/postcss.config.js ./
RUN npm install
RUN npm run build

FROM php:8.3-fpm-bookworm

WORKDIR /var/www/app

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libpq-dev \
        libzip-dev \
    && docker-php-ext-install pdo_pgsql zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=node-build /app/public/build /var/www/app-src/public/build

COPY backend /var/www/app-src

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --working-dir=/var/www/app-src \
    && chown -R www-data:www-data /var/www/app-src

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
