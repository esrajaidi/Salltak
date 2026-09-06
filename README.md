# سلات ليبيا — Laravel 13 + SHEIN Browser Importer

منصة عربية RTL لحفظ سلات التسوق، قراءة روابط السلات المدعومة، عرض المنتجات، تحويل الإجمالي إلى الدينار الليبي، وإدارة السلات والمستخدمين والمواقع وأسعار الصرف.

## أهم الوظائف

- تسجيل وإنشاء حسابات العملاء.
- إدخال رابط سلة/منتج مع كشف الموقع تلقائيًا.
- دعم روابط SHEIN `onelink.shein.com` وروابط Share Cart من نوع `/cart/share/landing`.
- جلب منتجات SHEIN بطريقتين:
  1. HTTP parser سريع إذا كانت بيانات السلة موجودة في HTML.
  2. Playwright + Chromium fallback لتشغيل JavaScript وقراءة DOM وطلبات JSON/XHR عندما تكون السلة محملة ديناميكيًا.
- عرض صورة المنتج، الاسم، Product/SKU ID، اللون، المقاس، Variant، السعر، الكمية، إجمالي السطر والرابط.
- تحويل الإجمالي إلى LYD حسب سعر الصرف من لوحة الإدارة.
- حفظ السلة وسعر الصرف وقت الحفظ.
- صفحة «سلاتي» مع عزل السلات بين المستخدمين.
- لوحة إدارة: Dashboard، السلات، المستخدمون، المواقع، أسعار الصرف والإعدادات.
- Bootstrap 5 RTL وResponsive للموبايل والتابلت والكمبيوتر.

## متطلبات التشغيل

- PHP 8.3+
- Composer
- Node.js حديث + npm
- Chromium الخاص بـPlaywright
- SQLite أو MySQL

## التثبيت على macOS

من Terminal داخل مجلد المشروع:

```bash
composer install
npm install
npx playwright install chromium
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
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

يمكن أيضًا تشغيل `./setup.sh` للتجهيز الكامل على macOS/Linux.

## حسابات Demo

- Admin: `admin@cartly.test` / `Admin@123456`
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
