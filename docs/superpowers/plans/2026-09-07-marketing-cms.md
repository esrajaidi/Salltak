# Salltak Marketing CMS Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a dynamic, publishable public-site CMS for Salltak while preserving all commerce/order behavior.

**Architecture:** Store each homepage section as published and draft JSON in `site_sections`. Admin edits draft content and publishes it; `HomeController` renders only published visible sections through focused Blade partials. Image uploads use Laravel's public disk.

**Tech Stack:** Laravel 13, PHP 8.3+, MySQL, Blade, Bootstrap 5 RTL, SweetAlert2, existing SVG icon component.

**Spec:** `docs/superpowers/specs/2026-09-07-marketing-cms-design.md`

## Global Constraints
- Preserve existing SHEIN USD/color/size behavior.
- Customer cannot modify prices.
- Preserve MySQL-only setup.
- Preserve current order/payment/deposit/notification workflows.
- Arabic RTL public/admin UI.
- Responsive on desktop/tablet/mobile.

---

### Task 1: CMS data model and default content
**Files:**
- Create: `database/migrations/2026_09_07_224500_create_site_sections_table.php`
- Create: `app/Models/SiteSection.php`
- Create: `app/Support/SiteContentDefaults.php`
- Create: `database/seeders/SiteContentSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php`
- Test: `tests/Feature/SiteContentCmsTest.php`

- [ ] Write tests that prove published and draft content are separate and seeded defaults exist.
- [ ] Run the tests/check and verify failure before implementation.
- [ ] Add model, migration, defaults and seeder.
- [ ] Re-run checks.

### Task 2: Admin CMS workflow
**Files:**
- Create: `app/Http/Controllers/Admin/SiteContentController.php`
- Create: `resources/views/admin/site-content/index.blade.php`
- Create: `resources/views/admin/site-content/edit.blade.php`
- Modify: `routes/web.php`
- Modify: `resources/views/layouts/admin.blade.php`
- Modify: `resources/views/components/icon.blade.php`

- [ ] Add route/access regression test/check.
- [ ] Implement draft update, image upload, single publish, publish all.
- [ ] Add admin cards/forms and SVG navigation icon.
- [ ] Verify validation and route wiring.

### Task 3: Dynamic public homepage
**Files:**
- Modify: `app/Http/Controllers/HomeController.php`
- Replace: `resources/views/home.blade.php`
- Create: `resources/views/site/sections/*.blade.php`
- Modify: `app/Providers/AppServiceProvider.php`
- Modify: `resources/views/layouts/app.blade.php`
- Modify: `public/css/app.css`

- [ ] Add public rendering regression test/check.
- [ ] Load published sections, active stores, configured payment methods.
- [ ] Render sections by slug and order.
- [ ] Add dynamic SEO/footer and responsive visual CSS.
- [ ] Verify hidden/draft content never appears publicly.

### Task 4: Final verification and release artifact
**Files:**
- Create: `scripts/site_cms_v10_7_check.php`
- Create: `SALLTAK_V10_7_NOTES.md`
- Create: `TEST_REPORT_V10_7.md`

- [ ] Run V10.7 structural checks.
- [ ] Run existing MySQL/order/payment/UI/SHEIN checks.
- [ ] Run PHP lint on all PHP files.
- [ ] Package `Salltak-v10.7-full.zip` and verify ZIP integrity.
