FROM node:22-bookworm AS node

FROM composer:2 AS composer

FROM php:8.3-cli-bookworm

ENV DEBIAN_FRONTEND=noninteractive \
    COMPOSER_ALLOW_SUPERUSER=1 \
    PLAYWRIGHT_BROWSERS_PATH=/ms-playwright \
    SHEIN_BROWSER_ENABLED=true \
    SHEIN_BROWSER_NODE=node \
    SHEIN_BROWSER_HEADLESS=true

COPY --from=node /usr/local/ /usr/local/
COPY --from=composer /usr/bin/composer /usr/bin/composer

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
        git \
        unzip \
        libonig-dev \
        libxml2-dev \
        libzip-dev \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql mbstring bcmath pcntl \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY composer.json composer.lock* ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --no-scripts

COPY package.json package-lock.json ./
RUN npm ci \
    && npx playwright install --with-deps chromium

COPY . .

RUN composer dump-autoload --optimize --no-dev \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chmod -R ug+rwX storage bootstrap/cache \
    && chmod +x docker/railway-start.sh

EXPOSE 8080

CMD ["./docker/railway-start.sh"]
