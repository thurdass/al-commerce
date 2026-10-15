# syntax=docker/dockerfile:1
FROM php:8.3-fpm-bookworm AS php-base

RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip libfcgi-bin libfreetype6-dev libjpeg62-turbo-dev libpng-dev \
        libicu-dev libonig-dev libsqlite3-dev libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" bcmath gd intl mbstring opcache pcntl pdo_mysql pdo_sqlite zip \
    && rm -rf /var/lib/apt/lists/*

COPY docker/php/app.ini /usr/local/etc/php/conf.d/99-app.ini
COPY docker/php/fpm.conf /usr/local/etc/php-fpm.d/zz-app.conf
COPY --chmod=755 docker/php/entrypoint.sh /usr/local/bin/app-entrypoint
COPY --chmod=755 docker/php/healthcheck.sh /usr/local/bin/app-healthcheck

WORKDIR /var/www/html
RUN mkdir -p vendor bootstrap/cache public/build \
        storage/app/private storage/app/public storage/framework/cache/data \
        storage/framework/sessions storage/framework/views storage/logs \
    && chown -R www-data:www-data /var/www/html

ENTRYPOINT ["app-entrypoint"]
CMD ["php-fpm"]

FROM php-base AS development
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
ARG APP_UID=1000
ARG APP_GID=1000
RUN groupadd --gid "$APP_GID" application \
    && useradd --uid "$APP_UID" --gid application --create-home application \
    && chown -R application:application /var/www/html
USER application

FROM php-base AS dependencies
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --prefer-dist --no-interaction --no-progress
COPY . .
RUN composer dump-autoload --no-dev --classmap-authoritative --no-interaction

FROM node:24-bookworm-slim AS assets
WORKDIR /var/www/html
COPY package.json package-lock.json ./
RUN npm ci --ignore-scripts
COPY --from=dependencies /var/www/html/vendor ./vendor
COPY resources resources
COPY public public
COPY vite.config.js ./
RUN npm run build

FROM node:24-bookworm-slim AS vite-development
ARG APP_UID=1000
ARG APP_GID=1000
WORKDIR /var/www/html
RUN mkdir -p node_modules public/build storage \
    && chown -R "$APP_UID:$APP_GID" /var/www/html
USER ${APP_UID}:${APP_GID}

FROM php-base AS production
COPY --from=dependencies /var/www/html /var/www/html
COPY --from=assets /var/www/html/public/build /var/www/html/public/build
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && chown -R www-data:www-data storage bootstrap/cache
USER www-data

FROM nginx:1.28-alpine AS web-production
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY --from=production /var/www/html/public /var/www/html/public
