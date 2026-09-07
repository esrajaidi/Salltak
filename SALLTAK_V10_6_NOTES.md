# Salltak V10.6 — UX, Cash, Pagination, Email Alerts

## Customer UX
- Customer-facing imported product prices remain server-controlled and read-only.
- Cart import preview stores a trusted snapshot in the session; customer submissions can change quantity only, not name/price/color/size/currency.
- Long cart/order item lists are paginated (12 items per page on saved cart/order details; preview uses client-side paging).
- Cart import now shows a full-screen Arabic waiting experience with rotating progress messages such as «استنا شوية... جاري جلب السلة».
- Currency/status labels shown to users are Arabic while stored technical codes remain unchanged.

## Admin UX
- Administration navigation/action icons use reusable inline SVG icons.
- Dashboard copy is Arabic and organized as an operations command center.
- Admin order/cart/payment-method lists use pagination instead of unbounded pages.
- SweetAlert2 is used for confirmations/toasts, with a browser fallback only when SweetAlert cannot load.

## Cash payment logic
- `cash`: physical cash can be configured for deposit and/or balance. A submitted cash payment remains `pending_verification` until staff confirms actual receipt.
- `cash_on_delivery`: separate method for the final balance during delivery-stage statuses only. It does not replace a required advance deposit.
- Both methods are controlled from Payment Methods and are inactive after catalog installation until the administrator enables them.
- Payment configuration supports `allow_deposit` and `allow_balance` independently.

## Libya payment catalog
The current catalog contains 23 logical entries, including LYPay, OnePay, bank transfer, NUMO QR, local cards, Visa, Mastercard, POS/SoftPOS, cash, cash on delivery, and the wallet/card providers already represented from the Libya payment directory.

## Email alerts
Admin settings now support multiple notification recipient emails. Recipients may be entered with commas or new lines and are normalized/deduplicated.

Optional email events:
- new customer order;
- new customer message/item reply;
- submitted payment / deposit proof.

Sending uses Laravel Mail and fails safely: mail transport errors are logged and do not fail the customer request. Local `MAIL_MAILER=log` remains suitable for development; configure SMTP in `.env` for real delivery.

## Existing database upgrade
No `migrate:fresh` is required.

```bash
php artisan optimize:clear
php artisan migrate
php artisan db:seed --class=Database\\Seeders\\LibyaPaymentMethodsSeeder
```

Then configure enabled payment methods and email recipients from the admin panel.
