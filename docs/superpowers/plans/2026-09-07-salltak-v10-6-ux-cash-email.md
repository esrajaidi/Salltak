# Salltak V10.6 UX, Cash Payments, Email Notifications Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [x]`) syntax for tracking.

**Goal:** Ship a fully Arabic, SVG-based responsive UX with pagination and cart loading feedback, plus configurable cash/deposit payment methods and multi-recipient email alerts.

**Architecture:** Preserve existing order/payment domain services and add narrowly scoped helpers for email recipient parsing and event email dispatch. Extend payment catalog configuration rather than creating fake provider APIs. Pagination is implemented in controllers/Blade presentation without changing stored totals or imported prices.

**Tech Stack:** Laravel 13, PHP 8.3, MySQL, Blade, Bootstrap 5, SweetAlert2, vanilla JavaScript, Laravel Mail.

**Spec:** `docs/superpowers/specs/2026-09-07-salltak-v10-6-ux-cash-email-design.md`

## Global Constraints
- MySQL only; do not reintroduce SQLite.
- Customer must never edit imported USD or LYD prices.
- Keep SHEIN USD/color/size extraction and order/deposit calculations intact.
- All visible application copy is Arabic except official provider/brand names.
- Replace UI glyph/emoji navigation/action icons with inline SVG.
- Existing databases must upgrade with `php artisan migrate`, not `migrate:fresh`.

---

### Task 1: Regression tests for V10.6 requirements

**Files:**
- Create: `tests/static/ui-v106.test.mjs`
- Create: `tests/static/cash-v106.test.mjs`
- Create: `tests/static/email-v106.test.mjs`

**Interfaces:**
- Consumes: current Blade/config/controller source files.
- Produces: static regression guards used by final verification.

- [x] **Step 1: Write failing UI test**

```js
import fs from 'node:fs';
import assert from 'node:assert/strict';
const admin = fs.readFileSync('resources/views/layouts/admin.blade.php','utf8');
const create = fs.readFileSync('resources/views/carts/create.blade.php','utf8');
assert.match(admin, /<svg/);
assert.doesNotMatch(admin, /Operations Center|Priority Queue|SLA Watch|Salltak Backoffice/);
assert.match(create, /استنا شوية/);
assert.match(create, /cart-import-overlay/);
```

- [x] **Step 2: Run it and confirm RED**

Run: `node tests/static/ui-v106.test.mjs`
Expected: FAIL because SVG/navigation Arabic/loading overlay are missing.

- [x] **Step 3: Write failing cash/email guards**

```js
const config = fs.readFileSync('config/libya_payment_methods.php','utf8');
const settings = fs.readFileSync('app/Http/Controllers/Admin/SettingController.php','utf8');
assert.match(config, /cash_on_delivery/);
assert.match(config, /allow_deposit/);
assert.match(settings, /notification_emails/);
```

- [x] **Step 4: Run guards and confirm RED**

Run: `node tests/static/cash-v106.test.mjs && node tests/static/email-v106.test.mjs`
Expected: FAIL because the new behavior does not exist.

### Task 2: Cash/deposit payment behavior

**Files:**
- Modify: `config/libya_payment_methods.php`
- Modify: `app/Models/PaymentMethod.php`
- Modify: `app/Http/Controllers/PaymentController.php`
- Modify: `resources/views/admin/payment-methods/index.blade.php`
- Modify: `resources/views/orders/show.blade.php`
- Test: `tests/static/cash-v106.test.mjs`

**Interfaces:**
- Produces: `PaymentMethod::allowsDeposit(): bool` and distinct `cash_on_delivery` catalog entry.
- `canOfferForOrder()` respects online/deposit/delivery availability.

- [x] **Step 1: Add cash configuration fields**

Add schema fields `allow_deposit`, `allow_balance`, `availability`, and customer instructions for the `cash` schema. Add `cash_on_delivery` with `availability=delivery_only`, no fake API credentials, and inactive-by-default catalog installation behavior.

- [x] **Step 2: Implement deposit-capability helper**

```php
public function allowsDeposit(): bool
{
    return filter_var(($this->config ?? [])['allow_deposit'] ?? false, FILTER_VALIDATE_BOOL);
}
```

Update order availability so `cash` may be offered in `awaiting_deposit` only when `allowsDeposit()` is true, while `cash_on_delivery` is restricted to delivery-stage statuses.

- [x] **Step 3: Keep cash payments verifiable**

`PaymentController::store()` records cash submissions as `pending_verification`; it never auto-verifies cash. Existing admin verification remains the source of truth.

- [x] **Step 4: Run cash regression guard**

Run: `node tests/static/cash-v106.test.mjs`
Expected: PASS.

### Task 3: Multi-recipient email alert service

**Files:**
- Create: `app/Services/OperationalEmailNotifier.php`
- Create: `resources/views/emails/operational-alert.blade.php`
- Modify: `app/Http/Controllers/Admin/SettingController.php`
- Modify: `resources/views/admin/settings/edit.blade.php`
- Modify: `app/Http/Controllers/OrderController.php`
- Modify: `app/Http/Controllers/PaymentController.php`
- Modify: `.env.example`
- Test: `tests/static/email-v106.test.mjs`

