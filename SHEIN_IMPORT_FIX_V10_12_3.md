# SHEIN Import Fix V10.12.3

## Root cause confirmed

The shared-cart URL is valid, but SHEIN is returning inconsistent results between requests.

Evidence from the supplied debug runs for group `851956795`:

- A successful run returned `35` cart items (`network_item_count=35`, `final_item_count=35`).
- A failing run loaded the page successfully, but the storefront shared-cart BFF request returned HTTP `403`; the explicit in-browser BFF request returned HTTP `200` with `0` matched items, so the worker finished with `final_item_count=0`.
- This explains why adding `dd()` can show 35 items while the next normal page request can still render an empty state: they are separate SHEIN requests and SHEIN may answer them differently.

## Fix

`SheinBrowserImporter` now:

1. Runs the normal Playwright import.
2. If Chromium reports `status=loaded` but returns zero cart items, it automatically retries once.
3. The retry uses a fresh temporary Chromium profile, avoiding a poisoned/stale cookie or browser session.
4. If the retry returns products, that successful result is used by Laravel and the cart preview renders the products.
5. Retry diagnostics are added to the import metadata.

Additional metadata now exposed by `SheinShareAdapter`:

- `browser_direct_bff_status`
- `browser_direct_bff_matched_items`
- `browser_import_attempt_count`
- `browser_fresh_profile_retry`
- `browser_retry_status` (when relevant)

## Regression test

Added:

`tests/Architecture/shein_browser_retry_check.php`

The test simulates:

- attempt 1: SHEIN page loaded, zero items
- attempt 2: successful cart response with products

Expected result: the importer returns the products from attempt 2 and reports two attempts.
