# SHEIN Shared Cart Reliability Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** استيراد عناصر SHEIN المشتركة بعددها ونسخها وأسعار USD وصورها الصحيحة؛ عرض أي عناصر غير مؤكدة للمراجعة فقط مع منع حفظ السلة غير المكتملة من الخادم.

**Architecture:** نفصل إثبات عضوية العنصر في قائمة المشاركة عن استخراج هويته، ثم ربط السعر بهوية النسخة والسوق؛ لا نستخدم مسح كل استجابات الصفحة أو المطابقة التقريبية. يوحّد عامل Playwright نتيجة الاستيراد، ثم يحوّلها Laravel إلى معاينة مكتملة أو جزئية، ويطبق `CartController::store` حاجز الحفظ من بيانات Session موثوقة.

**Tech Stack:** Laravel 13.8 / PHP ^8.3 / PHPUnit 12.5، Node.js ESM، Playwright Chromium 1.62.1، Blade + Bootstrap 5 RTL، MySQL، Railway.

**Spec:** `docs/superpowers/specs/2026-09-29-shein-shared-cart-reliability-design.md`

## Global Constraints

- العربية الفصحى للواجهات والرسائل؛ RTL وBootstrap 5 وهوية سلتك الحالية دون إعادة تصميم.
- استيراد روابط SHEIN Shared Cart فقط؛ لا استيراد سلة خاصة أو تجاوز تحدٍّ أمني من SHEIN.
- `AED/SAR` لا يُعاملان على أنهما `USD`؛ سعر `USD` لا يُعتمد دون هوية المنتج/النسخة والسوق والسياق.
- المعاينة الجزئية مسموحة، لكن حفظ السلة والطلب ممنوعان حتى إثبات اكتمال القائمة والأسعار.
- لا منتجات وهمية ولا دمج نسخ مختلفة لمجرد تشابه الاسم أو الصورة؛ الكمية الأصلية تُحفظ إذا ثبتت.
- التشخيص الآمن يسجل أعدادًا وتصنيفات فقط، ولا يسجل روابط `shc/group_id` كاملة أو cookies أو أسماء منتجات أو أجسام استجابات.
- كل إصلاح يبدأ باختبار يفشل للسبب المتوقع، ثم أصغر تغيير، ثم اختبار النجاح؛ اعزل الإنتاج عن التجارب والـfixtures.
- لا تدّعِ نجاح رابط حي بسبب نجاح الاختبارات أو Deploy؛ الاختبار الحقيقي يقارن SHEIN وسلتك في السوق/النسخة/الوقت نفسه.

## Review Focus

- [Task 2] نفس `goods_id` وله `sku_id` مختلف ولون/مقاس مختلف: يظل سطرين مستقلين؛ لا تجميع بالاسم أو الصورة.
- [Task 3] استجابة أب فيها سعر `usdAmount` لنسختين: لا ينتقل سعر الأولى للثانية؛ عند غياب SKU يكون السعر غير مؤكد.
- [Task 4] عميل يرسل POST مباشرًا ويحذف السطر غير المؤكد من HTML: الخادم يرفض حفظ **كل** Snapshot غير مكتمل.
- [Task 5] URL صورة صحيح شكليًا لكنه لا يتحمّل في المتصفح، أو fallback لمنتج آخر: placeholder فقط بلا صورة خاطئة.
- [Task 6] OneLink يعيد صفحة تحدٍّ أو يعلق الـretry: نتيجة `challenge/timeout` خلال الميزانية، بلا retry متكرر أو تسريب token.

## File Structure / Interfaces

