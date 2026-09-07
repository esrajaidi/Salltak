# Salltak V9 — UI Redesign Notes

## ما تغير

- إعادة تصميم الصفحة الخارجية بالكامل بهوية كحلي/تركواز/سماوي/ذهبي.
- Hero تفاعلي مع معاينة Dashboard/Mobile مبنية بـHTML/CSS بدون صور خارجية مطلوبة.
- أقسام: كيف تعمل المنصة، معاينة المنتجات، المميزات، المواقع المدعومة، CTA.
- تسجيل الدخول وإنشاء الحساب بتصميم Split Card حديث.
- تحديث كل صفحات العميل: إضافة السلة، المراجعة، السلات المحفوظة، تفاصيل السلة.
- تحديث Shell لوحة الإدارة: Sidebar ثابت Desktop + Offcanvas Mobile + Topbar.
- Dashboard جديد يستخدم البيانات الحالية فقط (`$stats` و`$latestCarts`) ولا يختلق بيانات تاريخية.
- توحيد تصميم صفحات الإدارة: السلات، المستخدمون، المواقع، أسعار الصرف، الإعدادات.
- Motion خفيف عبر `IntersectionObserver` + hover transitions.
- دعم `prefers-reduced-motion`.

## ما لم يتغير

- Routes وControllers وForm actions وCSRF.
- SHEIN Browser Importer V8.
- أسعار SHEIN بالدولار من `usdAmount` ثم USD → LYD.
- استخراج اللون والمقاس من V7.
- Playwright/Chromium وHeadless mode.

## ملفات الواجهة الأساسية

- `public/css/app.css`
- `public/js/app-ui.js`
- `resources/views/layouts/app.blade.php`
- `resources/views/layouts/admin.blade.php`
- `resources/views/home.blade.php`
- `resources/views/auth/*`
- `resources/views/carts/*`
- `resources/views/admin/*`

## فحص V9

```bash
php scripts/ui_v9_check.php
php scripts/ui_v4_check.php
php scripts/ui_smoke_test.php
node --check public/js/app-ui.js
node --test tests/Node/*.test.mjs
```