**Interfaces:**
- Produces: `OperationalEmailNotifier::send(string $event, string $subject, string $body, ?string $url = null): void`.
- Reads normalized recipients from `SystemSetting::notification_emails`.

- [x] **Step 1: Add setting validation**

Parse comma/newline-separated addresses, validate each with `filter_var(..., FILTER_VALIDATE_EMAIL)`, deduplicate, and store comma-separated normalized values. Store boolean toggles as `1`/`0`.

- [x] **Step 2: Implement safe mail dispatcher**

Use `Mail::html()` to send one Arabic HTML email per configured recipient. Catch `Throwable`, log the failure, and never throw into the customer request.

- [x] **Step 3: Trigger event emails**

Call notifier after successful new-order creation, customer message creation, and payment submission. Use event keys `new_order`, `new_message`, `payment` to honor toggles.

- [x] **Step 4: Add admin settings UI**

Add a multi-line recipient field and three switches with Arabic labels. Explain that local `MAIL_MAILER=log` writes email content to logs until SMTP is configured.

- [x] **Step 5: Run email regression guard**

Run: `node tests/static/email-v106.test.mjs`
Expected: PASS.

### Task 4: Arabic SVG dashboard and customer UX

**Files:**
- Create: `resources/views/components/icon.blade.php`
- Modify: `resources/views/layouts/admin.blade.php`
- Modify: `resources/views/admin/dashboard.blade.php`
- Modify: `resources/views/admin/orders/index.blade.php`
- Modify: `resources/views/admin/orders/show.blade.php`
- Modify: `resources/views/orders/index.blade.php`
- Modify: `resources/views/orders/show.blade.php`
- Modify: `resources/views/carts/create.blade.php`
- Modify: `public/css/app.css`
- Modify: `public/js/app-ui.js`
- Test: `tests/static/ui-v106.test.mjs`

**Interfaces:**
- Produces reusable `<x-icon name="..." />` SVG component.
- Produces global `cart-import-overlay` loading state.

- [x] **Step 1: Create SVG icon component**

Support at least: home, orders, carts, payment, percent, users, globe, exchange, settings, bell, menu, logout, clock, activity, wallet, check, warning, message, cash.

- [x] **Step 2: Replace navigation glyphs and English labels**

Replace `⌂ ✓ ▣ % ◎ ◇ $ ⚙ ☰` and dashboard English kickers with SVG and Arabic equivalents.

- [x] **Step 3: Add import waiting overlay**

Submit handler shows a modal overlay with spinner/progress dots and rotates these messages every ~2.4 seconds: `استنا شوية... جاري جلب السلة`, `جاري قراءة المنتجات والأسعار`, `نتحقق من الصور والمقاسات والألوان`, `قربنا نكمل... يتم تجهيز السلة للعرض`.

- [x] **Step 4: Polish customer/admin cards**

Add CSS for modern summary cards, action toolbar, order timeline, compact admin rows, responsive sticky summary, SVG sizing and mobile spacing. Do not add editable price inputs to customer pages.

- [x] **Step 5: Run UI guard**

Run: `node tests/static/ui-v106.test.mjs`
Expected: PASS.

### Task 5: Pagination without changing totals

**Files:**
- Modify: `app/Http/Controllers/CartController.php`
- Modify: `app/Http/Controllers/OrderController.php`
- Modify: `app/Http/Controllers/Admin/OrderController.php`
- Modify: `app/Http/Controllers/Admin/CartController.php`
- Modify: `resources/views/carts/show.blade.php`
- Modify: `resources/views/orders/show.blade.php`
- Modify: `resources/views/admin/orders/index.blade.php`
- Modify: `resources/views/admin/carts/index.blade.php`

**Interfaces:**
- Page collections are presentation-only; `Order.total_lyd`, `Cart.total_lyd` and payment calculations remain based on all persisted items.

- [x] **Step 1: Paginate admin/customer indexes**

Use Eloquent `paginate(15)->withQueryString()` for order/cart list pages.

- [x] **Step 2: Paginate item presentation**

For customer cart/order detail, query related items separately with `paginate(12, ['*'], 'items_page')->withQueryString()` and pass `$itemsPage` to Blade. Do not replace the relationship when recalculating totals.

- [x] **Step 3: Add Bootstrap pagination links**

Render `{{ $itemsPage->links() }}` / index paginator links beneath lists and retain filters/query string.

### Task 6: Final verification and packaging

**Files:**
- Create: `SALLTAK_V10_6_NOTES.md`
- Create: `TEST_REPORT_V10_6.md`
- Update: `README.md`

- [x] **Step 1: Run all V10.6 static guards**

Run: `node tests/static/ui-v106.test.mjs && node tests/static/cash-v106.test.mjs && node tests/static/email-v106.test.mjs`.

- [x] **Step 2: Run existing Node/static regressions**

Run all `tests/static/*.test.mjs`, `tests/Node/*.test.mjs`, MySQL architecture check, payment catalog checks and existing smoke scripts that do not require vendor.

- [x] **Step 3: PHP syntax check**

Run `find app config database routes tests -name '*.php' -print0 | xargs -0 -n1 php -l` and require zero syntax failures.

- [x] **Step 4: Package full artifact**

Create `/mnt/data/Salltak-v10.6-full.zip` containing the complete project, excluding runtime caches and local database files.
