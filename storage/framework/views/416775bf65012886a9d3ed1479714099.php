<?php $__env->startSection('title','سلتك — Salltak'); ?>
<?php $__env->startSection('body'); ?>
<section class="hero">
    <div class="container">
        <div class="row align-items-center g-4 g-lg-5">
            <div class="col-lg-7">
                <span class="eyebrow mb-3">سلة واحدة • سعر واضح بالدينار الليبي</span>
                <h1 class="hero-title mb-3">رابط سلتك يكفي.<br class="d-none d-md-block"> والباقي علينا.</h1>
                <p class="hero-copy mb-4">الصق رابط مشاركة السلة من SHEIN، راجع المنتجات الحقيقية في سلتك، وشوف السعر الأصلي والسعر بالدينار الليبي جنب بعض قبل ما تحفظها في حسابك.</p>
                <div class="d-flex flex-column flex-sm-row gap-2">
                    <?php if(auth()->guard()->check()): ?>
                        <a class="btn btn-primary btn-lg px-4" href="<?php echo e(auth()->user()->isAdmin() ? route('admin.dashboard') : route('carts.create')); ?>"><?php echo e(auth()->user()->isAdmin() ? 'لوحة الإدارة' : 'أضف سلتك الآن'); ?></a>
                        <?php if (! (auth()->user()->isAdmin())): ?><a class="btn btn-outline-primary btn-lg px-4" href="<?php echo e(route('carts.index')); ?>">سلاتي المحفوظة</a><?php endif; ?>
                    <?php else: ?>
                        <a class="btn btn-primary btn-lg px-4" href="<?php echo e(route('register')); ?>">ابدأ مجانًا</a>
                        <a class="btn btn-outline-primary btn-lg px-4" href="<?php echo e(route('login')); ?>">تسجيل الدخول</a>
                    <?php endif; ?>
                </div>
                <div class="d-flex flex-wrap gap-3 mt-4 small text-secondary">
                    <span>✓ جلب منتجات السلة</span><span>✓ تحويل تلقائي إلى LYD</span><span>✓ حفظ السلة</span>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="hero-panel">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div><div class="small text-secondary">كيف تشتغل؟</div><h2 class="h5 fw-bold mb-0 mt-1">من الرابط إلى سلة مرتبة</h2></div>
                        <span class="brand-mark">س</span>
                    </div>
                    <div class="step-item"><span class="step-number">1</span><div><strong>الصق رابط السلة</strong><div class="small text-secondary mt-1">استخدم رابط مشاركة السلة من SHEIN.</div></div></div>
                    <div class="step-item"><span class="step-number">2</span><div><strong>شوف منتجاتك</strong><div class="small text-secondary mt-1">الصورة، الاسم، اللون، المقاس، السعر والكمية.</div></div></div>
                    <div class="step-item"><span class="step-number">3</span><div><strong>اعرف السعر بالدينار</strong><div class="small text-secondary mt-1">السعر الأصلي وLYD ظاهرين جنب بعض.</div></div></div>
                    <div class="step-item"><span class="step-number">4</span><div><strong>احفظ السلة</strong><div class="small text-secondary mt-1">وارجع لها في أي وقت من حسابك.</div></div></div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section-space pt-4">
    <div class="container">
        <div class="row align-items-end g-3 mb-4">
            <div class="col-md"><div class="page-kicker">المواقع المدعومة</div><h2 class="h3 section-title mb-0">تسوّق من المواقع المفعّلة</h2></div>
            <div class="col-md-auto"><div class="d-flex flex-wrap gap-2 justify-content-md-end"><?php $__empty_1 = true; $__currentLoopData = $stores; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $store): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><span class="store-chip"><strong><?php echo e($store->name); ?></strong><span class="small text-secondary"><?php echo e($store->currency); ?></span></span><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><span class="text-secondary">لم تتم إضافة مواقع بعد.</span><?php endif; ?></div></div>
        </div>
        <div class="row g-3 g-lg-4">
            <div class="col-md-4"><div class="soft-card feature-card"><div class="feature-icon">01</div><h3 class="h5 fw-bold">رابط واحد</h3><p class="text-secondary mb-0 lh-lg">الصق رابط مشاركة السلة، والنظام يرتب المنتجات في شاشة واحدة بدون تشتيت.</p></div></div>
            <div class="col-md-4"><div class="soft-card feature-card"><div class="feature-icon">LYD</div><h3 class="h5 fw-bold">سعرين واضحين</h3><p class="text-secondary mb-0 lh-lg">السعر الأصلي وسعره بالدينار الليبي ظاهرين جنب بعض لكل منتج.</p></div></div>
            <div class="col-md-4"><div class="soft-card feature-card"><div class="feature-icon">✓</div><h3 class="h5 fw-bold">سلة محفوظة</h3><p class="text-secondary mb-0 lh-lg">عدّل الكمية، راجع الإجمالي، واحفظ السلة في حسابك للرجوع إليها لاحقًا.</p></div></div>
        </div>
    </div>
</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\laragon\www\Salltak\resources\views/home.blade.php ENDPATH**/ ?>