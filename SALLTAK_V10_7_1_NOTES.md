# Salltak V10.7.1

Packaging hotfix for Laravel runtime cache paths.

Added persistent runtime directories:
- storage/framework/views
- storage/framework/cache/data
- storage/framework/sessions
- storage/framework/testing
- storage/logs
- storage/app/private
- storage/app/public
- bootstrap/cache

Each directory contains a .gitignore placeholder so ZIP/Git packaging preserves it.

After replacing files, run:

```bash
php artisan optimize:clear
```
