# سلتك — Salltak V10.11

> **V10.9.1:** إصلاحات تشغيل `php artisan test` المبلغ عنها: بريد تحديثات العميل، طرق الدفع المخصصة، عدد كتالوج الدفع 23، اختبار Responsive، واختبارات SHEIN بعد تعريب عرض العملة.


منصة عربية RTL لحفظ سلات التسوق، قراءة روابط السلات المدعومة، عرض المنتجات، تحويل الإجمالي إلى الدينار الليبي، وإدارة السلات والمستخدمين والمواقع وأسعار الصرف.

## أهم الوظائف

- تسجيل وإنشاء حسابات العملاء.
- إدخال رابط سلة/منتج مع كشف الموقع تلقائيًا.
- دعم روابط SHEIN `onelink.shein.com` وروابط Share Cart من نوع `/cart/share/landing`.
- أسعار سلات SHEIN المشتركة تعتمد الدولار `USD` من حقل `usdAmount` في استجابة SHEIN، حتى لو كان الرابط يحتوي `local_country=AE`.
- التحويل إلى الدينار الليبي يعتمد سعر `USD -> LYD` المفعّل من لوحة الإدارة.
- جلب منتجات SHEIN بطريقتين:
  1. HTTP parser سريع إذا كانت بيانات السلة موجودة في HTML.
  2. Playwright + Chromium fallback لتشغيل JavaScript وقراءة DOM وطلبات JSON/XHR عندما تكون السلة محملة ديناميكيًا.
- عرض صورة المنتج، الاسم، Product/SKU ID، اللون، المقاس، Variant، السعر، الكمية، إجمالي السطر والرابط.
- تحويل الإجمالي إلى LYD حسب سعر الصرف من لوحة الإدارة.
- حفظ السلة وسعر الصرف وقت الحفظ.
- صفحة «سلاتي» مع عزل السلات بين المستخدمين.
- لوحة إدارة: Dashboard، السلات، المستخدمون، المواقع، أسعار الصرف والإعدادات.
- واجهة V9 كاملة بألوان كحلي + تركواز + سماوي + ذهبي، بدون اعتماد البنفسجي كهوية.
- صفحة خارجية حديثة مع Hero، خطوات الاستخدام، معاينة المنصة، Features، CTA وأنيميشن خفيف عند التمرير.
- لوحة تحكم V9 مع Sidebar ثابت على الكمبيوتر وOffcanvas على الهاتف، Topbar، KPI cards وإجراءات سريعة.
- Bootstrap 5 RTL وResponsive للموبايل والتابلت والكمبيوتر، مع احترام `prefers-reduced-motion`.


## هوية V9

ألوان الواجهة الرئيسية:

```text
Navy      #0F2744
Deep Navy #091827
Teal      #14B8A6
Sky       #38BDF8
Gold      #F4B942
Background#F4F8FB
```

تم الحفاظ على منطق V8 الخاص بـSHEIN كما هو: الأسعار من `usdAmount` بالدولار ثم التحويل إلى LYD حسب سعر الصرف المفعّل من لوحة الإدارة.

### ملاحظة Windows / Laragon

إذا كان الاستيراد يعمل من Tinker ولا يعمل من المتصفح، تأكد أن `SHEIN_BROWSER_NODE` يحتوي المسار الكامل الحقيقي لـ`node.exe` الذي يظهر من:

```bat
where node
```

مثال (استخدم مسارك الفعلي فقط):

```env
SHEIN_BROWSER_NODE="D:/laragon/bin/nodejs/node-vXX/node.exe"
```

ثم شغّل:

```bat
php artisan optimize:clear
```

وأعد تشغيل Laragon.

## متطلبات التشغيل

- PHP 8.3+
- Composer
- Node.js حديث + npm
- Chromium الخاص بـPlaywright
- MySQL 8+ أو MariaDB (المشروع مضبوط MySQL فقط)

## إعداد قاعدة البيانات MySQL

المشروع لا يعتمد إلا MySQL. أنشئ قاعدتي البيانات للتشغيل والاختبارات:

