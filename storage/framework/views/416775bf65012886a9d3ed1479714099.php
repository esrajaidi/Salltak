<?php $__env->startSection('title','سلتك — Salltak'); ?>
<?php $__env->startSection('body'); ?>
<section class="landing-hero">
    <span class="hero-orb one float-soft"></span><span class="hero-orb two float-soft-delay"></span><span class="hero-orb three pulse-soft"></span>
    <div class="container position-relative">
        <div class="row align-items-center g-5">
            <div class="col-lg-7 reveal is-visible">
                <span class="eyebrow mb-3">✦ سلتك — عالم التسوق بين يديك</span>
                <h1 class="hero-title mb-3">استوردي سلتك من SHEIN وشوفي السعر <span class="accent">بالدولار والدينار</span></h1>
                <p class="hero-copy mb-4">الصقي رابط مشاركة السلة، وسلتك تجيب المنتجات الحقيقية بصورها ومقاساتها وألوانها، تعرض السعر بالدولار USD وتحوله تلقائياً إلى الدينار الليبي حسب سعر الصرف عندك.</p>
                <div class="d-flex flex-column flex-sm-row gap-2">
                    <?php if(auth()->guard()->check()): ?>
                        <a class="btn btn-primary btn-lg px-4" href="<?php echo e(in_array(auth()->user()->role, ['admin','order_manager'], true) ? route('admin.dashboard') : route('carts.create')); ?>"><?php echo e(in_array(auth()->user()->role, ['admin','order_manager'], true) ? 'افتح لوحة الإدارة' : 'استورد سلتك الآن'); ?> ←</a>
                        <?php if (! (in_array(auth()->user()->role, ['admin','order_manager'], true))): ?><a class="btn btn-navy btn-lg px-4" href="<?php echo e(route('carts.index')); ?>">سلاتي المحفوظة</a><?php endif; ?>
                    <?php else: ?>
                        <a class="btn btn-primary btn-lg px-4" href="<?php echo e(route('register')); ?>">ابدئي الآن ←</a>
                        <a class="btn btn-navy btn-lg px-4" href="#how-it-works">شاهد كيف تعمل</a>
                    <?php endif; ?>
                </div>
                <div class="trust-row mt-4"><span><i class="trust-dot"></i> بدون تعقيد</span><span><i class="trust-dot"></i> USD → LYD</span><span><i class="trust-dot"></i> منتجات السلة فقط</span><span><i class="trust-dot"></i> متوافق مع الموبايل</span></div>
            </div>
            <div class="col-lg-5 reveal reveal-delay-1 is-visible">
                <div class="hero-visual" aria-label="معاينة واجهة سلتك">
                    <div class="browser-mock">
                        <div class="browser-bar"><i class="browser-dot"></i><i class="browser-dot"></i><i class="browser-dot"></i><span class="ms-2 small text-secondary">Salltak Dashboard</span></div>
                        <div class="mock-body">
                            <div class="mock-sidebar"><div class="mock-logo"></div><div class="mock-nav-line active"></div><div class="mock-nav-line"></div><div class="mock-nav-line"></div><div class="mock-nav-line"></div></div>
                            <div class="mock-content">
                                <div class="mock-kpis"><div class="mock-kpi"><span>إجمالي السلات</span><strong>35</strong></div><div class="mock-kpi"><span>العملة</span><strong>USD</strong></div><div class="mock-kpi"><span>التحويل</span><strong>LYD</strong></div></div>
                                <div class="mock-grid"><div class="mock-chart"><div class="small fw-bold">ملخص السلة</div><div class="mock-bars"><i style="height:42%"></i><i style="height:68%"></i><i style="height:53%"></i><i style="height:88%"></i><i style="height:64%"></i><i style="height:78%"></i></div></div><div class="mock-list"><div class="small fw-bold mb-2">المنتجات</div><div class="mock-list-line"></div><div class="mock-list-line"></div><div class="mock-list-line"></div><div class="mock-list-line"></div></div></div>
                            </div>
                        </div>
                    </div>
                    <div class="phone-mock float-soft"><div class="phone-screen"><div class="phone-notch"></div><div class="phone-brand">سلتك Salltak</div><div class="phone-card"><span>عدد المنتجات</span><strong>35</strong></div><div class="phone-card"><span>السعر الأصلي</span><strong>$9.85</strong></div><div class="phone-card"><span>بالدينار</span><strong>د.ل</strong></div></div></div>
                    <span class="floating-store shein float-soft-delay">SHEIN</span><span class="floating-store usd float-soft">USD $</span><span class="floating-store lyd pulse-soft">LYD د.ل</span>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="how-it-works" class="section-space how-it-works">
    <div class="container">
        <div class="text-center mb-4 reveal"><div class="page-kicker">كيف تعمل المنصة؟</div><h2 class="h2 section-title">أربع خطوات، من الرابط إلى سلة مرتبة</h2><p class="page-subtitle mx-auto" style="max-width:680px">ما تحتاجيش تنقلي المنتجات يدويًا. سلتك ترتب لك التفاصيل وتخلي المراجعة واضحة.</p></div>
        <div class="row g-3 g-lg-4">
            <div class="col-sm-6 col-lg-3 reveal"><article class="step-card" data-step="01"><div class="step-icon">🔗</div><h3 class="h5 fw-bold">الصقي رابط السلة</h3><p class="text-secondary mb-0 lh-lg">رابط Share Cart من SHEIN يكفي لبدء الاستيراد.</p></article></div>
            <div class="col-sm-6 col-lg-3 reveal reveal-delay-1"><article class="step-card" data-step="02"><div class="step-icon">▦</div><h3 class="h5 fw-bold">استيراد المنتجات</h3><p class="text-secondary mb-0 lh-lg">الاسم، الصورة، اللون، المقاس، SKU والسعر.</p></article></div>
            <div class="col-sm-6 col-lg-3 reveal reveal-delay-2"><article class="step-card" data-step="03"><div class="step-icon">$</div><h3 class="h5 fw-bold">السعر بالدولار</h3><p class="text-secondary mb-0 lh-lg">نقرأ USD من بيانات SHEIN مباشرة، ثم نحسب LYD.</p></article></div>
            <div class="col-sm-6 col-lg-3 reveal reveal-delay-3"><article class="step-card" data-step="04"><div class="step-icon">✓</div><h3 class="h5 fw-bold">راجعي واحفظي</h3><p class="text-secondary mb-0 lh-lg">عدلي الكمية، شوفي الإجمالي واحفظي السلة في حسابك.</p></article></div>
        </div>
    </div>
