#!/usr/bin/env bash
set -euo pipefail

[ -f .env ] || cp .env.example .env

if command -v mysql >/dev/null 2>&1; then
  echo "Creating MySQL databases salltak and salltak_test if needed..."
  mysql -h 127.0.0.1 -P 3306 -u root < database/mysql_setup.sql
else
  echo "MySQL CLI was not found in PATH."
  echo "Create salltak and salltak_test, or import database/mysql_setup.sql, before migrations."
fi

composer install
npm install
npx playwright install chromium
php artisan key:generate --force
php artisan optimize:clear
php artisan migrate --seed
php artisan test
php artisan serve