- `scripts/shein-share-evidence.mjs`: تصنيف إشارات الشبكة وحاوية المشاركة بصورة خالية من الأسرار؛ لا استخراج أسعار/منتجات من الشبكة الشاملة.
- `scripts/shein-shared-cart-items.mjs`: تحويل مصدر مشاركة مثبت إلى `SharedCartItemCandidate` مع دليل العضوية والكمية والهوية المركبة.
- `scripts/shein-shared-cart-price.mjs`: `resolveUsdQuote(candidate, priceRecords, context)`؛ ربط USD بنفس SKU/النسخة والسوق، أو `unconfirmed`.
- `scripts/shein-shared-page-import.mjs`: تنسيق مصادر المشاركة، واستدعاء الوحدتين، ونتيجة `complete/partial_review/unreadable/challenge/timeout`.
- `scripts/shein-shared-page-retry.mjs`: يُحذف الـpreflight المكرر أو يتحول إلى wrapper بلا متصفح ثانٍ؛ عامل الاستيراد نفسه يقبل بيئة المتصفح.
- `app/Services/CartImport/Browser/SheinBrowserImporter.php`: تتبع المحاولات والمهلة وإعدادات الجهاز واللغة، وإرجاع نتيجة موحدة.
- `app/Services/CartImport/Adapters/SheinShareAdapter.php` و`CartImportService.php`: حصر Fast Path بمصدر مشاركة مثبت، والحفاظ على سياق الرابط الأصلي عند تحويل OneLink.
- `app/Services/CartImport/ImportResult.php` و`app/Http/Controllers/CartController.php` و`resources/views/carts/preview.blade.php`: معاينة كاملة/جزئية، أسعار nullable، حاجز حفظ من الخادم.
- `app/Services/CartImport/Browser/SheinImportedItemCleaner.php`: صور fallback من **هوية العنصر/النسخة نفسها** فقط.
- `app/Services/CartImport/Browser/SheinImportDiagnostics.php`: ملخصات allowlisted منفصلة عن بيانات المستخدم.
- `tests/fixtures/shein/`: Fixtures اصطناعية أو منقّحة، ببيان أصلها في `docs/shein/fixture-manifest.md`؛ لا رموز مشاركة حقيقية.

**عقد العنصر المشترك بين Node وPHP:** `external_id` = `goods_id`، و`sku_id`، و`skc_id`، و`color`، و`size`، و`quantity`، و`share_position`، و`name`، و`image_url`، و`product_url`، و`unit_price_original: number|null`، و`price_status: confirmed|unconfirmed`، و`price_source: visible_same_card|network_exact_sku|null`، و`currency: USD`. الناتج يتضمن `share_complete: boolean`، و`expected_count: number|null`، و`observed_count: number`، و`status`. لا تضع رقمًا افتراضيًا عند عدم توفر دليل السعر/الكمية.

---

### Task 1: إثبات مصدر عناصر المشاركة وتشخيص حالة DOM=0

**Files:**
- Create: `scripts/shein-share-evidence.mjs`
- Create: `tests/static/shein-share-evidence.test.mjs`
- Create: `tests/fixtures/shein/README.md`
- Create: `docs/shein/fixture-manifest.md`
- Modify: `scripts/shein-shared-page-import.mjs`؛ `app/Services/CartImport/Browser/SheinImportDiagnostics.php`

**Interfaces:** `summarizeShareEvidence({ networkResponses, pageSignals, expectedShareContext }): { shareResponseSeen: boolean, shareBoundCandidateCount: number, domCandidateCount: number, responseClassCounts: Record<string,number> }`. المخرجات أعداد وتصنيفات فقط. `expectedShareContext` يبقى داخل العملية، ولا يخرج إلى logs.

