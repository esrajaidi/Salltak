# Salltak V10.1 — MySQL Only

هذه النسخة مبنية على V10 وتحافظ على دورة الطلبات، العربون، الدفعات، إدارة طرق الدفع، SHEIN USD، اللون والمقاس.

## التغيير الرئيسي

- MySQL هو اتصال قاعدة البيانات الوحيد في `config/database.php`.
- `.env` و`.env.example` يستخدمان `DB_CONNECTION=mysql` وقاعدة `salltak`.
- تم حذف ملف قاعدة البيانات المحلي القديم من حزمة المشروع.
- `config/queue.php` لا يحتوي fallback لقاعدة أخرى.
- اختبارات Laravel تستخدم قاعدة مستقلة `salltak_test` عبر `phpunit.xml`.
- تمت إضافة `database/mysql_setup.sql` لإنشاء `salltak` و`salltak_test` بترميز `utf8mb4`.
- `setup.bat` و`setup.sh` لا ينشئان أي قاعدة ملفية.

## Laragon

أنشئ/استورد القاعدتين من phpMyAdmin باستخدام `database/mysql_setup.sql`، ثم:

```bat
composer install
npm install
npx playwright install chromium
php artisan optimize:clear
php artisan migrate --seed
php artisan test
```

إعداد Node المحلي في الحزمة مطابق للمسار الذي زوده المستخدم:

```env
SHEIN_BROWSER_NODE="D:/laragon/bin/nodejs/node-v22/node.exe"
```

إذا كان `where node` عندك يعرض مسارًا مختلفًا، عدّل السطر إلى المسار الحقيقي.
