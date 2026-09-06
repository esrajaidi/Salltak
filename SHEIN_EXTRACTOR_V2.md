# SHEIN Extractor V2

This revision fixes the case where Chromium successfully renders a SHEIN shared-cart page but the cart items are not recognized.

Changes:
- scans JSON responses from SHEIN recursively instead of relying only on endpoint names;
- reads common hydrated browser state objects and JSON script tags;
- recognizes additional SHEIN product, SKU, price, quantity, image and variant field shapes;
- falls back to responsive DOM cart/product cards;
- deduplicates items and prefers the richest item record;
- corrects the preview notice so it only says items were extracted when the import actually succeeds;
- exposes network/state/DOM extraction counters in import metadata for diagnostics.

After replacing/upgrading the project on Mac:

```bash
npm install
npx playwright install chromium
npm run browser:smoke
php artisan optimize:clear
php artisan serve
```

For a visible browser when SHEIN asks for verification:

```env
SHEIN_BROWSER_HEADLESS=false
```

Then run `php artisan optimize:clear` and retry the shared-cart URL.