</section>

<section class="section-space pt-0">
    <div class="container">
        <div class="platform-preview reveal">
            <div class="row align-items-center g-4 g-lg-5 position-relative">
                <div class="col-lg-5"><span class="preview-badge mb-3">واجهة واضحة • بيانات حقيقية</span><h2 class="h2 section-title">كل منتج قدامك بالتفاصيل اللي تهمك</h2><p class="mb-4" style="color:#C8D8E3;line-height:1.95">بدل ما تضيع المعلومات بين صفحات المتجر، سلتك تجمعها في شاشة مراجعة واحدة وتخلي السعر الأصلي وسعر الدينار جنب بعض.</p><div class="d-flex flex-wrap gap-2"><span class="preview-badge">اللون والمقاس</span><span class="preview-badge">صور المنتجات</span><span class="preview-badge">تعديل الكمية</span><span class="preview-badge">إجمالي مباشر</span></div></div>
                <div class="col-lg-7"><div class="preview-panel" id="platform-preview"><div class="preview-row"><div class="preview-thumb">IMG</div><div><strong class="d-block">منتج SHEIN</strong><small>الأسود / XL • SKU محفوظ</small></div><div class="preview-price">$9.85<small class="d-block">+ د.ل</small></div></div><div class="preview-row"><div class="preview-thumb">IMG</div><div><strong class="d-block">منتج بمقاس ولون</strong><small>رمادي / 1XL • الكمية 1</small></div><div class="preview-price">$22.37<small class="d-block">+ د.ل</small></div></div><div class="preview-row mb-0"><div class="preview-thumb">IMG</div><div><strong class="d-block">منتج متعدد الخيارات</strong><small>متعدد الألوان / 40-43</small></div><div class="preview-price">$4.02<small class="d-block">+ د.ل</small></div></div></div></div>
            </div>
        </div>
    </div>
