#!/usr/bin/env bash
set -e
[ -f .env ] || cp .env.example .env
composer install
npm install
npx playwright install chromium
php artisan key:generate
[ -f database/database.sqlite ] || touch database/database.sqlite
php artisan migrate --seed
php artisan test
php artisan serve