- [ ] **Step 1: اكتب اختبارًا فاشلًا** في `tests/static/shein-share-evidence.test.mjs`: عينة اصطناعية فيها استجابة مشاركة بها عنصران، واستجابة توصيات بها 82 سجل USD، وDOM صفر؛ يؤكد أن `shareBoundCandidateCount===2` لا 82، وأن لا token أو URL كامل أو اسم أو سعر في JSON التشخيص. حالة لا يوجد فيها دليل عضوية تُرجع صفرًا.
- [ ] **Step 2: شغّل الأحمر**: `node --test tests/static/shein-share-evidence.test.mjs`؛ المتوقع فشل استيراد الدالة المفقودة أو فشل اختبار العضوية فقط.
- [ ] **Step 3: نفّذ `summarizeShareEvidence`** مع تصنيف allowlisted؛ أضف العدادات الآمنة إلى meta/`SheinImportDiagnostics::attempt` من دون payload raw. صنّف الاستجابات وفق evidence مثبت، ولا تعتبر وجود `usdAmount` وحده إثبات مشاركة.
- [ ] **Step 4: وثّق المشاهدة الفعلية**: شغّل تجربة مخوّلة على رابط مشاركة واحد يعمل وآخر يفشل في بيئة معزولة؛ سجّل **فقط** شكل الحاوية والمفاتيح اللازمة بعد تنقيحها في manifest. سمِّ العينات `synthetic` أو `observed_redacted` بصراحة. إن لم يظهر دليل عضوية من الاستجابة/الـhydrated state/DOM، أوقف مهام الاستخراج ولا تخمّن endpoint أو شكل JSON.
- [ ] **Step 5: شغّل الأخضر**: `node --test tests/static/shein-share-evidence.test.mjs` و`php tests/static/shein-import-diagnostics-v1021.php`؛ المتوقع نجاح الاختبارين وعدم ظهور أسرار في المخرجات.
- [ ] **Step 6: Commit**: `git add scripts/shein-share-evidence.mjs scripts/shein-shared-page-import.mjs app/Services/CartImport/Browser/SheinImportDiagnostics.php tests/static/shein-share-evidence.test.mjs tests/fixtures/shein/README.md docs/shein/fixture-manifest.md && git commit -m "test: establish safe SHEIN share evidence"`.

### Task 2: قائمة مشاركة موثوقة وهوية كل نسخة

**Files:**
- Create: `scripts/shein-shared-cart-items.mjs`؛ `tests/static/shein-shared-cart-items.test.mjs`
- Create: `tests/fixtures/shein/shared-list-redacted.json` و`tests/fixtures/shein/recommendations-redacted.json` بعد Task 1
- Modify: `scripts/shein-shared-page-import.mjs`؛ `app/Services/CartImport/Adapters/SheinShareAdapter.php`؛ `tests/Feature/SheinImportTest.php`

**Interfaces:** `extractSharedCartCandidates(payload, { shareContext, source }): { items: SharedCartItemCandidate[], expectedCount: number|null, shareBound: boolean }`؛ `source` أحد `share_response|hydrated_share|share_dom`. `canonicalItemKey(candidate): string` يستخدم `goods_id + sku_id/skc_id + color + size` ومعرّف سطر المشاركة إن وجد؛ لا يستخدم الصورة/الاسم.

- [ ] **Step 1: اكتب اختبارات فاشلة**: fixture قائمة من 31 عنصرًا + توصيات خارج الحاوية → 31 فقط؛ كرت مكرر من الشبكة وDOM → عنصر واحد؛ نفس `goods_id` بنسختين/SKU مختلف → عنصران؛ كمية 3 تبقى 3؛ غياب كمية مثبتة أو عدد كامل يجعل `shareBound/share_complete` غير كافيين للحفظ.
- [ ] **Step 2: شغّل الأحمر**: `node --test tests/static/shein-shared-cart-items.test.mjs`؛ المتوقع فشل الوظيفة المفقودة أو assertions الخاصة بالتوصيات/النسخ.
- [ ] **Step 3: نفّذ الوحدة** بناءً على schema المثبتة في Task 1؛ لا تستخرج من كل صور الصفحة. استدعها من الـworker؛ لا ترقِّ `networkUsdById` العام إلى قائمة السلة. في PHP اقصر الـHTML Fast Path لروابط المشاركة على `cartShareData/goods_list` المثبتة، وأوقف `Product JSON-LD` المنفرد كمصدر سلة مشتركة؛ حدّث اختبار `test_shein_share_adapter_extracts_json_ld_product_when_exposed` ليؤكد المعاينة غير المكتملة لرابط Share بدل نجاح زائف.
- [ ] **Step 4: شغّل الأخضر**: `node --test tests/static/shein-shared-cart-items.test.mjs` و`php artisan test tests/Feature/SheinImportTest.php`؛ المتوقع نجاح اختبارات العضوية وعدم إدخال منتجات توصيات.
- [ ] **Step 5: Commit**: `git add scripts/shein-shared-cart-items.mjs scripts/shein-shared-page-import.mjs app/Services/CartImport/Adapters/SheinShareAdapter.php tests/static/shein-shared-cart-items.test.mjs tests/fixtures/shein tests/Feature/SheinImportTest.php && git commit -m "fix: import only verified shared-cart items"`.