</section>

<section class="section-space landing-features pt-0">
    <div class="container">
        <div class="row align-items-end g-3 mb-4 reveal"><div class="col-md"><div class="page-kicker">مميزات سلتك</div><h2 class="h2 section-title mb-0">مصممة لتخلي التسوق العالمي أبسط</h2></div><div class="col-md-auto"><span class="store-chip"><strong>Responsive</strong><span class="small text-secondary">كل الأجهزة</span></span></div></div>
        <div class="row g-3 g-lg-4">
            <div class="col-md-6 col-xl-3 reveal"><div class="surface-card feature-card"><div class="feature-icon">USD</div><h3 class="h5 fw-bold">تحويل واضح</h3><p class="text-secondary mb-0 lh-lg">عرض USD أولاً ثم تحويله للدينار حسب السعر المحدد في النظام.</p></div></div>
            <div class="col-md-6 col-xl-3 reveal reveal-delay-1"><div class="surface-card feature-card"><div class="feature-icon">35</div><h3 class="h5 fw-bold">السلة الحقيقية</h3><p class="text-secondary mb-0 lh-lg">نستبعد التوصيات والإعلانات ونركز على المنتجات الموجودة في السلة المشتركة.</p></div></div>
            <div class="col-md-6 col-xl-3 reveal reveal-delay-2"><div class="surface-card feature-card"><div class="feature-icon">RTL</div><h3 class="h5 fw-bold">واجهة عربية</h3><p class="text-secondary mb-0 lh-lg">تصميم RTL مريح وواضح على الكمبيوتر والتابلت والهاتف.</p></div></div>
            <div class="col-md-6 col-xl-3 reveal reveal-delay-3"><div class="surface-card feature-card"><div class="feature-icon">⚡</div><h3 class="h5 fw-bold">تجربة سريعة</h3><p class="text-secondary mb-0 lh-lg">واجهة نظيفة، حالات تحميل واضحة، وتفاعل بصري بدون مكتبات ثقيلة.</p></div></div>
        </div>
    </div>
</section>

<section class="section-space supported-stores pt-0">
    <div class="container">
        <div class="surface-card p-4 p-lg-5 reveal">
            <div class="row align-items-center g-4"><div class="col-lg"><div class="page-kicker">المواقع المفعّلة</div><h2 class="h3 section-title">المتاجر والعملات من لوحة التحكم</h2><p class="page-subtitle">أي متجر مفعّل يظهر هنا مباشرة بدون تعديل الصفحة يدويًا.</p></div><div class="col-lg-auto"><div class="d-flex flex-wrap gap-2 justify-content-lg-end"><?php $__empty_1 = true; $__currentLoopData = $stores; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $store): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><span class="store-chip"><strong><?php echo e($store->name); ?></strong><span class="small text-secondary"><?php echo e($store->currency); ?></span></span><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><span class="text-secondary">لم تتم إضافة مواقع بعد.</span><?php endif; ?></div></div></div>
        </div>
    </div>
</section>

<section class="section-space pt-0">
    <div class="container">
        <div class="landing-cta reveal" id="landing-cta">
            <div class="row align-items-center g-4 position-relative" style="z-index:1"><div class="col-auto"><div class="cta-icon">س</div></div><div class="col"><div class="page-kicker">ابدئي من رابط واحد</div><h2 class="h2 section-title mb-2">خلي سلتك العالمية أوضح وأسهل</h2><p class="page-subtitle mb-0">استوردي، راجعي، حوّلي السعر واحفظي — في مكان واحد.</p></div><div class="col-lg-auto"><?php if(auth()->guard()->check()): ?><a class="btn btn-primary btn-lg" href="<?php echo e(in_array(auth()->user()->role, ['admin','order_manager'], true) ? route('admin.dashboard') : route('carts.create')); ?>">ابدئي الآن ←</a><?php else: ?><a class="btn btn-primary btn-lg" href="<?php echo e(route('register')); ?>">إنشاء حساب مجاني ←</a><?php endif; ?></div></div>
        </div>
    </div>
</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\laragon\www\Salltak\resources\views/home.blade.php ENDPATH**/ ?>