# Salltak V10.9.1 — Full test regression fixes

This patch addresses the failures reported from the real `php artisan test` run on V10.9.

## Fixed root causes

1. Customer update email now sends a real Laravel `Mailable` (`App\Mail\OrderUpdateMail`) instead of raw `Mail::html`, so `Mail::fake()` can observe the message and the production email path remains explicit/testable.
2. Order payment availability no longer applies Libya-catalog activation schemas to unrelated custom/manual payment methods created outside the catalog. Catalog-backed methods still require their official configuration before exposure.
3. Payment catalog tests now expect 23 methods because V10.6 intentionally added `cash` and `cash_on_delivery`.
4. Responsive cart-grid test now creates a cart before asserting the rendered card column class. The old test asserted a card class while rendering only the empty state.
5. SHEIN feature tests now follow the Arabic UI: visible currency is asserted as `دولار`, not the English `USD` code.
6. The escaped SHEIN fixture now includes authoritative `usdAmount=5.19`, matching the documented V8 USD-first rule instead of expecting a fabricated conversion from an AED-only amount.
7. Empty SHEIN preview state now displays the Arabic pricing currency so the customer still knows which currency is being used when no products were readable.

## No migration required

Run:

```bash
php artisan optimize:clear
php artisan test
```
