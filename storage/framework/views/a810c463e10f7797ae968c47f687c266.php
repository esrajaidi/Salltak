<?php $__env->startSection('title','سلة جديدة'); ?>
<?php $__env->startSection('body'); ?>
<section class="page-section customer-page">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-9 col-xxl-8">
                <div class="d-flex flex-column flex-md-row align-items-md-end justify-content-between gap-3 mb-4 reveal is-visible">
                    <div><div class="page-kicker">إضافة سلة</div><h1 class="page-heading">ألصق رابط سلتك</h1><p class="page-subtitle">استخدم رابط مشاركة السلة من SHEIN، وسنحاول جلب العناصر الحقيقية وعرض سعرها بالدينار الليبي.</p></div>
                    <a class="btn btn-ghost" href="<?php echo e(route('carts.index')); ?>">سلاتي</a>
                </div>

                <div class="surface-card-elevated reveal p-3 p-md-4 p-lg-5">
                    <form method="POST" action="<?php echo e(route('carts.analyze')); ?>" id="analyzeForm">
                        <?php echo csrf_field(); ?>
                        <label class="form-label" for="source_url">رابط مشاركة السلة</label>
                        <div class="input-group input-group-lg flex-column flex-sm-row gap-2 gap-sm-0">
                            <input id="source_url" class="form-control ltr <?php $__errorArgs = ['source_url'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" type="url" name="source_url" value="<?php echo e(old('source_url')); ?>" placeholder="https://m.shein.com/ar/cart/share/landing?..." required>
                            <button class="btn btn-primary px-4" type="submit"><span class="submit-label">جلب السلة</span><span class="spinner-border spinner-border-sm d-none" aria-hidden="true"></span></button>
                        </div>
                        <div class="form-text mt-2">الصق رابط <strong>Share Cart</strong> وليس رابط صفحة السلة العادي.</div>
                    </form>

                    <div class="row g-3 mt-4">
                        <div class="col-sm-4"><div class="summary-tile"><div class="summary-label">1. الصق الرابط</div><div class="summary-value fs-6">رابط مشاركة SHEIN</div></div></div>
                        <div class="col-sm-4"><div class="summary-tile"><div class="summary-label">2. راجع العناصر</div><div class="summary-value fs-6">الصورة والسعر والكمية</div></div></div>
                        <div class="col-sm-4"><div class="summary-tile"><div class="summary-label">3. احفظ</div><div class="summary-value fs-6">السلة في حسابك</div></div></div>
                    </div>

                    <div class="alert alert-light border mt-4 mb-4 small text-secondary">
                        <strong class="text-dark">ملاحظة:</strong> تشغيل الاستيراد يتم في الخلفية. لو SHEIN منع القراءة أو طلب تحققًا أمنيًا، سيظهر لك تنبيه واضح بدون إضافة منتجات غير موجودة في سلتك.
                    </div>

                    <div><div class="small fw-bold text-secondary mb-2">المواقع المفعّلة</div><div class="d-flex flex-wrap gap-2"><?php $__currentLoopData = $stores; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $store): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><span class="store-chip"><strong><?php echo e($store->name); ?></strong><span class="small text-secondary"><?php echo e($store->currency); ?></span></span><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></div></div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php $__env->stopSection(); ?>
<?php $__env->startPush('scripts'); ?>
<script>
document.getElementById('analyzeForm')?.addEventListener('submit', function(){
    const btn=this.querySelector('button[type="submit"]');
    btn.disabled=true; btn.querySelector('.submit-label').textContent='جاري جلب السلة...'; btn.querySelector('.spinner-border').classList.remove('d-none');
});
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\laragon\www\Salltak\resources\views/carts/create.blade.php ENDPATH**/ ?>