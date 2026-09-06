# Test Report — Cartly Libya / SHEIN Browser Importer

## Tests executed in the packaging environment

### PHP smoke tests

`php scripts/smoke_test.php`

Result: **18/18 PASS**

Covered:

- line totals and LYD conversion
- SHEIN onelink detection
- native SHEIN Share Cart detection
- domain matching
- multiple embedded cart items
- quantity / currency / color / size extraction
- escaped React/Next state extraction
- nested image and attributes
- Playwright item normalization inside the PHP adapter
- browser worker/package/env integration files

### Responsive UI smoke tests

`php scripts/ui_smoke_test.php`

Result: **10/10 PASS**

Covered Bootstrap 5, RTL, local assets, responsive tables, cart editor, admin offcanvas and pagination.

### Syntax checks

- `node --check scripts/shein-browser-import.mjs` — PASS
- `node --check scripts/shein-browser-check.mjs` — PASS
- PHP lint across **58 PHP files** — PASS, no syntax errors

## Laravel feature tests added/updated

`tests/Feature/SheinImportTest.php` includes coverage for:

- JSON-LD product import
- native SHEIN share-cart link and AED detection
- multiple items from initial state
- escaped Next/React state
- HTTP 429 behavior
- Playwright fallback returning cart items
- Playwright security-challenge state and Mac headed-mode guidance

## Runtime note

Composer/vendor and the npm Playwright package were not available in the packaging environment, and npm dependency download could not complete there. Therefore this report does **not** claim a successful live Chromium launch against SHEIN from the packaging environment.

On the target Mac run:

```bash
composer install
npm install
npx playwright install chromium
npm run browser:smoke
php artisan test
```

A successful `npm run browser:smoke` confirms Playwright Chromium is installed and launchable locally. The actual SHEIN import can then be tested with the real Share Cart URL.

## V8 USD-first SHEIN pricing
- Shared-cart price source: `usdAmount`.
- Shared-cart currency: `USD`.
- `local_country=AE` no longer forces AED for SHEIN shared-cart totals.
- USD to LYD uses the active USD exchange rate.
