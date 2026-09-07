# Salltak V10.2 Notes

## What changed
- Fixed disappearing imported-cart content caused by the reveal IntersectionObserver threshold on very tall cards.
- Added centralized Libya payment-method catalog with 17 customer/payment options.
- Added idempotent catalog sync service and dedicated seeder.
- Added admin “install/refresh Libya catalog” action.
- Added full generic configuration fields for merchant/account/API settings.
- New providers are inactive by default; admin controls ON/OFF.
- Customer only sees active methods that support the current due amount.
- Payment choice UI changed to responsive radio cards.
- Manual proof rules are configurable (`none`, `reference`, `receipt`, `reference_or_receipt`).
- API-mode methods are not presented as operational unless a real connector exists; fake gateway success is never generated.
- MySQL-only configuration from V10.1 remains unchanged.

## Catalog basis
Catalog prepared from the Central Bank of Libya electronic-payment directory as published/updated in September 2026, plus the national instant-payment services LYPay and OnePay.

Sources used during implementation:
- https://cbl.gov.ly/electronic-payment/
- https://lypay.gov.ly/
- https://cbl.gov.ly/إحصائيات-خدمات-الدفع-الفوري/

## Upgrade
```bash
php artisan optimize:clear
php artisan migrate
php artisan db:seed --class=Database\\Seeders\\LibyaPaymentMethodsSeeder
```

Then visit Admin → Payment Methods and configure/enable the methods you actually accept.
