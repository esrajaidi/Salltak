# Libya Payments V10.3 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement a truthful Libya payment catalog with provider-specific configuration, activation readiness, customer filtering, and compact admin UX.

**Architecture:** Keep encrypted `PaymentMethod::config` as the flexible merchant-configuration store and add schema metadata in `config/libya_payment_methods.php`. `PaymentMethod` owns readiness/customer-availability rules, controllers enforce them server-side, and the admin Blade renders provider-specific forms from the schema.

**Tech Stack:** Laravel/PHP 8+, MySQL, Blade, Bootstrap 5, vanilla JavaScript.

**Spec:** `docs/superpowers/specs/2026-09-07-libya-payments-v10-3-design.md`

## Global Constraints
- MySQL only; do not reintroduce SQLite.
- Preserve V10 order/deposit/payment workflow and V8 SHEIN USD/color/size behavior.
- Do not invent external-provider APIs or successful automatic payment behavior.
- Keep all payment credentials encrypted and never expose secrets to customers.
- Admin can activate/deactivate methods, but activation requires usable provider configuration.
- Customer sees only methods usable for the current order amount and stage.

---

### Task 1: Catalog and provider schemas
**Files:** `config/libya_payment_methods.php`, `tests/Architecture/libya_payment_v103_config_check.php`
**Interfaces:** Produces `methods`, `schemas`, documentation metadata, activation requirements, and customer-public field definitions.
- [ ] Write config regression test for 22 methods and LYPay/OnePay/provider schema requirements.
- [ ] Run test and verify RED against V10.2.
- [ ] Replace catalog with schema-driven truthful definitions.
- [ ] Run config test and verify GREEN.

### Task 2: Activation and availability domain rules
**Files:** `app/Models/PaymentMethod.php`, `app/Http/Controllers/Admin/PaymentMethodController.php`, `app/Http/Controllers/OrderController.php`, `app/Http/Controllers/PaymentController.php`, `tests/static/payment-flow-v103.test.mjs`
**Interfaces:** Produces `activationIssues()`, `isConfiguredForActivation()`, `canOfferForOrder(Order,float)`, dynamic customer details, and server-side activation/payment guards.
- [ ] Write failing source-level regression test for the new methods and guards.
- [ ] Run test and verify RED.
- [ ] Implement model/controller rules and dynamic config validation.
- [ ] Run test and verify GREEN.

### Task 3: Compact admin configuration UX
**Files:** `resources/views/admin/payment-methods/index.blade.php`, `public/css/app.css`, `tests/static/payment-admin-v103.test.mjs`
**Interfaces:** Responsive card grid, filters, readiness badges, modal configuration forms driven by schema.
- [ ] Write failing UI regression test.
- [ ] Run test and verify RED.
- [ ] Implement compact Bootstrap UI and CSS.
- [ ] Run UI test and verify GREEN.

### Task 4: Customer payment UX and documentation
**Files:** `resources/views/orders/show.blade.php`, `README.md`, `SALLTAK_V10_3_NOTES.md`
**Interfaces:** Shows public merchant/account/QR fields only, external checkout links where configured, and clear proof instructions.
- [ ] Extend payment-flow regression test for public-only/external-link rendering.
- [ ] Run test and verify RED.
- [ ] Implement customer UI and notes.
- [ ] Run test and verify GREEN.

### Task 5: Full regression and package
**Files:** final ZIP artifact.
**Interfaces:** `Salltak-v10.3-full.zip`.
- [ ] Run V10.3 checks.
- [ ] Run V10.2/MySQL/UI/responsive/SHEIN/reveal checks.
- [ ] PHP lint all project PHP files.
- [ ] Package ZIP and verify contents.
