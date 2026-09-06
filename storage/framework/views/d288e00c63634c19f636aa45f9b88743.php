<?php $__env->startSection('title',$cart->number); ?>
<?php $__env->startSection('body'); ?>
<section class="page-section">
    <div class="container">
        <?php ($statusClass = $cart->status === 'cancelled' ? 'status-danger' : ($cart->status === 'confirmed' ? 'status-success' : 'status-primary')); ?>
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
            <div><div class="page-kicker">تفاصيل السلة</div><h1 class="page-heading"><?php echo e($cart->number); ?></h1><p class="page-subtitle"><?php echo e($cart->store?->name ?? $cart->source_host); ?> • <?php echo e($cart->created_at->format('Y-m-d H:i')); ?></p></div>
            <span class="status-badge <?php echo e($statusClass); ?> align-self-start align-self-md-center"><?php echo e($cart->status); ?></span>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3"><div class="summary-tile h-100"><div class="summary-label">عدد المنتجات</div><div class="summary-value"><?php echo e($cart->items->count()); ?></div></div></div>
            <div class="col-6 col-lg-3"><div class="summary-tile h-100"><div class="summary-label">سعر الصرف</div><div class="summary-value"><span class="ltr">1 <?php echo e($cart->source_currency); ?></span> = <?php echo e(number_format((float)$cart->exchange_rate,4)); ?> د.ل</div></div></div>
            <div class="col-6 col-lg-3"><div class="summary-tile h-100"><div class="summary-label">الإجمالي الأصلي</div><div class="summary-value ltr text-end"><?php echo e(number_format((float)$cart->subtotal_original,2)); ?> <?php echo e($cart->source_currency); ?></div></div></div>
            <div class="col-6 col-lg-3"><div class="summary-tile is-primary h-100"><div class="summary-label">الإجمالي بالدينار</div><div class="summary-value text-primary fs-5"><?php echo e(number_format((float)$cart->total_lyd,2)); ?> د.ل</div></div></div>
        </div>

        <div class="surface-card-elevated p-3 p-md-4">
            <div class="d-flex align-items-center justify-content-between gap-3 mb-2">
                <div><h2 class="h5 fw-bold mb-1">منتجات السلة</h2><div class="small text-secondary">السعر الأصلي والسعر المحول محفوظان حسب سعر الصرف وقت إنشاء السلة.</div></div>
            </div>

            <div class="mt-2">
                <?php $__empty_1 = true; $__currentLoopData = $cart->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php ($unitLyd = (float)$item->unit_price_original * (float)$cart->exchange_rate); ?>
                    <?php ($lineLyd = (float)$item->line_total_original * (float)$cart->exchange_rate); ?>
                    <article class="saved-product">
                        <?php if($item->image_url): ?>
                            <img class="saved-product-image" src="<?php echo e($item->image_url); ?>" alt="<?php echo e($item->name); ?>" loading="lazy">
                        <?php else: ?>
                            <div class="saved-product-image product-image-placeholder">بدون صورة</div>
                        <?php endif; ?>
                        <div class="min-w-0">
                            <div class="saved-product-title"><?php echo e($item->name); ?></div>
                            <div class="saved-product-meta d-flex flex-wrap gap-2">
                                <?php if($item->color): ?><span>اللون: <strong><?php echo e($item->color); ?></strong></span><?php endif; ?>
                                <?php if($item->size): ?><span>المقاس: <strong><?php echo e($item->size); ?></strong></span><?php endif; ?>
                                <?php if($item->variant): ?><span>الخيار: <strong><?php echo e($item->variant); ?></strong></span><?php endif; ?>
                            </div>
                            <div class="small text-secondary mt-2">الكمية: <strong><?php echo e($item->quantity); ?></strong> • إجمالي المنتج: <span class="ltr d-inline-block"><?php echo e(number_format((float)$item->line_total_original,2)); ?> <?php echo e($item->currency); ?></span> / <strong class="text-primary"><?php echo e(number_format($lineLyd,2)); ?> د.ل</strong></div>
                        </div>
                        <div class="saved-product-prices">
                            <div class="small text-secondary">سعر القطعة</div>
                            <div class="original ltr"><?php echo e(number_format((float)$item->unit_price_original,2)); ?> <?php echo e($item->currency); ?></div>
                            <div class="lyd"><?php echo e(number_format($unitLyd,2)); ?> د.ل</div>
                        </div>
                    </article>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <div class="empty-state py-5"><div class="empty-state-icon">—</div><h3 class="h5 fw-bold">لا توجد منتجات</h3><p class="text-secondary mb-0">هذه السلة لا تحتوي على عناصر محفوظة.</p></div>
                <?php endif; ?>
            </div>

            <div class="row g-3 mt-4 justify-content-end">
                <div class="col-lg-5"><div class="cart-total-card"><div class="d-flex justify-content-between gap-3 mb-2"><span class="summary-label">الإجمالي الأصلي</span><strong class="ltr"><?php echo e(number_format((float)$cart->subtotal_original,2)); ?> <?php echo e($cart->source_currency); ?></strong></div><div class="d-flex align-items-end justify-content-between gap-3"><span class="summary-label">الإجمالي بالدينار</span><strong class="lyd-grand"><?php echo e(number_format((float)$cart->total_lyd,2)); ?> د.ل</strong></div></div></div>
            </div>

            <div class="d-flex flex-column flex-sm-row gap-2 mt-4">
                <?php if($cart->status!=='cancelled'): ?><form method="POST" action="<?php echo e(route('carts.cancel',$cart)); ?>"><?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?><button class="btn btn-danger-soft btn-mobile-full" type="submit">إلغاء السلة</button></form><?php endif; ?>
                <form method="POST" action="<?php echo e(route('carts.destroy',$cart)); ?>" onsubmit="return confirm('حذف السلة نهائيًا؟')"><?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?><button class="btn btn-ghost btn-mobile-full" type="submit">حذف السلة</button></form>
                <a class="btn btn-outline-primary btn-mobile-full me-sm-auto" href="<?php echo e(route('carts.index')); ?>">الرجوع إلى سلاتي</a>
            </div>
        </div>
    </div>
</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\laragon\www\Salltak\resources\views/carts/show.blade.php ENDPATH**/ ?>