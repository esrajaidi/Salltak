# Salltak MySQL-Only Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Convert Salltak V10 from SQLite-default operation/testing to MySQL-only operation with a separate `salltak_test` database.

**Architecture:** Runtime configuration will expose only the MySQL connection and default to `salltak`. PHPUnit will use a dedicated MySQL database named `salltak_test`. Setup scripts and documentation will stop creating or referring to SQLite, and a SQL bootstrap file will create the two databases safely.

**Tech Stack:** Laravel, PHP 8.3+, MySQL 8+/MariaDB, PHPUnit, Bootstrap 5, Node/Playwright.

**Spec:** Approved in chat on 2026-09-07: no SQLite; MySQL only; separate test database; preserve current SHEIN/ordering/payment features.

## Global Constraints

- Keep existing application behavior and V10 order/payment functionality unchanged.
- Runtime database is `salltak` by default.
- Automated Laravel tests use `salltak_test`, never the runtime database.
- Do not create or reference `database/database.sqlite`.
- Preserve the user's Laragon Node path in the packaged local `.env`.

---

### Task 1: Add a MySQL-only architecture regression check

**Files:**
- Create: `tests/Architecture/mysql_only_config_check.php`

**Interfaces:**
- Consumes: project configuration files.
- Produces: a dependency-free PHP check that exits non-zero when SQLite configuration returns.

- [x] **Step 1: Write the failing test**
- [x] **Step 2: Run it and verify it fails on current SQLite defaults**
- [x] **Step 3: Apply the minimal configuration changes**
- [x] **Step 4: Re-run and verify all architecture checks pass**

### Task 2: Switch runtime and test configuration to MySQL

**Files:**
- Modify: `.env.example`
- Create: `.env`
- Modify: `config/database.php`
- Modify: `config/queue.php`
- Modify: `phpunit.xml`
- Create: `database/mysql_setup.sql`
- Delete: `database/database.sqlite`

**Interfaces:**
- Runtime: `DB_CONNECTION=mysql`, `DB_DATABASE=salltak`.
- Tests: `DB_CONNECTION=mysql`, `DB_DATABASE=salltak_test`.

- [x] **Step 1: Change runtime defaults to MySQL**
- [x] **Step 2: Remove SQLite connection and artifact**
- [x] **Step 3: Point PHPUnit at isolated MySQL test DB**
- [x] **Step 4: Add database bootstrap SQL**

### Task 3: Update install scripts and documentation

**Files:**
- Modify: `setup.bat`
- Modify: `setup.sh`
- Modify: `README.md`
- Create: `SALLTAK_V10_1_MYSQL_NOTES.md`

**Interfaces:**
- Setup assumes `salltak` and `salltak_test` exist or are created using `database/mysql_setup.sql`.

- [x] **Step 1: Remove SQLite creation from scripts**
- [x] **Step 2: Document MySQL database creation and Laragon setup**
- [x] **Step 3: Document test database isolation**

### Task 4: Verify and package

**Files:**
- Package: `Salltak-v10.1-mysql-full.zip`

**Interfaces:**
- Produces a full project ZIP with MySQL-only defaults.

- [x] **Step 1: Run MySQL-only architecture check**
- [x] **Step 2: Run PHP syntax checks**
- [x] **Step 3: Run Node tests/smoke checks available without Composer**
- [x] **Step 4: Search project for remaining SQLite references**
- [x] **Step 5: Package and inspect ZIP contents**
