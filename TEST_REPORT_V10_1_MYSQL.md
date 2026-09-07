# Salltak V10.1 MySQL Verification Report

Date: 2026-09-07

## MySQL-only regression

- MySQL architecture/config check: **15/15 PASS**
- Runtime `.env`: `DB_CONNECTION=mysql`, database `salltak`
- Example env: MySQL by default
- Laravel database config: MySQL is the only configured relational connection
- Queue failed-job database fallback: MySQL
- PHPUnit: MySQL + isolated database `salltak_test`
- `database/database.sqlite`: removed from package
- Windows/Linux setup scripts: no file-database creation
- MySQL bootstrap: `database/mysql_setup.sql`

## Existing V10 regression checks

- Deposit calculator: **5/5 PASS**
- Order/payment workflow structural checks: **23/23 PASS**
- Core/SHEIN PHP smoke checks: **PASS**
- V9 UI identity check: **PASS**
- V4 UI regression checks: **14/14 PASS**
- Responsive UI checks: **10/10 PASS**
- Node SHEIN tests: **7/7 PASS**
- PHP unit-service smoke (`MoneyCalculator`, `StoreUrlClassifier`): **PASS**
- PHP syntax scan: **82 files / 0 syntax failures**
- Node worker syntax: **PASS**

## PHPUnit / Laravel Unit + Feature suite

The project contains real PHPUnit tests under `tests/Unit` and Laravel feature tests under `tests/Feature`.

The packaging container does not contain `vendor/` and Composer is unavailable, so `php artisan test --testsuite=Unit` stops before Laravel boots at `vendor/autoload.php`. This is an environment/dependency limitation, not a test assertion failure.

On the target Laragon machine, after `composer install` and creating the test database, run:

```bat
php artisan test --testsuite=Unit
php artisan test
```

`phpunit.xml` points DB-backed tests at `salltak_test`, not the runtime `salltak` database.
