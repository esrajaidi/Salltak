# Salltak V10 — Orders, Deposits & Payments

V10 extends V9 without changing the SHEIN importer or the navy/teal/sky/gold UI identity.

## Added
- Convert any saved cart into a customer order.
- Order statuses from submission/review through purchase, shipping, Libya arrival and delivery.
- Rejection/cancellation reasons and full status history.
- Assignment to admin or `order_manager` staff.
- Item-level review: OK, unavailable, price changed, color/size issue, rejected.
- Customer accept/reject reply and text response per item.
- General order conversation.
- Configurable deposit rules by total bands (percentage/fixed).
- Automatic deposit calculation plus per-order manual override with reason.
- Multiple partial payments, deposit tracking, paid/remaining totals.
- Prevent delivery while a balance remains.
- Payment receipt upload and transaction reference.
- Backoffice verification/rejection of payments.
- Admin payment-method manager with ON/OFF, type, ordering, min/max, fee, instructions and encrypted configuration.
- Seeded provider entries: LYPay, OnePay, bank transfer, wallet/manual transfer, local card gateway and cash. Additional providers can be created in the dashboard.
- New demo role: order manager.

## Important about online gateways
The database/UI configuration is ready for provider credentials, but V10 does not pretend that all Libyan providers share one API. Provider-specific automatic checkout/webhook integration must use that provider's official API contract, merchant account and credentials. Until then, the manual receipt/reference verification path is fully supported.

## After updating an existing installation
```bash
php artisan migrate
php artisan db:seed
php artisan storage:link
php artisan optimize:clear
```
Keep the existing `.env`, especially the working `SHEIN_BROWSER_NODE` absolute path on Laragon/Apache.
