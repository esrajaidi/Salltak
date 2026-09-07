# Libya Payment Catalog and Cart Reveal Fix Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Prevent imported cart content from disappearing and add a complete, admin-controlled Libya payment-method catalog that only exposes enabled/eligible methods to customers.

**Architecture:** Keep the existing PaymentMethod/Payment workflow and add a centralized Libya provider catalog plus a sync service used by seeding and an admin refresh action. Preserve manual-verification behavior unless an actual provider adapter exists; store provider credentials encrypted in `payment_methods.config` and expose only safe customer details. Fix reveal animation defensively for very tall cart cards.

**Tech Stack:** Laravel, Blade, MySQL, Bootstrap 5, vanilla JavaScript, Node static regression checks.

**Spec:** Approved in conversation: admin can enable/disable/configure methods; customers see enabled methods and choose one; deposit/partial payment workflow remains intact.

## Global Constraints

- MySQL only; do not reintroduce SQLite.
- Preserve SHEIN USD/color/size behavior and V10 order/deposit workflow.
- Do not pretend provider API integrations are live without official API contracts/credentials.
- Sensitive config remains encrypted by the existing PaymentMethod cast.

---

### Task 1: Cart reveal regression
**Files:** `public/js/app-ui.js`, `resources/views/carts/preview.blade.php`, `resources/views/carts/show.blade.php`, `tests/static/reveal-guard.test.mjs`
- [ ] Run the failing regression check.
- [ ] Lower the reveal threshold and make primary cart cards visible immediately.
- [ ] Re-run the regression check.

### Task 2: Central Libya payment catalog
**Files:** `config/libya_payment_methods.php`, `app/Services/LibyaPaymentMethodCatalog.php`, `database/seeders/LibyaPaymentMethodsSeeder.php`, `database/seeders/DatabaseSeeder.php`, `tests/static/payment-catalog.test.mjs`
- [ ] Run the failing catalog check.
- [ ] Define the catalog from the Central Bank of Libya licensed-provider directory plus LYPay/OnePay and generic bank/cash methods.
- [ ] Add idempotent sync preserving existing activation/merchant settings.
- [ ] Re-run catalog checks.

### Task 3: Admin provider configuration
**Files:** `app/Http/Controllers/Admin/PaymentMethodController.php`, `resources/views/admin/payment-methods/index.blade.php`, `routes/web.php`
- [ ] Add catalog refresh action.
- [ ] Add complete generic provider fields (integration mode, merchant/terminal/API/callback/account/wallet/QR fields) without exposing secrets to customers.
- [ ] Keep toggle behavior and fee/limit controls.

### Task 4: Customer method selection
**Files:** `app/Models/PaymentMethod.php`, `app/Http/Controllers/OrderController.php`, `app/Http/Controllers/PaymentController.php`, `resources/views/orders/show.blade.php`
- [ ] Filter enabled methods by amount eligibility for the current due amount.
- [ ] Render responsive radio-card choices with fees, instructions and safe account details.
- [ ] Require proof according to method config and keep server-side revalidation.
- [ ] Never execute fake API payment flows; API-mode methods remain pending gateway only when explicitly configured.

### Task 5: Laravel regression tests and packaging
**Files:** `tests/Feature/OrderPaymentWorkflowTest.php`, `tests/Feature/PaymentMethodCatalogTest.php`, docs/reports.
- [ ] Add tests for catalog sync, enable/disable visibility, amount limits and manual proof requirements.
- [ ] Run Node static checks, Node SHEIN checks and PHP lint.
- [ ] Package a full V10.2 ZIP.
