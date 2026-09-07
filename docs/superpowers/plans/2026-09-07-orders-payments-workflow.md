# Salltak Orders & Payments Workflow Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a complete order review, deposit, partial-payment, payment-method configuration, staff assignment, customer reply, and delivery workflow to Salltak V9.

**Architecture:** Preserve carts as import/saved-cart records and create immutable order snapshots when a customer submits a cart. Use dedicated models/services for deposit calculation and order payment totals, plus role-separated customer/backoffice/admin controllers and views.

**Tech Stack:** Laravel 12, PHP 8.3+, MySQL/SQLite, Bootstrap 5 RTL, Blade, existing V9 CSS/JS.

**Spec:** `docs/superpowers/specs/2026-09-07-orders-payments-workflow-design.md`

## Global Constraints
- Preserve existing SHEIN V8 USD extraction, color, size, SKU, quantities, and LYD conversion.
- Preserve V9 navy/teal/sky/gold design; do not reintroduce purple.
- Full RTL and responsive UI.
- Payment provider secrets stored encrypted via model casts.
- No fake provider API success; manual verification is the working fallback until official provider API credentials/contracts are supplied.
- Delivered status requires zero remaining balance.

---

### Task 1: Domain schema and models
**Files:** create migrations/models for orders, order items, messages, history, payment methods, payments, deposit rules; modify User and Cart relations.
- [ ] Write structural checks for required files/columns/relations.
- [ ] Run checks and confirm failure before implementation.
- [ ] Add migrations/models/relations/casts.
- [ ] Run structural checks and PHP lint.

### Task 2: Deposit and payment calculations
**Files:** create `app/Services/DepositCalculator.php` and order payment helpers.
- [ ] Write calculator tests for percentage/fixed bands, manual override and remaining balance.
- [ ] Run and confirm failure.
- [ ] Implement minimal calculator and order helper methods.
- [ ] Run tests and lint.

### Task 3: Customer order lifecycle
**Files:** create customer OrderController/PaymentController and routes; modify cart detail UI; create order list/detail views.
- [ ] Add route/controller/view checks first.
- [ ] Implement cart→order snapshot, messages/item replies, payment submission/receipt upload.
- [ ] Add responsive customer order UI.
- [ ] Run structural/UI checks and lint.

### Task 4: Backoffice order review
**Files:** create BackofficeMiddleware, admin OrderController, admin order views; modify bootstrap aliases/admin layout/dashboard.
- [ ] Add checks for assignment, item review, approval/payment terms, rejection reason, transitions and payment verification.
- [ ] Implement role access and workflow actions with history logging.
- [ ] Add responsive order review pages and dashboard links.
- [ ] Run checks and lint.

### Task 5: Payment method and deposit rule administration
**Files:** create admin PaymentMethodController/DepositRuleController and views/routes; modify admin nav.
- [ ] Add configuration checks first.
- [ ] Implement CRUD/toggle for methods and rules, encrypted config, min/max/fees/sort/instructions.
- [ ] Add admin UI.
- [ ] Run checks and lint.

### Task 6: Seed/demo/docs/final verification
**Files:** modify DatabaseSeeder/README, add V10 notes and verification script.
- [ ] Seed admin, order manager, configurable payment methods, deposit bands, and demo order data where safe.
- [ ] Document setup/migrate/storage-link and gateway integration limitation.
- [ ] Run old smoke checks, new V10 checks, Node tests, all PHP lint, and package ZIP.