### Task 3: سعر USD مطابق لنفس الـSKU والسوق

**Files:**
- Create: `scripts/shein-shared-cart-price.mjs`؛ `tests/static/shein-shared-cart-price.test.mjs`
- Modify: `scripts/shein-shared-page-import.mjs`؛ `tests/static/shein-shared-card-accuracy-v1018.test.mjs`

**Interfaces:** `resolveUsdQuote(candidate, priceRecords, { market, locale, observedAt }): { status: 'confirmed'|'unconfirmed', amountUsd: number|null, source: 'visible_same_card'|'network_exact_sku'|null }`. المنتج `candidate` يأتي من Task 2، والأسعار تأتي من نفس الكرت أو سجل تسعير مرتبط بمعرّف نسخته وسياق المحاولة.

- [ ] **Step 1: اكتب اختبارًا فاشلًا**: كائن أب يحوي `skuA=10.62 USD` و`skuB=9.59 USD`؛ كل SKU يأخذ سعره فقط. سجل AED قيمته 37 من دون `usdAmount` → `unconfirmed`؛ اسمان/صورتان متطابقتان بلا SKU مطابق → `unconfirmed`؛ تعارض سعرين موثوقين في السياق نفسه → `unconfirmed`؛ اختلاف السوق/وقت العرض لا يخلط الأسعار.
- [ ] **Step 2: شغّل الأحمر**: `node --test tests/static/shein-shared-cart-price.test.mjs`؛ المتوقع فشل استيراد `resolveUsdQuote` أو اختبار منع خلط SKU.
- [ ] **Step 3: نفّذ الوحدة** وألغِ للـshared-cart مسار `explicitUsd(parent)` الذي يلتقط أول سعر داخل الأب، و`fuzzyNamePrice` ومطابقة السعر بالصورة دون هوية. أكّد السعر المرئي فقط من الكرت نفسه، لا من parent يحتوي كروتًا أخرى؛ احتفظ بـ`price_source` و`observedAt` داخل سياق الاستيراد.
- [ ] **Step 4: شغّل الأخضر**: `node --test tests/static/shein-shared-cart-price.test.mjs tests/static/shein-shared-cart-items.test.mjs`؛ المتوقع نجاح كل حالات اختلاف SKU/العملة.
- [ ] **Step 5: Commit**: `git add scripts/shein-shared-cart-price.mjs scripts/shein-shared-page-import.mjs tests/static/shein-shared-cart-price.test.mjs tests/static/shein-shared-card-accuracy-v1018.test.mjs && git commit -m "fix: bind USD quote to exact SHEIN variant"`.

### Task 4: معاينة جزئية وحاجز حفظ حقيقي من الخادم

**Files:**
- Modify: `app/Services/CartImport/ImportResult.php`؛ `app/Services/CartImport/Adapters/SheinShareAdapter.php`؛ `app/Http/Controllers/CartController.php`؛ `resources/views/carts/preview.blade.php`؛ `tests/Feature/CartFlowTest.php`
- Create: `tests/Feature/SheinImportPreviewSafetyTest.php`

**Interfaces:** يحوّل Adapter `complete` إلى `ImportResult::success` مع `meta.share_complete=true`، و`partial_review` إلى `ImportResult::needsReview($message,$items,'USD',$meta)` مع `share_complete=false`. `CartController::canSaveSheinShare(array $snapshot): bool` تُستخدم قبل بدء Transaction، بناءً على الـSession فقط.

