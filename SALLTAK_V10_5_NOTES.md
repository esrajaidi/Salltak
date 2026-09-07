# Salltak V10.5 — Premium Orders, Notifications & Audit

## What changed

V10.5 upgrades the customer and backoffice order experience while preserving the existing SHEIN/USD import, MySQL-only setup, deposit workflow, and Libyan payment catalog.

### Customer order experience
- Premium responsive order details page with status progress tracker.
- Product original price (USD/source currency) is display-only and cannot be edited by the customer.
- LYD reviewed price, item total, deposit, paid amount, remaining balance and payment status are shown clearly.
- Customer-visible activity timeline shows status changes and customer-facing notes.
- Internal notes never render in the customer timeline.
- Product issues (price change, unavailable item, option issue) can receive customer accept/reject replies.
- Payment methods still support public merchant details, QR image and external checkout URL where configured.

### Backoffice order workspace
- Premium operations workspace for admin/order managers.
- Item-by-item review with original USD price preserved and optional reviewed LYD price.
- Status transitions support a note and note visibility.
- Sensitive states require a reason: rejected, cancelled and needs-customer-action.
- Separate note action supports:
  - internal note (staff only)
  - customer note (shown to customer + notification)
- Full activity timeline shows actor, status transition, visibility, note and time.
- Compact accordion keeps order controls from making the side panel excessively long.

### Notifications
- New database-backed `app_notifications` table.
- Bell with unread count and latest notifications in both customer and backoffice layouts.
- Full notification inbox with mark-one-read and mark-all-read.
- Notifications are created for important order, item, message and payment activity.

### Audit monitoring
- New `audit_logs` table.
- Audits order status, notes, assignment, item review, payments, payment method changes, user role/activation, system settings, deposit rules, exchange rates and store changes.
- Dashboard now includes an audit activity stream and operational queues.

### Dashboard monitoring
- Action queue for orders needing attention.
- Pending payment verification queue.
- Aging orders older than 24 hours.
- Recent system activity/audit feed.
- Premium metrics and responsive command-center layout.

### SweetAlert2
- Session success messages use SweetAlert2 toast notifications.
- Validation errors use SweetAlert2 error modal.
- Sensitive forms can use `data-confirm` and receive a SweetAlert2 confirmation dialog with native fallback.

### Role safety fix
Runtime Blade/middleware/auth redirects no longer require `User::isBackoffice()` or `User::isAdmin()` custom methods. Role checks use the stored role values directly (`admin`, `order_manager`, `customer`), while helper methods remain in the model for backward compatibility.

## Database upgrade
Run on the existing MySQL database:

```bash
php artisan optimize:clear
php artisan migrate
```

The new migration is:

`2026_09_07_133000_add_activity_notifications_and_audit.php`

It adds activity metadata to `order_status_histories` and creates `app_notifications` and `audit_logs`. It does not require `migrate:fresh`.

## Recommended verification on your Laragon machine

```bash
composer install
php artisan optimize:clear
php artisan migrate
php artisan test
```

Keep the existing `.env` MySQL and `SHEIN_BROWSER_NODE` values.
