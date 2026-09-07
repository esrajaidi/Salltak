@echo off
setlocal

if not exist .env copy .env.example .env

where mysql >nul 2>nul
if %ERRORLEVEL%==0 (
    echo Creating MySQL databases salltak and salltak_test if needed...
    mysql -h 127.0.0.1 -P 3306 -u root < database\mysql_setup.sql
    if errorlevel 1 (
        echo.
        echo Could not create the MySQL databases automatically.
        echo Import database\mysql_setup.sql using phpMyAdmin, then run setup.bat again.
        exit /b 1
    )
) else (
    echo MySQL CLI was not found in PATH.
    echo Make sure databases salltak and salltak_test exist in Laragon/phpMyAdmin.
    echo You can import database\mysql_setup.sql manually.
)

call composer install
if errorlevel 1 exit /b 1

call npm install
if errorlevel 1 exit /b 1

call npx playwright install chromium
if errorlevel 1 exit /b 1

php artisan key:generate --force
if errorlevel 1 exit /b 1

php artisan optimize:clear
if errorlevel 1 exit /b 1

php artisan migrate --seed
if errorlevel 1 exit /b 1

php artisan test
if errorlevel 1 exit /b 1

php artisan serve
