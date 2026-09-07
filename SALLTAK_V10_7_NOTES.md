# Salltak V10.7 — Dynamic Marketing Website CMS

## What changed
V10.7 turns the public Salltak homepage into a dynamic marketing site controlled from the admin panel without editing Blade code.

### New admin area
**لوحة التحكم → إدارة الموقع الخارجي**

The admin can manage:
- Hero heading, copy, CTA buttons, trust badges, desktop/mobile images.
- How-it-works steps.
- Supported-stores section copy (store list itself remains driven by active Stores).
- Product/order showcase copy, bullets and responsive images.
- Features/benefits cards and SVG icons.
- Payment-method marketing section (customer-facing list is driven by active + configured payment methods).
- Testimonials.
- FAQs.
- Final CTA.
- Footer/contact details.
- SEO title/description/Open Graph image.

### Draft / publish workflow
Each section has separate **draft** and **published** content:
- `حفظ كمسودة` does not change the public website.
- `معاينة المسودة` renders the draft website for the admin.
- `نشر القسم` publishes one section.
- `نشر كل التعديلات` publishes all draft sections.
- Visibility and ordering are also drafted, then applied on publish.

### Images
Images are uploaded to Laravel's `public` disk under `site-content/`.
Run `php artisan storage:link` so uploaded images are publicly accessible.

### Public design
- Premium navy/teal/sky/gold visual identity.
- Responsive laptop/phone product visualization when no image has been uploaded.
- Uploaded responsive Hero and Showcase images when available.
- Scroll/hover motion uses the existing reveal system and reduced-motion safety.
- Dynamic stores and dynamic configured payment methods.
- Dynamic footer and SEO metadata.

## Existing systems preserved
No changes were made to:
- SHEIN extraction logic.
- USD price source and LYD conversion rules.
- Customer price lock.
- Order review/status/payment/deposit logic.
- LYPay/OnePay/payment-provider configuration.
- Notifications, audit logs, cash/COD logic.
- MySQL-only configuration.

## Upgrade an existing V10.6 database
Do **not** use `migrate:fresh`.

```bash
php artisan optimize:clear
php artisan migrate
php artisan storage:link
```

The new migration creates `site_sections` and inserts safe starter content automatically.

Then open:

**لوحة التحكم → إدارة الموقع الخارجي**

Edit the draft, upload your own images, preview it, then publish.

## Laravel test suite
A new Feature test exists at:

`tests/Feature/SiteContentCmsTest.php`

Run it on the real project after dependencies are installed:

```bash
php artisan test
```
