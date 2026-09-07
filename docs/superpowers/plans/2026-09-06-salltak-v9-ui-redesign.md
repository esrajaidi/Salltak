# Salltak V9 UI Redesign Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Deliver a complete non-purple responsive V9 redesign of Salltak's public site, customer pages, authentication, and admin interface while preserving all existing functionality.

**Architecture:** Keep Blade routes/forms/controllers unchanged and implement the redesign through a new shared CSS design system plus a tiny no-dependency interaction script. Update layout and page markup only where semantic sections/classes are needed; all business data continues to come from existing view variables.

**Tech Stack:** Laravel Blade, Bootstrap 5 RTL, plain CSS, plain JavaScript, existing Node/Playwright SHEIN importer.

**Spec:** `docs/superpowers/specs/2026-09-06-salltak-v9-ui-redesign-design.md`

## Global Constraints

- Arabic RTL is the default direction.
- Preserve all existing routes, form fields, validation, CSRF behavior, and cart-import logic.
- Preserve SHEIN V8 USD pricing and V7 color/size extraction unchanged.
- Use navy `#0F2744`, deep navy `#091827`, teal `#14B8A6`, teal dark `#0F8F83`, sky `#38BDF8`, gold `#F4B942`, background `#F4F8FB`, surface `#FFFFFF`, ink `#122033`, muted `#66788A`.
- Do not use purple as a primary, secondary, gradient, chart, focus, button, active-navigation, or decorative color.
- Do not add frontend build dependencies or remote JS.
- Respect `prefers-reduced-motion`.

---

### Task 1: V9 regression/static checks

**Files:**
- Create: `scripts/ui_v9_check.php`
- Test: `scripts/ui_v9_check.php`

**Interfaces:**
- Consumes: Blade/CSS/JS files from the project tree.
- Produces: exit code 0 only when V9 identity, responsive hooks, landing sections, admin shell, and no legacy purple tokens are present.

- [ ] **Step 1: Write the failing V9 checker**

Create a PHP script that asserts: V9 CSS variables include navy/teal/sky/gold; CSS contains `.reveal`, reduced-motion handling, responsive admin sidebar; home contains `landing-hero`, `how-it-works`, `platform-preview`, and `landing-cta`; admin layout contains `admin-topbar` and `admin-sidebar`; app layout loads `/js/app-ui.js`; and legacy primary purple tokens `#6f3fb5`, `#5d2ea3`, `#4b228d`, `#24135f`, `#251451` are absent from public CSS and layouts.

- [ ] **Step 2: Run checker and verify RED**

Run: `php scripts/ui_v9_check.php`
Expected: non-zero exit with missing V9 palette/sections.

- [ ] **Step 3: Keep checker unchanged for remaining tasks**

No production code in this task.

### Task 2: Shared V9 design system and interaction script

**Files:**
- Modify: `public/css/app.css`
- Create: `public/js/app-ui.js`
- Modify: `resources/views/layouts/app.blade.php`

**Interfaces:**
- Consumes: existing Bootstrap classes and current Blade markup.
- Produces: shared V9 tokens/classes and reveal behavior used by all pages.

- [ ] **Step 1: Implement the V9 palette and shared components**

Replace legacy purple variables/styles with navy/teal/sky/gold tokens. Add reusable nav, cards, forms, buttons, badges, cart editor, table, footer, auth, admin shell, landing, responsive, and reduced-motion styles.

- [ ] **Step 2: Add no-dependency reveal script**

`public/js/app-ui.js` should add `js-enabled` to `<html>`, use IntersectionObserver for `.reveal`, and immediately reveal everything when IntersectionObserver is unavailable.

- [ ] **Step 3: Update app layout shell**

Use a premium light public navbar with the navy/teal identity, keep all auth/admin/customer links and logout form, add a richer footer, and load `app-ui.js` with `defer`.

- [ ] **Step 4: Run V9 checker**

Run: `php scripts/ui_v9_check.php`
Expected: some checks still fail only because landing/admin markup is not yet updated.

### Task 3: Public landing page and authentication

**Files:**
- Modify: `resources/views/home.blade.php`
- Modify: `resources/views/auth/login.blade.php`
- Modify: `resources/views/auth/register.blade.php`

**Interfaces:**
- Consumes: `$stores`, auth state, existing auth routes.
- Produces: public marketing surface and modern auth cards with no route changes.

- [ ] **Step 1: Build animated landing hero**

Add `landing-hero`, CTA buttons based on auth state, CSS device/dashboard preview, floating store chips, and trust indicators.

