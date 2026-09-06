# Salltak UI/UX V4 — Bootstrap 5 Responsive

## ما تم تحسينه
- Bootstrap 5 + RTL مع هوية بنفسجية هادئة ومظهر SaaS نظيف.
- تحسين الصفحة الرئيسية والتنقل وتسجيل الدخول وإنشاء الحساب.
- شاشة "سلة جديدة" مع حالة تحميل واضحة أثناء جلب SHEIN.
- شاشة مراجعة السلة Card-based بدل جدول عريض.
- لكل منتج: صورة، اسم، لون، مقاس، SKU، السعر الأصلي، السعر بالدينار، الكمية +/-، وإجمالي المنتج بالعملتين.
- تحديث فوري للأسعار والإجماليات بدون Refresh.
- شاشة تفاصيل السلة المحفوظة تعرض السعر الأصلي وLYD لكل منتج.
- تحسين "سلاتي" ولوحة الإدارة وOffcanvas على الهاتف.
- Playwright مضبوط Headless افتراضيًا في `.env.example`.
- الإعدادات المحلية الافتراضية تستخدم file cache/session وsync queue لتجنب جداول cache/sessions/jobs أثناء التطوير.

## مهم على مشروع Mac الحالي
الـPatch لا يغيّر ملف `.env` الحالي. للتأكد أن نافذة SHEIN لا تظهر:

```env
SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
CART_IMPORT_TIMEOUT=30
SHEIN_BROWSER_ENABLED=true
SHEIN_BROWSER_HEADLESS=true
```

ثم:

```bash
php artisan optimize:clear
npm run browser:smoke
php artisan serve
```
