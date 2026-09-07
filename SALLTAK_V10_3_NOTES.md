# Salltak V10.3 — Libya Payments

## What changed
- Expanded the Libya payment catalog to 22 logical entries.
- Kept LYPay and OnePay as distinct instant-payment options.
- Added traditional bank transfer, NUMO/merchant QR, local cards, Visa, Mastercard, POS/SoftPOS and cash/on-delivery.
- Retained all CBL-listed wallet and card/mobile-banking companies from the V10.2 catalog.
- Added provider-specific configuration schemas instead of showing the same API fields for every method.
- Admin activation is blocked until required receiver/merchant configuration is complete.
- Customer sees only active, configured methods valid for the amount and current order stage.
- Delivery-only methods (cash and POS/SoftPOS) are hidden during early deposit stages.
- External-card entries require a contracted Acquirer/Processor, Merchant ID and Checkout URL.
- No provider API is invented. `partner_api` is intentionally blocked from customer activation until a real connector is implemented.
- Payment settings page is now a compact responsive card grid; full configuration opens in a Bootstrap modal.

## LYPay
LYPay supports merchant receipt via IBAN and merchant QR. Salltak defaults to merchant QR/manual verification. Public CBL developer documentation describes Bearer-token APIs, webhooks/HMAC and NUMO QR, but direct CBL API access is documented for banks/licensed financial institutions. Therefore Salltak does not pretend to be directly connected to the CBL API.

## OnePay
OnePay is kept as an official instant-payment option. Public CBL sources confirm the service, but a public Libya merchant API specification is not assumed. Configure the merchant/beneficiary/QR details supplied by the participating bank/provider and use manual verification until official merchant integration documents are provided.

## Card processors and wallets
The CBL electronic-payment directory is used for official company names/activity. When merchant API documentation is not public, the system asks only for the merchant/account/checkout information that the contracted provider supplies. API keys/secrets remain encrypted and are never shown to customers.

## Updating an existing V10.2 database
Run:

```bash
php artisan optimize:clear
php artisan migrate
php artisan db:seed --class=Database\\Seeders\\LibyaPaymentMethodsSeeder
```

Then open **لوحة التحكم → طرق الدفع** and configure/activate only the methods you actually accept.
