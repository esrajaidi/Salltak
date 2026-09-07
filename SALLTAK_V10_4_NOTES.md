# Salltak V10.4 — Long SHEIN Product Name Fix

## Problem fixed
MySQL strict mode raised `SQLSTATE[22001] Data too long for column name` when SHEIN returned Arabic product titles longer than 255 characters.

The application validation allowed names longer than the database `VARCHAR(255)` definition, and order creation copied the same title into `order_items`, where the same limit existed.

## Changes
- `cart_items.name` is now `TEXT` for fresh installations.
- `order_items.name` is now `TEXT` for fresh installations.
- Added migration `2026_09_07_121500_expand_product_name_columns.php` so existing MySQL databases are upgraded with `php artisan migrate`.
- Increased cart item name validation from 500 to 2000 characters so legitimate long catalog titles can be stored without truncation.
- Added a Laravel Feature regression test covering saving a long SHEIN item name and copying it into an order.
- Added standalone schema regression check `scripts/cart_item_long_name_schema_check.php`.

## Upgrade existing installation
Do not run `migrate:fresh` just for this fix. Run:

```bash
php artisan optimize:clear
php artisan migrate
```

Then retry importing and saving the same SHEIN cart.