- [ ] **Step 1: اكتب اختبارات Feature فاشلة**: معاينة عنصر سعره `null` تظهر «السعر غير مؤكد» ولا تعرض إجمالي USD/LYD؛ الـsave disabled؛ POST مباشر يحذف السطر الناقص أو يرسل سعرًا مزيفًا → رفض ولا Cart في DB؛ Snapshot قديم لرابط SHEIN بلا `share_complete` → رفض؛ Snapshot مكتمل لكل الأصناف المؤكدة → حفظ بسعر الصرف المثبت؛ تدفق المتجر غير SHEIN لا يتعطل.
- [ ] **Step 2: شغّل الأحمر**: `php artisan test tests/Feature/SheinImportPreviewSafetyTest.php tests/Feature/CartFlowTest.php`؛ المتوقع فشل حالات الحفظ/السعر الناقص، وليس فشل إعداد MySQL.
- [ ] **Step 3: نفّذ تحويل النتائج**؛ احتفظ بـ`unit_price_original=null` و`price_status=unconfirmed` في الـSession، لا `(float) null`. في `store` احسب `canSaveSheinShare` من مصدر SHEIN و`import_status=success` و`share_complete===true` وجميع عناصر Snapshot `confirmed` وبسعر USD عددي موجب؛ لا تعتمد على القيم المرسلة من HTML أو حذف سطر غير مؤكد. حدّث fixtures القديمة في `CartFlowTest.php` لإثبات الاكتمال بدل تجاوز الحاجز.
- [ ] **Step 4: نفّذ واجهة المعاينة** بـRTL الحالي: السعر غير المؤكد بدل 0، إخفاء الإجمالي النهائي، تعطيل حفظ السلة/الطلب بصريًا؛ الخادم يرفض مهما تغيّر DOM. لا تعطل التحكم بالكمية للمعاينة المكتملة.
- [ ] **Step 5: شغّل الأخضر**: `php artisan test tests/Feature/SheinImportPreviewSafetyTest.php tests/Feature/CartFlowTest.php tests/Feature/SheinImportTest.php`؛ المتوقع عدم إنشاء أي Cart غير مكتمل واستمرار الحالات المكتملة.
- [ ] **Step 6: Commit**: `git add app/Services/CartImport/ImportResult.php app/Services/CartImport/Adapters/SheinShareAdapter.php app/Http/Controllers/CartController.php resources/views/carts/preview.blade.php tests/Feature/SheinImportPreviewSafetyTest.php tests/Feature/CartFlowTest.php && git commit -m "fix: block saving unverified SHEIN shared carts"`.

### Task 5: صور مرتبطة بهوية المنتج نفسها

**Files:**
- Modify: `app/Services/CartImport/Browser/SheinImportedItemCleaner.php`؛ `scripts/shein-shared-page-import.mjs`؛ `resources/views/carts/preview.blade.php`؛ `tests/Unit/SheinImportedItemCleanerTest.php`
- Create: `tests/static/shein-shared-image-identity.test.mjs`

**Interfaces:** `SheinImportedItemCleaner::clean(array $items, array $fallbackItems=[]): array` يحافظ على التوقيع القائم؛ الـfallback للـshared-cart يقبل فقط هوية `goods_id+sku_id/skc_id/variant` المطابقة. لا fallback بالمؤشر أو بنفس الاسم فقط.

