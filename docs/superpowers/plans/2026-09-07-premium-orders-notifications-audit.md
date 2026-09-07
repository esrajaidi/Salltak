# Salltak Premium Orders, Notifications & Audit Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [x]`) syntax for tracking.

**Goal:** Deliver a premium, monitored order workflow with persistent status notes, internal/customer visibility, in-app notifications, audit logs, SweetAlert2 confirmations, and improved responsive customer/backoffice UI.

**Architecture:** Extend existing order history instead of creating a competing timeline model. Add small notification and audit services used by controllers/workflow, while keeping existing payment/deposit/SHEIN services intact. Render notifications through shared layout data and expose simple auth routes for reading them.

**Tech Stack:** Laravel 13, PHP 8.3+, MySQL, Blade, Bootstrap 5 RTL, SweetAlert2, vanilla JS.

**Spec:** `docs/superpowers/specs/2026-09-07-premium-orders-notifications-audit-design.md`

## Global Constraints
- MySQL only; no SQLite runtime/test configuration changes.
- Preserve SHEIN Playwright import and USD source pricing.
- Customer must not be able to edit USD/LYD unit price values.
- Keep current Libyan payment provider catalog and deposit logic.
- Responsive RTL and no purple brand colors.

---

### Task 1: Role-Safe Authorization Regression
**Files:** modify middleware/auth/layouts; create `tests/static/role-safety-v105.test.mjs`.
**Produces:** direct role-based checks with no required custom method calls in runtime views/middleware.
- [x] Write static failing checks that reject `->isBackoffice()`/`->isAdmin()` in runtime Blade/middleware/auth redirect files.
- [x] Run `node tests/static/role-safety-v105.test.mjs` and verify failure.
- [x] Replace runtime checks with direct role comparisons while leaving User helper methods intact.
- [x] Re-run check and verify pass.

### Task 2: Durable Activity, Notifications and Audit Schema
**Files:** create migration `2026_09_07_133000_add_activity_notifications_and_audit.php`; create models `AppNotification`, `AuditLog`; extend `OrderStatusHistory`, `User`.
**Produces:** persistent visibility-aware timeline, notification inbox and audit log.
- [x] Write static schema test for required tables/columns/indexes.
- [x] Verify it fails.
- [x] Add migration/models/relations/casts.
- [x] Verify schema test passes and PHP syntax is clean.

### Task 3: Activity Services and Workflow Integration
**Files:** create `NotificationService`, `AuditLogger`; modify `OrderWorkflowService`.
**Produces:** `transition(..., visibility, eventType, metadata)` and `addNote(...)` plus safe event fan-out.
- [x] Write static/service-shape tests for transition persistence and notification/audit calls.
- [x] Verify failure.
- [x] Implement services and workflow integration.
- [x] Verify tests pass.

### Task 4: Controller Event Coverage
**Files:** modify customer/admin order/payment controllers plus key admin management controllers; add notification controller/routes.
**Produces:** notes, notifications and audit events for submitted orders, assignment, item review, replies/messages, payment terms, payment submission/verification and status transitions.
- [x] Write failing route/controller static checks.
- [x] Implement endpoints `orders.notes.store`, `notifications.index/read/read-all` and event calls.
- [x] Verify checks pass.

### Task 5: Shared SweetAlert2 and Notification Bell
**Files:** modify `layouts/app`, `layouts/admin`, `AppServiceProvider`; create `partials/notification-bell`, `notifications/index`, `public/js/app-ui.js`.
**Produces:** session toasts/errors, confirm dialogs, notification dropdown and inbox.
- [x] Write failing UI static checks.
- [x] Add SweetAlert2 CDN integration with native fallback and data-confirm interception.
- [x] Add notification data composer, bell and routes/UI.
- [x] Verify checks pass.

### Task 6: Premium Customer Order Details
**Files:** rewrite `resources/views/orders/show.blade.php`; extend CSS.
**Produces:** progress tracker, read-only USD/LYD product pricing, premium cards, visible activity timeline, payment summary and conversation.
- [x] Write failing static checks for immutable price display and timeline filtering.
- [x] Implement responsive layout.
- [x] Verify UI checks pass.

### Task 7: Premium Backoffice Order Workspace and Dashboard Monitoring
**Files:** rewrite `admin/orders/show`, improve `admin/dashboard`, modify dashboard controller, extend CSS.
**Produces:** operations workspace, status-note visibility controls, internal note form, payment/actions, monitoring queues, recent activity/audit.
- [x] Write failing static checks for status note/visibility and monitoring widgets.
- [x] Implement views/controller queries.
- [x] Verify checks pass.

### Task 8: Final Regression and Package
**Files:** update README and create `SALLTAK_V10_5_NOTES.md`, `TEST_REPORT_V10_5.md`.
**Produces:** full V10.5 package.
- [x] Run all V10.5 static checks.
- [x] Run existing V10.4/V10.3/MySQL/UI/Node checks.
- [x] Run `php -l` over all PHP files.
- [x] Record inability to run `php artisan test` if vendor/composer unavailable; do not claim it ran.
- [x] Zip exact verified tree as `Salltak-v10.5-full.zip` and verify archive integrity.
