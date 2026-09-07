# Salltak Marketing CMS Design

## Goal
Turn the public Salltak homepage into a premium, image-rich, animated marketing site whose content can be edited and published from the admin panel without code changes.

## Architecture
- Add `site_sections` as the single source of truth for public marketing content.
- Each section stores published content and draft content separately, plus published/draft visibility and ordering.
- Public visitors only read published content.
- Admin edits drafts, uploads images, controls visibility/order, previews the draft, then publishes one section or all sections.
- Keep stores, payment methods, cart importing, prices, orders, payments, notifications and customer logic unchanged.

## Sections
1. Hero
2. How it works
3. Supported stores
4. Showcase / visual proof
5. Benefits / features
6. Available payment methods
7. Testimonials
8. FAQs
9. Final CTA
10. Footer
11. SEO metadata

## Admin UX
- New navigation item: `إدارة الموقع الخارجي`.
- Overview cards show section visibility, draft status, order and publish status.
- Edit screen uses normal Arabic form fields, repeatable rows for items, image upload + current preview, show/hide and order controls.
- `حفظ كمسودة`, `نشر هذا القسم`, and `نشر كل التعديلات` actions.
- SweetAlert remains the global success/error UX.

## Public UX
- RTL, responsive, image-rich hero and showcase.
- Existing navy/teal/sky/gold identity, no purple.
- Light scroll/hover animation only.
- Stores come from active `stores` records.
- Payment methods show only active and configured methods.
- Homepage CTA respects login role and existing routes.

## Image Handling
- Images uploaded to `public` disk under `site-content/`.
- Draft keeps current image when no replacement is uploaded.
- No destructive cleanup of old files in this phase.

## SEO
- Dynamic title, meta description, Open Graph title/description/image.
- Defaults remain safe if SEO content has not been published.

## Safety / Compatibility
- MySQL only.
- Existing databases receive a non-destructive migration.
- No `migrate:fresh` required.
- No changes to customer product pricing or SHEIN extraction logic.
