<?php $__env->startSection('title','طلباتي'); ?>
<?php $__env->startSection('body'); ?>
<section class="page-section customer-page">
<div class="container">
    <div class="d-flex flex-column flex-md-row align-items-md-end justify-content-between gap-3 mb-4 reveal is-visible">
        <div><div class="page-kicker">متابعة الشراء</div><h1 class="page-heading">طلباتي</h1><p class="page-subtitle">تابع المراجعة، العربون، الدفعات، الشراء والشحن حتى التسليم.</p></div>
        <a class="btn btn-primary" href="<?php echo e(route('carts.index')); ?>">اختيار سلة محفوظة</a>
    </div>

    <?php ($labels = [
        'submitted'=>'تم الإرسال','under_review'=>'تحت المراجعة','needs_customer_action'=>'يحتاج ردك','approved'=>'معتمد',
        'awaiting_deposit'=>'بانتظار العربون','awaiting_payment'=>'بانتظار الدفع','deposit_paid'=>'العربون مدفوع','purchasing'=>'جاري الشراء',
        'ordered'=>'تم الطلب من المتجر','shipped'=>'جاري الشحن','arrived_libya'=>'وصل ليبيا','awaiting_balance'=>'بانتظار باقي المبلغ',
        'ready_for_delivery'=>'جاهز للتسليم','out_for_delivery'=>'خرج للتسليم','delivered'=>'تم التسليم','rejected'=>'مرفوض','cancelled'=>'ملغي'
    ]); ?>
    <div class="row g-3 g-lg-4">
        <?php $__empty_1 = true; $__currentLoopData = $orders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php ($danger = in_array($order->status,['rejected','cancelled'],true)); ?>
            <?php ($success = in_array($order->status,['delivered','ready_for_delivery'],true)); ?>
            <div class="col-md-6 col-xl-4">
                <article class="surface-card order-card reveal h-100">
                    <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                        <span class="cart-number ltr"><?php echo e($order->number); ?></span>
                        <span class="status-badge <?php echo e($danger?'status-danger':($success?'status-success':'status-primary')); ?>"><?php echo e($labels[$order->status] ?? $order->status); ?></span>
                    </div>
                    <div class="small text-secondary mb-1"><?php echo e($order->cart?->store?->name ?? 'سلة تسوق'); ?> • <?php echo e($order->items_count); ?> منتج</div>
                    <div class="order-money-grid my-3">
                        <div><span>الإجمالي</span><strong><?php echo e(number_format((float)$order->total_lyd,2)); ?> د.ل</strong></div>
                        <div><span>المدفوع</span><strong><?php echo e(number_format((float)$order->paid_amount,2)); ?> د.ل</strong></div>
                        <div><span>المتبقي</span><strong><?php echo e(number_format((float)$order->remaining_amount,2)); ?> د.ل</strong></div>
                    </div>
                    <?php if($order->assignee): ?><div class="small text-secondary mb-3">المسؤول: <strong class="text-dark"><?php echo e($order->assignee->name); ?></strong></div><?php endif; ?>
                    <a class="btn btn-soft w-100 mt-auto" href="<?php echo e(route('orders.show',$order)); ?>">عرض ومتابعة الطلب</a>
                </article>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div class="col-12"><div class="surface-card empty-state"><div class="empty-state-icon">ط</div><h2 class="h4 fw-bold">ما عندكش طلبات بعد</h2><p class="text-secondary">احفظ سلة أولًا، وبعدها اضغط «اطلب هذه السلة» لإرسالها للمراجعة.</p><a class="btn btn-primary" href="<?php echo e(route('carts.index')); ?>">الذهاب إلى سلاتي</a></div></div>
        <?php endif; ?>
    </div>
    <div class="mt-4"><?php echo e($orders->links()); ?></div>
</div>
</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\laragon\www\Salltak\resources\views/orders/index.blade.php ENDPATH**/ ?>