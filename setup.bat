@echo off
if not exist .env copy .env.example .env
call composer install
call npm install
call npx playwright install chromium
php artisan key:generate
if not exist database\database.sqlite type nul > database\database.sqlite
php artisan migrate --seed
php artisan test
php artisan serve
