#!/usr/bin/env sh
set -eu

php artisan optimize:clear
php artisan migrate --force
php artisan config:cache
php artisan route:cache || true
php artisan view:cache || true

exec php artisan serve --host=0.0.0.0 --port="${PORT:-8080}"
