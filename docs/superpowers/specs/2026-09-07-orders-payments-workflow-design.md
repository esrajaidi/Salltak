# Salltak Orders & Payments Workflow Design

## Goal
Turn a saved cart into a managed order lifecycle with review, item-level issues/replies, assignment to responsible staff, configurable payment methods, automatic or manual deposits, multiple partial payments, verification, status history, and final delivery.

## Core Flow
Saved cart → submitted → under_review → needs_customer_action (when required) → approved → awaiting_deposit/awaiting_payment → deposit_paid/partial/paid → purchasing → ordered → shipped → arrived_libya → awaiting_balance → ready_for_delivery → out_for_delivery → delivered. Rejected and cancelled are terminal alternatives. Rejection requires a reason.

## Payments
Payment methods are data-driven and controlled by the system administrator. Each method can be enabled/disabled, ordered, limited by min/max amount, configured with fixed/percentage fees, instructions, and encrypted provider configuration. Seeded methods are LYPay, OnePay, bank transfer, local wallet/manual transfer, local card gateway, and cash; additional Libyan providers can be created from the dashboard without code changes.

API credentials are configurable but no provider-specific remote API call is assumed without that provider's official API contract and live credentials. Manual/receipt verification works end-to-end.

## Deposit Rules
Admin manages amount bands. Each rule has min/max order total and either percentage or fixed deposit value. Approval calculates the deposit automatically; authorized staff can override a specific order with a reason. Multiple payments accumulate. The order cannot be marked delivered while a positive balance remains.

## Roles
- admin: full platform configuration and order operations.
- order_manager: order review, assignment, item decisions, messages, payment verification, and workflow transitions; cannot edit platform-wide payment/deposit configuration.
- customer: owns carts/orders, replies to item issues, submits payments and receipts, sees timeline and balances.

## Data
New tables: orders, order_items, order_messages, order_status_histories, payment_methods, payments, deposit_rules. Orders snapshot cart totals/items so later cart changes do not alter submitted orders.

## UI
Keep V9 navy/teal/sky/gold identity. Add customer Orders pages and order detail timeline, item issue/reply cards, payment summary/methods, and payment history. Add backoffice Orders pages, assignment/review controls, item review, payment verification, and admin Payment Methods + Deposit Rules configuration pages. Responsive RTL Bootstrap styling remains mandatory.
