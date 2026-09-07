# Salltak V10.6 UX, Cash Payments, Email Notifications Design

## Goal
Upgrade Salltak's customer and back-office experience without changing imported product prices or existing order/payment calculations. The release adds complete Arabic-facing copy, SVG icons, compact/paginated views, a richer cart-loading experience, cash and cash-on-delivery deposit handling, and configurable multi-recipient email alerts.

## Customer Experience
- All visible system copy is Arabic. Brand/provider proper names such as SHEIN, LYPay, OnePay, Visa and Mastercard may remain in their official spelling, but descriptive UI labels around them are Arabic.
- Imported USD and LYD unit/line prices are read-only for customers. Customers may never submit a price field.
- Long product collections are paginated at the presentation layer; customer cart/order pages use 12 products per page by default while retaining complete totals for the whole cart/order.
- Cart import uses a full-screen waiting overlay with a progress animation and rotating Arabic messages such as "استنا شوية... جاري جلب السلة" and "جاري قراءة المنتجات والأسعار". The submit button remains disabled until navigation completes.
- Customer order pages emphasize status, payment summary, deposit due, remaining balance, timeline, messages and item problems in clearly separated cards.

## Back-office Experience
- Replace character/emoji navigation icons with reusable inline SVG components.
- Dashboard uses Arabic section labels only and a denser command-center layout: primary KPIs, priority orders, pending payments, delayed orders and audit activity.
- Admin lists use Laravel pagination rather than rendering unbounded datasets.
- Order details keep the current audit/status-note behavior but reorganize controls into a polished operations workspace.

## Cash and Cash on Delivery
- Keep `cash` as the physical cash method and introduce a distinct `cash_on_delivery` method.
- Both methods are controlled by the Payment Methods manager and are inactive by default unless explicitly enabled by an administrator.
- `cash` can be offered for an advance/deposit when configuration `allow_deposit=true`; a customer chooses the method and submits a cash-payment intent. The payment enters `pending_verification` until a back-office user confirms actual receipt of the cash.
- `cash_on_delivery` is available for the balance only in delivery-stage statuses. It may still require the order-level deposit before purchasing; the deposit is paid through any enabled deposit-capable method, including cash if `cash.allow_deposit=true`.
- Payment-method configuration exposes availability, whether deposits are allowed, customer instructions, and proof requirements. No API is invented for cash methods.

## Email Notifications
- Add system settings `notification_emails`, `notify_email_new_order`, `notify_email_new_message`, and `notify_email_payment`.
- `notification_emails` accepts multiple comma/newline-separated email addresses, is normalized and deduplicated, and is validated individually.
- When a customer creates an order, sends a message, or submits a payment, the existing in-app notification remains and an Arabic email is sent to every configured recipient for that event category.
- Email sending uses Laravel Mail and must fail safely: a mail transport failure is logged and must not make the customer action fail.
- Default `MAIL_MAILER=log` remains safe for local development; production SMTP credentials are configured through `.env`.

## Safety and Compatibility
- MySQL remains the only database configuration.
- Existing SHEIN importer, USD pricing, color/size extraction, totals, deposits, audit log and status workflow remain unchanged except for the explicit cash-method behavior described above.
- Existing databases receive additive migrations only; no `migrate:fresh` is required.
- UI remains Bootstrap 5, RTL and responsive.