- [ ] **Step 2: Add workflow, platform preview, features, store support, and final CTA**

Use semantic sections `how-it-works`, `platform-preview`, `landing-features`, `supported-stores`, `landing-cta`; add `.reveal` hooks and no fabricated customer metrics.

- [ ] **Step 3: Refresh login/register markup**

Use a split auth presentation with benefit copy and the existing forms/fields/actions untouched.

- [ ] **Step 4: Run V9 checker**

Run: `php scripts/ui_v9_check.php`
Expected: only admin-shell/dashboard checks may remain.

### Task 4: Admin shell and dashboard

**Files:**
- Modify: `resources/views/layouts/admin.blade.php`
- Modify: `resources/views/admin/dashboard.blade.php`

**Interfaces:**
- Consumes: current auth user, `$stats`, `$latestCarts`, existing admin routes.
- Produces: fixed responsive admin navigation, topbar, KPI cards, current-state visuals, and latest-carts table.

- [ ] **Step 1: Replace admin shell markup**

Create a right-side RTL desktop sidebar with brand, navigation icons/labels, user/help block; mobile offcanvas; and `admin-topbar` with title/search-style field/user summary.

- [ ] **Step 2: Redesign dashboard using existing data only**

Render KPI cards from `$stats`, CSS mini-bars/rings as decorative representations of current totals, quick actions, platform health/store coverage summary, and latest carts table from `$latestCarts`. Do not create fake historical series.

- [ ] **Step 3: Run V9 checker**

Run: `php scripts/ui_v9_check.php`
Expected: PASS.

### Task 5: Customer cart pages and admin management consistency

**Files:**
- Modify: `resources/views/carts/create.blade.php`
- Modify: `resources/views/carts/index.blade.php`
- Modify: `resources/views/carts/preview.blade.php`
- Modify: `resources/views/carts/show.blade.php`
- Modify: `resources/views/admin/carts/index.blade.php`
- Modify: `resources/views/admin/carts/show.blade.php`
- Modify: `resources/views/admin/users/index.blade.php`
- Modify: `resources/views/admin/stores/index.blade.php`
- Modify: `resources/views/admin/exchange-rates/index.blade.php`
- Modify: `resources/views/admin/settings/edit.blade.php`

**Interfaces:**
- Consumes: existing variables/actions/forms.
- Produces: consistent V9 page headers, cards, toolbars, tables, and responsive forms without changing business behavior.

- [ ] **Step 1: Add V9 page composition classes to customer cart pages**

Preserve every input name, quantity control hook, product field, route, and submit action. Only change wrappers, labels, hierarchy, and visual classes.

- [ ] **Step 2: Harmonize admin management page headers/cards**

Add shared admin page-header, panel, responsive form/table classes while preserving controller expectations.

- [ ] **Step 3: Run existing UI/static checks**

Run: `php scripts/ui_v9_check.php && php scripts/ui_v4_check.php && php scripts/ui_smoke_test.php`
Expected: V9 PASS; legacy functional UI checks PASS unless they assert obsolete colors/markup, in which case update only brittle style assertions while preserving behavioral assertions.

### Task 6: Full verification and packaging

**Files:**
- Create: `SALLTAK_V9_NOTES.md`
- Update: `README.md` only to mention V9 visual identity and unchanged V8 SHEIN behavior.

**Interfaces:**
- Produces: verified full-project ZIP artifact.

- [ ] **Step 1: Run PHP lint**

Run: `find app bootstrap config database routes tests scripts -name '*.php' -print0 | xargs -0 -n1 php -l`
Expected: zero syntax errors.

- [ ] **Step 2: Run Node checks**

Run: `node --check public/js/app-ui.js && node --check scripts/shein-browser-import.mjs && node --test tests/Node/*.test.mjs`
Expected: all pass.

- [ ] **Step 3: Run project smoke/UI checks**

Run: `php scripts/smoke_test.php && php scripts/ui_v9_check.php && php scripts/ui_smoke_test.php`
Expected: all pass.

- [ ] **Step 4: Run PHPUnit when vendor dependencies are available**

Run: `php artisan test`
Expected: 0 failures. If dependencies are unavailable in the artifact workspace, report that explicitly and do not claim PHPUnit passed.

- [ ] **Step 5: Package full project**

Rename the root directory to `Salltak-v9-full` and create `/mnt/data/Salltak-v9-full.zip`, excluding runtime caches, `.env`, `vendor`, and `node_modules` if they are absent from source artifact.