- [ ] **Step 1: اكتب اختبارات فاشلة**: `//img.ltwebstatic.com/...webp` → HTTPS؛ URL صفحة منتج أو placeholder → مرفوض؛ عنصران لهما نفس `goods_id` لكن SKU مختلف، صورة الثانية لا تنتقل للأولى؛ صورة المتصفح المكسورة → placeholder بلا نص alt مشوه.
- [ ] **Step 2: شغّل الأحمر**: `php artisan test tests/Unit/SheinImportedItemCleanerTest.php` و`node --test tests/static/shein-shared-image-identity.test.mjs`.
- [ ] **Step 3: طبّق ربط الصورة** بالهوية المثبتة؛ أزل fallback بالمؤشر في مسار السلة المشتركة، واحفظ الصورة الصحيحة أو قيمة فارغة. اجعل عرض `<img>` يبدّل إلى placeholder عند حدث `error` في الواجهة.
- [ ] **Step 4: شغّل الأخضر**: الأوامر نفسها؛ المتوقع صور صحيحة وعدم تبديلها بين النسخ، مع فحص صفحة Preview موبايل/ديسكتوب.
- [ ] **Step 5: Commit**: `git add app/Services/CartImport/Browser/SheinImportedItemCleaner.php scripts/shein-shared-page-import.mjs resources/views/carts/preview.blade.php tests/Unit/SheinImportedItemCleanerTest.php tests/static/shein-shared-image-identity.test.mjs && git commit -m "fix: recover only matching SHEIN variant images"`.

### Task 6: OneLink والانتظار والمهلة والتشخيص الواضح

**Files:**
- Modify: `app/Services/CartImport/CartImportService.php`؛ `app/Services/CartImport/Adapters/SheinShareAdapter.php`؛ `app/Services/CartImport/Browser/SheinBrowserImporter.php`؛ `scripts/shein-shared-page-import.mjs`؛ `scripts/shein-shared-page-retry.mjs`؛ `app/Services/CartImport/Browser/SheinImportDiagnostics.php`؛ `tests/Unit/SheinUrlResolverTest.php`؛ `tests/static/shein-shared-retry-v1019.test.mjs`
- Create: `tests/static/shein-shared-time-budget.test.mjs`

**Interfaces:** `SheinShareAdapter::importWithContext(string $resolvedUrl,string $sourceUrl,?Store $store=null): ImportResult` يحافظ على الأصل عند OneLink؛ `import(...)` القائم يناديه بالقيمتين نفسيهما للتوافق مع `CartSourceAdapter`. `SheinBrowserImporter::runWorker(...,array $browserOptions=[]): array` يمرر `locale/viewport/userAgent/timeoutMs` إلى العامل نفسه، مع Trace ID واحد للمحاولتين.

- [ ] **Step 1: اكتب اختبارات فاشلة**: OneLink بعد إعادة التوجيه يحتفظ بسياق المشاركة ولا يخرج خارج نطاق SHEIN؛ لو DOM صفر والـshare response مثبتة يتابع مسار المصدر المثبت؛ challenge → `challenge`؛ worker timeout أو exit=1 → سبب واضح؛ mobile/desktop تختلف فعلًا من غير فتح Desktop مرتين؛ لا تتجاوز ميزانية المتصفح `50_000ms` قبل مهلة HTTP.
- [ ] **Step 2: شغّل الأحمر**: `node --test tests/static/shein-shared-retry-v1019.test.mjs tests/static/shein-shared-time-budget.test.mjs` و`php artisan test tests/Unit/SheinUrlResolverTest.php`؛ الفشل المتوقع في تكرار الـpreflight أو عدم تصنيف timeout.
- [ ] **Step 3: نفّذ المحاولة الواحدة لكل بيئة**: mobile بحد `25_000ms` ثم desktop بما تبقى وبحد `20_000ms`؛ حد أقصى `50_000ms` لمرحلة المتصفح مع هامش التشغيل. انتظر ظهور دليل مشاركة/استجابة متعلقة بها بدل `networkidle` وحده؛ ألغِ `spawnSync`/متصفح الـpreflight الإضافي. ميّز timeout عن تعذّر القراءة وعن التحقق الأمني، ولا تعِد تشغيل الـlegacy importer الذي يختلق محتوى.
- [ ] **Step 4: طبّق التشخيص الآمن**: سجل `share_response_seen`، وعدد عناصر المشاركة المثبتة، والأسعار المؤكدة/المفقودة، و`failure_reason` ضمن allowlist؛ لا URL كامل ولا response body ولا stderr raw في Railway.
- [ ] **Step 5: شغّل الأخضر**: أوامر Step 2 مع `php tests/static/shein-import-diagnostics-v1021.php`؛ المتوقع حالة الخطأ الصحيحة والمهلة المحددة، وسلامة مسار OneLink.
- [ ] **Step 6: Commit**: `git add app/Services/CartImport scripts/shein-shared-page-import.mjs scripts/shein-shared-page-retry.mjs tests/Unit/SheinUrlResolverTest.php tests/static/shein-shared-retry-v1019.test.mjs tests/static/shein-shared-time-budget.test.mjs && git commit -m "fix: bounded SHEIN retry and actionable diagnostics"`.