```sql
CREATE DATABASE IF NOT EXISTS `salltak` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS `salltak_test` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

يمكنك تنفيذ الملف الجاهز `database/mysql_setup.sql` من phpMyAdmin.

إعداد التشغيل الافتراضي في `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=salltak
DB_USERNAME=root
DB_PASSWORD=
```

قاعدة `salltak_test` مخصصة للاختبارات فقط، ومضبوطة في `phpunit.xml` حتى لا تمس الاختبارات بيانات التشغيل.

## التثبيت

من Terminal داخل مجلد المشروع:

```bash
composer install
npm install
npx playwright install chromium
php artisan optimize:clear
php artisan migrate --seed
```

اختبر Chromium:

```bash
npm run browser:smoke
```

شغّل اختبارات Laravel:

```bash
php artisan test
```

وشغّل النظام:

```bash
php artisan serve
```

ثم افتح عادة:

```text
http://127.0.0.1:8000
```

يمكن أيضًا تشغيل `setup.bat` على Windows/Laragon أو `./setup.sh` على macOS/Linux بعد التأكد من توفر MySQL.

## حسابات Demo

- Admin: `admin@cartly.test` / `Admin@123456`
- Order Manager: `manager@cartly.test` / `Manager@123456`
- Customer: `customer@cartly.test` / `Customer@123`

غيّر حسابات Demo قبل النشر الحقيقي.

## كيف يعمل SHEIN Share Cart؟

عند إدخال رابط مثل:

```text
https://m.shein.com/ar/cart/share/landing?...&group_id=...&local_country=AE&cart_share=1
```

النظام يقوم بالخطوات التالية:

1. يتحقق أن الرابط تابع لـSHEIN.
2. يحاول قراءة HTML مباشرة.
3. إذا لم يجد المنتجات، يشغّل `scripts/shein-browser-import.mjs` عبر Laravel Process.
4. Playwright يفتح Chromium ويشغّل JavaScript مثل المتصفح الحقيقي.
5. يتم التقاط استجابات JSON/XHR المرتبطة بالسلة والمنتجات.
6. يتم أيضًا فحص DOM بعد اكتمال تحميل الصفحة.
7. تعاد المنتجات إلى Laravel وتعرض في شاشة مراجعة السلة.
8. المستخدم يراجع المنتجات ثم يحفظ السلة.

## إعدادات SHEIN Browser في `.env`

```env
SHEIN_BROWSER_ENABLED=true
SHEIN_BROWSER_NODE=node
SHEIN_BROWSER_HEADLESS=true
SHEIN_BROWSER_TIMEOUT_MS=35000
SHEIN_BROWSER_PROCESS_TIMEOUT=110
SHEIN_BROWSER_MANUAL_CHALLENGE_WAIT_MS=60000
```

### إذا ظهر Verify / Captcha على Mac

Playwright لا يتجاوز حماية SHEIN. لتشغيل Chromium كنافذة عادية حتى تتمكن من إكمال التحقق بنفسك:

```env
SHEIN_BROWSER_HEADLESS=false
```

ثم نفّذ:

```bash
php artisan config:clear
```

أعد إدخال رابط السلة. ستظهر نافذة Chromium. إذا طلب SHEIN تحققًا أمنيًا، أكمله يدويًا. يحتفظ النظام ببروفايل Chromium داخل:

```text
storage/app/shein-browser-profile
```

وبالتالي يمكن إعادة استخدام cookies المحلية في المحاولات اللاحقة. لا تشارك هذا المجلد مع الآخرين ولا ترفعه إلى Git.

بعد نجاح التحقق يمكنك إبقاء الوضع المرئي أو إعادة:

```env
SHEIN_BROWSER_HEADLESS=true
```

ثم:

```bash
php artisan config:clear
```

## ماذا يحدث لو SHEIN منع القراءة؟

إذا ظهر HTTP 429 أو صفحة تحقق، النظام لا يتعطل. يحاول Chromium أولًا، وإذا ظل التحقق موجودًا يعطي رسالة واضحة للمستخدم. لا توجد محاولة لتجاوز Captcha أو أي حماية أمنية.

## الأمان

- Browser worker يقبل روابط HTTPS التابعة لـSHEIN فقط كرابط رئيسي.
- الطلبات إلى localhost وعناوين IP الخاصة يتم حظرها داخل worker.
- رابط المستخدم لا يمرر كجزء من shell command؛ يتم إرساله إلى worker عبر STDIN JSON.
- بروفايل Chromium المحلي مضاف إلى `.gitignore`.
- كلمات المرور مشفرة بواسطة Laravel.
- كل مستخدم يستطيع الوصول إلى سلاته فقط.

## الاختبارات

### Laravel

```bash
php artisan test
```

تشمل الاختبارات:

- استخراج SHEIN من JSON-LD.
- استخراج عدة منتجات من embedded cart state.
- قراءة escaped React/Next state.
- fallback من HTTP إلى Playwright.
- إرجاع منتجات Playwright إلى شاشة المراجعة.
- حالة SHEIN security challenge.
- الصلاحيات وعزل السلات والحسابات.

### Smoke tests بدون Composer

```bash
php scripts/smoke_test.php
php scripts/ui_smoke_test.php
node --check scripts/shein-browser-import.mjs
```

### اختبار Chromium الحقيقي

بعد `npm install` و`npx playwright install chromium`:

```bash
npm run browser:smoke
```

## ملاحظة تشغيلية مهمة

نجاح قراءة أي سلة خارجية يعتمد على ما يسمح به الموقع في وقت التنفيذ. SHEIN قد يغير DOM أو API أو يطلب تحققًا أمنيًا. تم فصل SHEIN importer عن بقية النظام حتى يمكن تحديثه دون إعادة بناء المنصة كاملة.

---

# V10 — الطلبات، العربون وطرق الدفع

بعد حفظ سلة SHEIN أو أي سلة مدعومة، يستطيع العميل الآن الضغط على **«اطلب هذه السلة»**. يتم إنشاء Order مستقل يحتفظ بنسخة من المنتجات والأسعار، ثم يدخل في دورة مراجعة كاملة.

## دورة الطلب
`submitted → under_review → needs_customer_action → approved → awaiting_deposit / awaiting_payment → deposit_paid → purchasing → ordered → shipped → arrived_libya → awaiting_balance → ready_for_delivery → out_for_delivery → delivered`

يوجد أيضًا `rejected` و`cancelled`. رفض الطلب يتطلب سببًا، ولا يمكن تحويل الطلب إلى `delivered` إذا بقي عليه مبلغ غير مسدد.

## العربون والدفع الجزئي
من **لوحة الإدارة → قواعد العربون** يمكن إنشاء شرائح حسب إجمالي الطلب، بنسبة أو مبلغ ثابت. الإعداد التجريبي:
- أقل من 200 د.ل: 30%
- 200 إلى 500 د.ل: 40%
- أكثر من 500 د.ل: 50%

المسؤول يستطيع عند اعتماد طلب معين استخدام القواعد تلقائيًا أو تحديد نسبة/مبلغ مختلف مع تسجيل سبب التعديل. النظام يدعم عدة دفعات لنفس الطلب ويحسب `paid_amount` و`remaining_amount` تلقائيًا بعد اعتماد كل دفعة.

## طرق الدفع
من **لوحة الإدارة → طرق الدفع** يستطيع مدير النظام:
- تشغيل/إيقاف كل طريقة.
- إضافة طريقة جديدة بدون تعديل الكود.
- تحديد نوعها: يدوي / مصرف / محفظة / API / نقدي.
- تحديد Min/Max، ترتيب الظهور، رسوم ثابتة أو نسبة، وتعليمات العميل.
- حفظ بيانات الحساب الظاهرة للعميل.
- حفظ Merchant ID / API Key / Secret / Webhook Secret / Checkout URL، ويتم تخزين حقل `config` مشفرًا.

V10 يضيف إدخالات جاهزة لـ LYPay وOnePay والتحويل المصرفي والمحفظة/التحويل اليدوي وبوابة بطاقات محلية والنقدي. بوابات API تكون غير مفعلة افتراضيًا حتى يتم إدخال عقد التاجر والمفاتيح والربط حسب وثائق المزود الرسمية؛ لا يتم تزوير نجاح دفع إلكتروني.

## مسؤول الطلبات
دور جديد: `order_manager`. مدير النظام يستطيع تحويل مستخدم إلى مسؤول طلبات من صفحة المستخدمين. المسؤول يستطيع مراجعة الطلبات والمنتجات، الإسناد، التواصل، اعتماد الدفعات وتغيير الحالات، بينما إعداد طرق الدفع وقواعد العربون يبقى للـadmin فقط.

## التحديث من V9
احتفظ بملف `.env` الحالي، ثم:

```bash
composer install
npm install
npx playwright install chromium
php artisan migrate
php artisan db:seed
php artisan storage:link
php artisan optimize:clear
```

بعدها أعد تشغيل Laragon Apache/MySQL.

### حسابات Demo
- Admin: `admin@cartly.test` / `Admin@123456`
- Order Manager: `manager@cartly.test` / `Manager@123456`
- Customer: `customer@cartly.test` / `Customer@123`

غيّر كلمات المرور في أي بيئة فعلية.

---

# V10.2 — إصلاح اختفاء السلة + دليل طرق الدفع الليبية

## إصلاح اختفاء المنتجات بعد الجلب
تم إصلاح مشكلة الـReveal Animation التي كانت تخفي كرت السلة الطويل بعد تحميل JavaScript. الكروت الأساسية للسلة أصبحت ظاهرة مباشرة، كما تم تخفيض `IntersectionObserver threshold` ليعمل مع القوائم الطويلة.

## دليل طرق الدفع الليبية
تمت إضافة كتالوج مركزي لطرق الدفع بناءً على دليل شركات الدفع الإلكتروني المنشور من مصرف ليبيا المركزي، بالإضافة إلى LYPay وOnePay، والتحويل المصرفي والدفع النقدي.

الطرق الجاهزة في الدليل:
- LYPay
- OnePay
- محفظة المدار الجديد
- محفظة شركة الاتحاد الدولي
- محفظة ميزا للخدمات المالية
- محفظة دليل ليبيا
- محفظة فوري للدفع الإلكتروني
- محفظة البداية للتقنية المالية
- RUNPAY - مساهمات الائتمانية
- تداول - البطاقات المحلية
- تفاني - قبول البطاقات المحلية
- عبور - البطاقات المحلية
- مسارات - الهاتف المحمول والبطاقات المحلية
- إثمار - البطاقات المحلية
- معاملات - البطاقات المحلية
- تحويل مصرفي
- دفع نقدي / عند الاستلام

كل الطرق الجديدة تُنشأ **متوقفة افتراضيًا**. مدير النظام هو من يفعّل ما يملكه ويضبط بيانات التاجر. من صفحة **الإدارة → طرق الدفع** يوجد زر **«تثبيت / تحديث الدليل الليبي»** ويمكن تشغيل/إيقاف كل طريقة بشكل مستقل.

لكل طريقة يمكن ضبط:
- ترتيب الظهور.
- الحد الأدنى والأعلى للمبلغ.
- رسوم ثابتة أو نسبة.
- تعليمات العميل.
- اسم المصرف / الحساب / IBAN / رقم الحساب.
- رقم المحفظة / اسم التاجر / الهاتف / بيانات QR.
- Merchant ID / Terminal ID.
- API Base URL / Checkout URL / Callback / Return / Cancel URL.
- API Key / Secret Key / Username / Password / Webhook Secret.
- Test / Live.
- نوع إثبات الدفع: رقم عملية، إيصال، أحدهما، أو بدون إثبات.

> ملاحظة: إعدادات API جاهزة للحفظ المشفر، لكن لا يتم تنفيذ تكامل آلي وهمي. الوضع الافتراضي هو `manual_verification` حتى يتم تركيب موصل API رسمي خاص بالمزود.

## ظهور طرق الدفع للعميل
عندما يصبح الطلب في حالة قابلة للدفع، العميل يرى فقط الطرق التي:
1. فعّلها مدير النظام.
2. يناسبها مبلغ العربون/الرصيد الحالي حسب Min/Max.
3. ليست مضبوطة على API غير موصول.

الاختيار يظهر كبطاقات Responsive بدل قائمة بسيطة، مع تعليمات ورسوم وبيانات الحساب الآمنة فقط. المفاتيح السرية لا تظهر للعميل.

## تحديث قاعدة موجودة
بعد استبدال الملفات:

```bash
php artisan optimize:clear
php artisan migrate
php artisan db:seed --class=Database\\Seeders\\LibyaPaymentMethodsSeeder
```

أو افتح **لوحة الإدارة → طرق الدفع → تثبيت / تحديث الدليل الليبي**.

# V10.3 — طرق الدفع الليبية بشكل منطقي وقابل للتفعيل

V10.3 يفصل بين **طريقة الدفع التي يراها العميل** وبين **بيانات المزود/المعالج التي يضبطها مدير النظام**. الكتالوج يحتوي 22 إدخالًا: LYPay، OnePay، تحويل مصرفي تقليدي، NUMO/Merchant QR، بطاقات محلية، Visa، Mastercard، POS/SoftPOS، نقدي، إضافة إلى المحافظ ومعالجات البطاقات المدرجة في دليل مصرف ليبيا المركزي.

## قاعدة مهمة
لا يوجد API وهمي. إذا كانت وثائق Merchant API غير منشورة للعامة، تبقى الطريقة متوقفة إلى أن تضيف بيانات العقد/التاجر من الشركة. طرق البطاقات الخارجية تحتاج على الأقل Acquirer/Processor + Merchant ID + Checkout URL قبل التفعيل. وضع `partner_api` لا يظهر للعملاء حتى يتم تركيب Connector فعلي داخل الكود.

## LYPay
الضبط الافتراضي هو **QR/تحويل فوري**: يمكن إدخال IBAN أو رقم الحساب أو Merchant QR. حقول API/Webhook موجودة لحفظ بيانات تكامل مصرف/جهة مرخصة مستقبلًا، لكن لا يتم ادعاء اتصال مباشر بمصرف ليبيا المركزي.

## OnePay
موجود كخدمة دفع فوري مستقلة. يتم إدخال حساب/معرّف المستفيد أو Merchant ID/QR وفق البيانات التي يمنحها المصرف/المزود. لا يتم افتراض Merchant API عامة غير منشورة.

## لوحة الإدارة
صفحة طرق الدفع أصبحت Cards مختصرة Responsive مع بحث وفلاتر. زر **الإعدادات** يفتح Modal خاص بالطريقة ويعرض فقط الحقول المنطقية لها. زر **تفعيل** يرفض التشغيل لو الإعداد ناقص، وأي طريقة مفعلة تصبح ناقصة بعد تعديلها يتم إيقافها تلقائيًا.

## تحديث V10.2 موجود
```bash
php artisan optimize:clear
php artisan migrate
php artisan db:seed --class=Database\\Seeders\\LibyaPaymentMethodsSeeder
```
ثم افتح: **لوحة التحكم → طرق الدفع** واضبط بيانات الاستقبال/التاجر وفعّل الطرق التي تستخدمها فقط.

# V10.5 — Premium order operations, notifications and monitoring

V10.5 adds a premium customer order page, a new backoffice operations workspace, SweetAlert2 confirmations/toasts, database notifications, customer/internal order notes, persistent status history with note visibility, and a system audit log.

Important rules:
- The customer cannot edit original USD or LYD product prices.
- Backoffice may review a LYD price and must provide a reason when the item changes or is unavailable.
- Every status change is persisted with actor/time/from/to/note/visibility.
- Internal notes are staff-only; customer notes appear in the customer timeline and create a notification.
- Rejected/cancelled/needs-customer-action states require a reason.
- Dashboard monitoring shows attention orders, pending payment checks, aging work and recent audit activity.

Upgrade an existing MySQL database without deleting data:

```bash
php artisan optimize:clear
php artisan migrate
```

Then verify:

```bash
php artisan test
```

See `SALLTAK_V10_5_NOTES.md` and `TEST_REPORT_V10_5.md`.

# V10.6 — تحسين تجربة العميل والإدارة + الكاش + إشعارات البريد

- الواجهة الظاهرة عربية مع الحفاظ على أسماء العلامات الرسمية مثل SHEIN وLYPay وOnePay.
- الأسعار المستوردة من المتجر لا يرسلها العميل عند الحفظ؛ السيرفر يعتمد Snapshot موثوق ويقبل تغيير الكمية فقط.
- تمت إضافة Pagination للسلات والطلبات والقوائم الطويلة.
- عند جلب السلة تظهر شاشة انتظار متحركة برسائل عربية حتى يكتمل الاستيراد.
- لوحة الإدارة تستخدم SVG للأيقونات وواجهة مراقبة عمليات أكثر ترتيبًا.
- `cash` يدعم عربون نقدي أو رصيد نقدي حسب إعداد المدير، ويظل بانتظار تحقق الموظف حتى استلام المبلغ فعليًا.
- `cash_on_delivery` طريقة منفصلة للرصيد النهائي عند التسليم ولا تلغي العربون المطلوب قبل الشراء.
- كتالوج الدفع الحالي يحتوي 23 مدخلًا منطقيًا، وكل طريقة تُفعل أو توقف من مدير النظام.
- إعدادات النظام تسمح بأكثر من بريد لاستقبال تنبيهات الطلبات الجديدة ورسائل العملاء والدفعات.

لتحديث قاعدة MySQL الموجودة بدون حذف البيانات:

```bash
php artisan optimize:clear
php artisan migrate
php artisan db:seed --class=Database\\Seeders\\LibyaPaymentMethodsSeeder
```

بعد تثبيت `vendor` وNode dependencies شغل:

```bash
php artisan test
npm run browser:smoke
```

راجع `SALLTAK_V10_6_NOTES.md` و `TEST_REPORT_V10_6.md` للتفاصيل.

# V10.7 — الموقع الخارجي الديناميكي من لوحة التحكم

V10.7 يضيف CMS بسيط ومرن للموقع الخارجي بدل المحتوى الثابت. من **لوحة التحكم → إدارة الموقع الخارجي** تقدري تعدلي العناوين، النصوص، الصور، خطوات العمل، المميزات، آراء العملاء، الأسئلة الشائعة، CTA، بيانات الفوتر وSEO بدون تعديل الكود.

المحتوى يعتمد **مسودة / نشر**: الحفظ لا يغير الصفحة العامة مباشرة. استخدمي **معاينة المسودة** ثم **نشر القسم** أو **نشر كل التعديلات** لما تكوني جاهزة.

المتاجر المعروضة تأتي تلقائيًا من المتاجر المفعلة، وطرق الدفع المعروضة تأتي فقط من الطرق المفعلة والمكتملة الإعداد.

لتحديث قاعدة MySQL الحالية بدون حذف أي بيانات:

```bash
php artisan optimize:clear
php artisan migrate
php artisan storage:link
```

بعدها افتحي لوحة التحكم، ادخلي **إدارة الموقع الخارجي**، ارفعي صورك وعدلي المحتوى ثم انشريه.

راجع `SALLTAK_V10_7_NOTES.md` و `TEST_REPORT_V10_7.md` للتفاصيل.

## Salltak V10.8 responsive UI update
V10.8 removes the old admin header offset, improves the Arabic typography, adds mobile card rendering for admin tables, improves responsive behavior across the admin/customer/marketing shells, and adds accessible motion. No V10.8 database migration is required; after upgrading run `php artisan optimize:clear`.

## Salltak V10.9 — قرارات الطلب والملاحظات المتراكمة
V10.9 يحسن متابعة قرارات الطلب: كل ملاحظة تحفظ كسجل مستقل ولا تستبدل السابقة، وسبب الرفض/الإلغاء/طلب رد العميل إلزامي ويظهر للعميل. القبول يدعم ملاحظة اختيارية للعميل. تغييرات الحالة تحدد ظهورها تلقائيًا، بينما الملاحظات التشغيلية المستقلة يمكن أن تكون داخلية أو ظاهرة للعميل. يمكن أيضًا تفعيل بريد تحديثات الطلب للعميل من إعدادات النظام.

لا توجد Migration جديدة في V10.9. بعد التحديث شغل:

```bash
php artisan optimize:clear
php artisan test
```

## بيانات الدفع التجريبية

في بيئة `local` و`testing` فقط، يقوم `DatabaseSeeder` بتفعيل LYPay والدفع النقدي ببيانات تجريبية واضحة للاختبار. هذه البيانات ليست صالحة لأي دفع حقيقي. يمكن تعطيل هذا السلوك قبل تشغيل الـSeeder عبر:

```env
DEMO_PAYMENT_METHODS=false
```

في بيئة `production` لا يتم حقن بيانات LYPay التجريبية تلقائيًا.

لتفعيل بيانات الدفع التجريبية فقط على مشروع محلي موجود مسبقًا بعد تثبيت كتالوج الدفع:

```bash
php artisan db:seed --class=Database\\Seeders\\LibyaPaymentMethodsSeeder
php artisan db:seed --class=Database\\Seeders\\DemoPaymentMethodsSeeder
```


## Salltak V10.11 — سياسة إلغاء الطلب والعربون

عند إلغاء الطلب من حساب العميل، أصبح سبب الإلغاء إلزاميًا. وإذا كان هناك عربون مدفوع، يجب على العميل الإقرار بأن العربون غير قابل للاسترداد وفق سياسة الإلغاء المعتمدة قبل تنفيذ الإلغاء. تحفظ قيمة العربون وسبب الإلغاء في سجل الطلب، ويبقى المبلغ المدفوع في السجل المالي بينما يصبح الرصيد المستحق على الطلب الملغي صفرًا. إذا تجاوزت الدفعات قيمة العربون، يتطلب الإلغاء مراجعة المسؤول.

لا توجد Migration جديدة. بعد التحديث شغل:

```bash
php artisan optimize:clear
php artisan test
```
