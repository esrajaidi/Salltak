<?php $__env->startSection('title','سلاتي'); ?>
<?php $__env->startSection('body'); ?>
<section class="page-section">
    <div class="container">
        <div class="d-flex flex-column flex-md-row align-items-md-end justify-content-between gap-3 mb-4">
            <div><div class="page-kicker">حسابي</div><h1 class="page-heading">سلاتي</h1><p class="page-subtitle">السلات التي حفظتها، مرتبة من الأحدث للأقدم.</p></div>
            <a class="btn btn-primary" href="<?php echo e(route('carts.create')); ?>">+ سلة جديدة</a>
        </div>

        <div class="row g-3 g-lg-4">
            <?php $__empty_1 = true; $__currentLoopData = $carts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cart): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <?php ($statusClass = $cart->status === 'cancelled' ? 'status-danger' : ($cart->status === 'confirmed' ? 'status-success' : 'status-primary')); ?>
                <div class="col-md-6 col-xl-4">
                    <article class="surface-card cart-card">
                        <div class="d-flex justify-content-between align-items-center gap-2 mb-3"><span class="cart-number ltr"><?php echo e($cart->number); ?></span><span class="status-badge <?php echo e($statusClass); ?>"><?php echo e($cart->status); ?></span></div>
                        <div class="d-flex align-items-center justify-content-between gap-3 mb-1"><div class="fw-bold"><?php echo e($cart->store?->name ?? $cart->source_host ?? 'موقع خارجي'); ?></div><span class="small fw-bold text-primary"><?php echo e($cart->source_currency); ?></span></div>
                        <div class="small text-secondary mb-4"><?php echo e($cart->items_count); ?> منتج • <?php echo e($cart->created_at->format('Y-m-d H:i')); ?></div>
                        <div class="small text-secondary">إجمالي السلة</div>
                        <div class="cart-total mb-3"><?php echo e(number_format((float)$cart->total_lyd,2)); ?> <small class="fs-6">د.ل</small></div>
                        <a class="btn btn-soft w-100" href="<?php echo e(route('carts.show',$cart)); ?>">عرض السلة</a>
                    </article>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="col-12"><div class="surface-card empty-state"><div class="empty-state-icon">س</div><h2 class="h4 fw-bold">ما عندكش سلات محفوظة</h2><p class="text-secondary">ألصق رابط أول سلة، راجع المنتجات والأسعار، وبعدها احفظها في حسابك.</p><a class="btn btn-primary" href="<?php echo e(route('carts.create')); ?>">أضف أول سلة</a></div></div>
            <?php endif; ?>
        </div>
        <div class="mt-4"><?php echo e($carts->links()); ?></div>
    </div>
</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\laragon\www\Salltak\resources\views/carts/index.blade.php ENDPATH**/ ?>