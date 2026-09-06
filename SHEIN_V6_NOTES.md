# Salltak — SHEIN Share Endpoint V6 Patch

This patch targets the exact SHEIN shared-cart BFF endpoint observed in Chromium:

`/bff-api/order/cart/share/landing`

Changes:
- Treats the shared-cart landing response as a trusted cart context.
- Allows generic `num` / `count` quantity fields only inside this exact trusted endpoint.
- Parses bounded JSON strings embedded inside the BFF response.
- Adds request method, POST data, response top-level keys, body size, and endpoint marker to `response_meta`.
- When `debug: true` is passed, the exact shared-cart landing JSON response is included in `payloads` even if no cart items match yet.
- Keeps strict recommendation/trend/search filtering elsewhere.

## Install
Copy this patch over the existing Salltak project, replacing:

`scripts/shein-browser-import.mjs`

Then run:

```bat
php artisan optimize:clear
npm run browser:smoke
```

## Debug test
In `shein-test.json`, add:

```json
"debug": true
```

Then run:

```bat
type shein-test.json | node scripts\shein-browser-import.mjs > shein-debug-v6.json
notepad shein-debug-v6.json
```

Look for the `response_meta` entry whose URL contains:

`/bff-api/order/cart/share/landing`

and the corresponding object in `payloads`.
