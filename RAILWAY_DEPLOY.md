# Railway deployment — Salltak

This project requires Laravel + PHP 8.3 + Node.js 20+ + Playwright Chromium + MySQL.

## Railway services

Create two services in one Railway project:

1. **Salltak app** — deploy this GitHub repository from branch `deploy/railway` for the first test.
2. **MySQL** — add Railway MySQL.

Railway will detect the root `Dockerfile` automatically.

## Application variables

Set these in the Salltak service:

```env
APP_NAME="سلات ليبيا"
APP_ENV=production
APP_DEBUG=false
APP_KEY=<generate a Laravel APP_KEY locally and paste it here>
APP_LOCALE=ar
APP_FALLBACK_LOCALE=en
LOG_CHANNEL=stderr
LOG_LEVEL=info

DB_CONNECTION=mysql
DB_HOST=${{MySQL.MYSQLHOST}}
DB_PORT=${{MySQL.MYSQLPORT}}
DB_DATABASE=${{MySQL.MYSQLDATABASE}}
DB_USERNAME=${{MySQL.MYSQLUSER}}
DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}

SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
FILESYSTEM_DISK=local

CART_IMPORT_TIMEOUT=30
SHEIN_BROWSER_ENABLED=true
SHEIN_BROWSER_NODE=/usr/bin/node
SHEIN_BROWSER_HEADLESS=true
SHEIN_BROWSER_TIMEOUT_MS=35000
SHEIN_BROWSER_PROCESS_TIMEOUT=110
SHEIN_BROWSER_MANUAL_CHALLENGE_WAIT_MS=0
SHEIN_BROWSER_PROFILE_DIR=/app/storage/app/shein-browser-profile
```

Do not commit production secrets to GitHub.

## First database setup

After MySQL is connected and the application container is healthy, run once from Railway's service shell or deployment command environment:

```bash
php artisan migrate --force
```

Only seed demo data if this is intentionally a demo environment. Do not run the demo seeder on production by default.

## Public URL

Generate a Railway domain for the app service. Then set:

```env
APP_URL=https://YOUR-RAILWAY-DOMAIN
```

Redeploy after changing it.

## Browser validation

Inside the app container, the following should succeed:

```bash
node --version
php --version
node -e "import('playwright-chromium').then(() => console.log('PLAYWRIGHT OK'))"
node scripts/shein-browser-check.mjs
```

Then test a real SHEIN shared-cart URL from the Salltak UI.

## Notes

- Railway provides `PORT`; the Docker command starts Laravel on that port.
- Chromium is installed during the Docker build with its Linux dependencies.
- The browser importer is headless in production.
- `storage/app/shein-browser-profile` is writable inside the container. Container-local files are ephemeral across redeploys; the browser profile must not be treated as permanent user data.
