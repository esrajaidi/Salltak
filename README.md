# سلات ليبيا — Laravel 13

منصة عربية RTL لحفظ سلات التسوق، تحليل الروابط المتاحة، تحويل الإجمالي إلى الدينار الليبي، وإدارة السلات والمستخدمين والمواقع وأسعار الصرف.

## الوظائف
- تسجيل وإنشاء حسابات العملاء.
- رابط سلة/منتج مع كشف الموقع.
- Adapter خاص بروابط SHEIN + Generic adapter للمنتجات التي تعرض JSON-LD/OpenGraph.
- fallback يدوي عندما الموقع يخفي عناصر السلة.
- مراجعة المنتجات والأسعار والكميات قبل الحفظ.
- تثبيت سعر الصرف داخل السلة وقت الحفظ.
- صفحة «سلاتي» مع الملكية والعزل بين المستخدمين.
- لوحة إدارة: Dashboard، السلات، المستخدمون، المواقع، أسعار الصرف، الإعدادات.
- RTL responsive بدون اعتماد على npm.
- Feature + Unit tests.

## التشغيل
1. ثبت PHP 8.3+ وComposer.
2. داخل مجلد المشروع:
   `composer install`
3. انسخ البيئة:
   Windows: `copy .env.example .env`
   macOS/Linux: `cp .env.example .env`
4. `php artisan key:generate`
5. SQLite جاهز داخل `database/database.sqlite`. أو عدّل `.env` لاستخدام MySQL.
6. `php artisan migrate --seed`
7. `php artisan serve`

## حسابات Demo
- Admin: `admin@cartly.test` / `Admin@123456`
- Customer: `customer@cartly.test` / `Customer@123`

غيّر بيانات Demo قبل الإنتاج عبر متغيرات `DEMO_ADMIN_*` و `DEMO_CUSTOMER_*` أو احذف حسابات العرض.

## الاختبارات
`php artisan test`

الاختبارات تشمل التسجيل، الصلاحيات، عزل السلات، حساب التحويل، وتصرف SHEIN adapter عند توفر JSON-LD وعند إخفاء المنتجات.

## ملاحظة مهمة عن روابط المتاجر
المشروع لا يدّعي أن كل متجر يسمح بقراءة السلة من رابط عام. SHEIN وغيره قد يعرضون رابط مشاركة يعمل داخل التطبيق لكن لا يعرض عناصر السلة كـHTML/API عام. لذلك تم تصميم النظام بـAdapters مستقلة مع fallback يدوي، وهذا يمنع تعطل تجربة المستخدم ويجعل إضافة دعم متجر جديد سهلة.

## الإنتاج
- استخدم HTTPS.
- غيّر كلمات مرور Demo واحذف الحسابات غير المطلوبة.
- استخدم MySQL مع نسخ احتياطي.
- اضبط `APP_ENV=production` و`APP_DEBUG=false`.
- شغّل `php artisan optimize`.
- راقب أي تغييرات في بنية روابط المتاجر وعدّل Adapter الخاص بها فقط.
# Salltak
