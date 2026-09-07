# Salltak V10.8 — Responsive UI & Motion

## What changed
- Removed the phantom 74px offset above admin pages. Admin routes now start at the top of the viewport.
- Loaded Cairo for headings and IBM Plex Sans Arabic for body/interface text with safe Arabic fallbacks.
- Added responsive admin table cards on phones; column headings become inline labels automatically.
- Improved topbar, sidebar, dashboard cards, filters, pagination, CMS, and command-center responsiveness.
- Added a mobile logout action inside the admin offcanvas sidebar.
- Improved marketing-page entrance, floating device, hover, and section motion.
- Added `prefers-reduced-motion` handling so animations respect accessibility settings.
- Existing order, price, payment, notification, SHEIN, and CMS business logic is unchanged.

## Upgrade
Run:

```bash
php artisan optimize:clear
```

No new database migration is required by V10.8.
