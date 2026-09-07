# TEST REPORT — Salltak V10.11

## نطاق الفحص

تم التحقق من سياسة إلغاء الطلب بعد دفع العربون ومن اختبارات الانحدار الثابتة المتعلقة بالدفع والطلبات وSHEIN والواجهة.

## النتائج المنفذة في بيئة التجهيز

- `tests/static/order-cancellation-policy-v1011.test.mjs`: PASS بعد دورة RED → GREEN.
- جميع ملفات `tests/static/*.test.mjs`: PASS.
- `tests/Node/shein-goods-attr.test.mjs`: 5/5 PASS.
- `tests/Node/shein-usd-price.test.mjs`: 2/2 PASS.
- PHP lint على `app`, `bootstrap`, `config`, `database`, `routes`, `tests`: 0 syntax errors.

## اختبار Laravel المضاف

تمت إضافة:

- `tests/Feature/OrderCancellationPolicyTest.php`

ويغطي:

1. إلزام سبب الإلغاء وإقرار السياسة عند وجود عربون مدفوع.
2. حفظ قيمة العربون المحتفظ بها وسبب الإلغاء في سجل الطلب.
3. السماح بإلغاء الطلب غير المدفوع مع سبب فقط.
4. منع الإلغاء الذاتي إذا تجاوزت الدفعات قيمة العربون.

لم يتم تشغيل `php artisan test` الكامل داخل بيئة التجهيز لأن حزمة التسليم لا تحتوي Composer `vendor`. يجب تشغيله في بيئة المشروع بعد `composer install`.
