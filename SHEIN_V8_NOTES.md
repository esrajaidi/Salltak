# SHEIN V8 — USD-first pricing

- SHEIN shared carts now use `usdAmount` from SHEIN BFF responses as the authoritative product price.
- The imported cart currency is always `USD`, including links with `local_country=AE`.
- LYD totals use the active `USD -> LYD` exchange rate from the admin exchange-rates screen.
- DOM fallback no longer relabels AED/SAR numeric prices as USD; it only accepts prices explicitly shown as USD/$ when network/state data is unavailable.
- Existing color/size extraction from V7 remains unchanged.
