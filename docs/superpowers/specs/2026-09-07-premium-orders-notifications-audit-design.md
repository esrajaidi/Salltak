# Salltak Premium Orders, Notifications & Audit Design

## Goal
Upgrade Salltak's customer and backoffice experience without changing the existing SHEIN import, MySQL-only setup, Libyan payment catalog, deposit rules, or order/payment business logic.

## Customer Experience
- Customer product USD price is immutable/read-only. Customer may never submit or edit product price fields.
- Order details use a premium responsive layout with status progress, financial summary, item cards, payment area, customer-visible activity timeline, and conversation.
- Customer-visible status notes appear in the order timeline. Internal notes never appear to customers.
- Important events create in-app notifications: status changes, customer-action requests, payment terms, payment verification/rejection, and customer-visible notes.

## Backoffice Experience
- Admin/order manager order screen is an operations workspace: order identity, assignee, customer data, financial summary, item review, status transition, notes, payments, communications, and full activity history.
- Every status transition accepts an optional note and note visibility. Rejected/cancelled/needs-customer-action transitions require a meaningful customer-facing reason.
- Backoffice can add notes without changing status. Notes can be `customer` or `internal`.
- All history rows preserve actor, event type, from/to state, note, visibility, metadata and timestamp.
- SweetAlert2 confirms sensitive actions; session success/errors display as SweetAlert2 toasts/modals with native fallback.

## Monitoring
- Add a system audit log for order/payment/assignment/item-review/payment-method/user-management actions.
- Dashboard shows actionable queues: new/review orders, customer-action orders, pending payments, overdue/aging work, recent activity, and latest audit entries.
- Notifications bell works for both customer and backoffice with unread count, recent notifications, mark-one-read and mark-all-read.

## Authorization
- Avoid runtime dependency on custom `User::isBackoffice()` / `isAdmin()` methods in Blade, middleware, and auth redirection. Role checks use `role` values directly (`admin`, `order_manager`, `customer`). The helper methods remain for backward compatibility only.

## Data Model
- Extend `order_status_histories` with `event_type`, `visibility`, `metadata`.
- Create `app_notifications` for database-backed in-app notifications.
- Create `audit_logs` for system monitoring.

## UI
- Keep V9/V10 navy + teal + cyan + gold brand, no purple.
- Responsive Bootstrap 5 RTL.
- Premium cards, sticky summary/action rails on desktop, compact stacked layout on mobile.
- No long always-open control panels; use sections/cards/modals where appropriate.

## Testing
- Regression checks for role safety and missing custom-method crash.
- History visibility and note persistence checks.
- Customer cannot receive internal notes in timeline.
- Notification and audit source checks.
- Static UI checks for SweetAlert, notification bell, premium order sections and read-only customer pricing.
- Existing V10.4 regression/static/Node/PHP lint checks remain green.