### Task 7: مراجعة كاملة وإثبات حي ووثائق التسليم

**Files:**
- Create: `docs/shein/import-verification.md`؛ `docs/shein/operator-troubleshooting.md`
- Update: `docs/shein/fixture-manifest.md`؛ ملفات اختبارات Task 1–6 عند اكتشاف فجوات مثبتة فقط.

**Interfaces:** لا تغيير عام في PHP/Node. معيار الإطلاق = اختبارات نظيفة + رابط حي مثبت + حاجز الحفظ في قاعدة البيانات.

- [ ] **Step 1: نفّذ اختبارات الوحدة/الميزة** بقاعدة `salltak_test` المعزولة (لا Production): `composer test`. نفّذ `node --test tests/static/*.test.mjs`، و`php -l` لكل ملف PHP تغيّر، و`node --check` لكل ملف JS تغيّر، و`vendor/bin/pint --test`. أي اختبار قديم يعتمد على extractor غير آمن يُحدّث ليتحقق من السلوك المعتمد، ولا يُحذف بصمت.
- [ ] **Step 2: راجع فرع الإصلاح كاملًا** مع reviewer مستقل عندما تتوفر وسيلته؛ عالج الفجوات، ثم أعد الأوامر الكاملة. سجل أسماء الاختبارات ونتائجها بلا ادعاءات غير مثبتة.
- [ ] **Step 3: جرّب بيئة staging معزولة**، ثم اطلب الإذن قبل نشر production إن لم يكن مخوّلًا صراحة. بعد نجاح النشر قارن رابطًا سبق أن فشل ورابطًا سبق أن عمل: نفس السوق والنسخ والوقت وعدد المشاركة، صورة/لون/مقاس/كمية وسعر USD لكل عنصر. اختبر رفض POST للحفظ الجزئي فعليًا. لا تُسجّل روابط مشاركة أو بيانات العملاء في الوثائق العامة.
- [ ] **Step 4: وثّق النتائج** في `docs/shein/import-verification.md` مع حالة كل اختبار وحدود الفشل الخارجي؛ وثّق خطوات تشخيص Trace ID والمهلة والحالات الآمنة في `docs/shein/operator-troubleshooting.md`.
- [ ] **Step 5: Commit**: `git add docs/shein && git commit -m "docs: verify SHEIN shared-cart reliability"`. إن فشل دليل مشاركة حي أو بقيت حالة حماية لا يمكن قراءتها، صنّفها بوضوح `blocked` ولا تعلن الإصلاح منتهيًا.

## Implementation Stop Gates

- **بعد Task 1:** دون Fixture منقّحة تُثبت ربط مصدر الشبكة بقائمة المشاركة، لا تبدأ Task 2–3؛ اطلب دليلًا إضافيًا بدل اختلاق JSON أو endpoint.
- **بعد Task 3:** لا تدمج السعر في أي UI قبل نجاح اختبارات SKU/السوق والتعارض.
- **بعد Task 4:** لا تسمح لأي عميل بحفظ سلة SHEIN جزئية حتى لو أزال السطر غير المؤكد من DOM.
- **قبل production:** لا يكفي نجاح Railway أو Node tests؛ يلزم رابط حقيقي ومقارنة منتجات وأسعار مستقلة مع SHEIN في سياق متماثل.
