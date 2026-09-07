<?php $__env->startSection('title','لوحة الإدارة'); ?>
<?php $__env->startSection('admin-content'); ?>
<?php
$statusLabels=['submitted'=>'جديد','under_review'=>'تحت المراجعة','needs_customer_action'=>'يحتاج رد العميل','approved'=>'معتمد','awaiting_deposit'=>'بانتظار العربون','awaiting_payment'=>'بانتظار الدفع','deposit_paid'=>'العربون مدفوع','purchasing'=>'جاري الشراء','ordered'=>'تم الطلب','shipped'=>'الشحن','arrived_libya'=>'وصل ليبيا','awaiting_balance'=>'باقي المبلغ','ready_for_delivery'=>'جاهز للتسليم','out_for_delivery'=>'خارج للتسليم','delivered'=>'تم التسليم','rejected'=>'مرفوض','cancelled'=>'ملغي'];
?>
<div class="dashboard-command-center">
    <header class="dashboard-welcome mb-4">
        <div><div class="page-kicker">Operations Center</div><h1 class="h3 fw-black mb-1">صباح النشاط، <?php echo e(auth()->user()->name); ?> 👋</h1><p class="text-secondary mb-0">هذه أهم الحالات التي تحتاج انتباهك الآن، مع مراقبة حركة النظام لحظة بلحظة.</p></div>
        <div class="d-flex gap-2 flex-wrap"><a class="btn btn-primary" href="<?php echo e(route('admin.orders.index',['status'=>'submitted'])); ?>">الطلبات الجديدة</a><?php if(auth()->user()->role === 'admin'): ?><a class="btn btn-ghost" href="<?php echo e(route('admin.payment-methods.index')); ?>">طرق الدفع</a><?php endif; ?></div>
    </header>

    <div class="monitoring-grid mb-4">
        <div class="command-metric"><span class="command-icon">✓</span><div><small>كل الطلبات</small><strong><?php echo e(number_format($stats['orders'])); ?></strong><em><?php echo e(number_format($stats['pending_orders'])); ?> تحتاج متابعة</em></div></div>
        <div class="command-metric is-warning"><span class="command-icon">د</span><div><small>دفعات تنتظر التحقق</small><strong><?php echo e(number_format($stats['pending_payments'])); ?></strong><em>راجع الإثباتات قبل الاعتماد</em></div></div>
        <div class="command-metric is-success"><span class="command-icon">ل</span><div><small>مدفوع ومعتمد</small><strong><?php echo e(number_format((float)$stats['paid'],2)); ?></strong><em>د.ل إجمالي الدفعات</em></div></div>
        <div class="command-metric"><span class="command-icon">◎</span><div><small>العملاء</small><strong><?php echo e(number_format($stats['users'])); ?></strong><em><?php echo e(number_format($stats['delivered'])); ?> طلب مكتمل</em></div></div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xl-7">
            <section class="admin-panel p-3 p-md-4 h-100">
                <div class="d-flex justify-content-between align-items-center gap-2 mb-3"><div><div class="page-kicker">Priority Queue</div><h2 class="h5 panel-title mb-0">طلبات تحتاج تدخل</h2></div><a class="small fw-bold text-decoration-none" href="<?php echo e(route('admin.orders.index')); ?>">عرض الكل ←</a></div>
                <div class="dashboard-order-list"><?php $__empty_1 = true; $__currentLoopData = $needsAction; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><a href="<?php echo e(route('admin.orders.show',$order)); ?>" class="dashboard-order-row"><span class="order-row-code ltr"><?php echo e($order->number); ?></span><span class="min-w-0"><strong><?php echo e($order->user->name); ?></strong><small><?php echo e($order->cart?->store?->name ?? 'سلة'); ?> • <?php echo e($order->updated_at->diffForHumans()); ?></small></span><span class="status-badge <?php echo e($order->status==='needs_customer_action'?'status-warning':'status-primary'); ?>"><?php echo e($statusLabels[$order->status] ?? $order->status); ?></span><strong><?php echo e(number_format((float)$order->total_lyd,2)); ?> د.ل</strong></a><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><div class="empty-state-compact">لا توجد طلبات تحتاج تدخل مباشر الآن.</div><?php endif; ?></div>
            </section>
        </div>
        <div class="col-xl-5">
            <section class="admin-panel p-3 p-md-4 h-100">
                <div class="d-flex justify-content-between align-items-center gap-2 mb-3"><div><div class="page-kicker">Payments</div><h2 class="h5 panel-title mb-0">دفعات تنتظر التحقق</h2></div><span class="status-badge status-warning"><?php echo e($stats['pending_payments']); ?></span></div>
                <div class="dashboard-payment-list"><?php $__empty_1 = true; $__currentLoopData = $pendingPayments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $payment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><a href="<?php echo e(route('admin.orders.show',$payment->order)); ?>" class="dashboard-payment-row"><span class="payment-row-icon">د</span><span class="min-w-0"><strong><?php echo e(number_format((float)$payment->amount,2)); ?> د.ل</strong><small><?php echo e($payment->order?->number); ?> • <?php echo e($payment->method?->name); ?></small></span><span class="text-secondary small"><?php echo e($payment->created_at->diffForHumans()); ?></span></a><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><div class="empty-state-compact">لا توجد دفعات معلقة.</div><?php endif; ?></div>
            </section>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-5">
            <section class="admin-panel p-3 p-md-4 h-100">
                <div class="page-kicker">SLA Watch</div><h2 class="h5 panel-title mb-3">طلبات متأخرة أكثر من 24 ساعة</h2>
                <div class="d-grid gap-2"><?php $__empty_1 = true; $__currentLoopData = $agingOrders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><a href="<?php echo e(route('admin.orders.show',$order)); ?>" class="aging-order-card"><div><strong class="ltr"><?php echo e($order->number); ?></strong><small><?php echo e($order->user->name); ?> • <?php echo e($statusLabels[$order->status] ?? $order->status); ?></small></div><span><?php echo e($order->updated_at->diffForHumans()); ?></span></a><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><div class="empty-state-compact">ممتاز، لا توجد طلبات متأخرة حاليًا.</div><?php endif; ?></div>
            </section>
        </div>
        <div class="col-xl-7">
            <section class="admin-panel p-3 p-md-4 h-100 audit-monitor-card">
                <div class="d-flex justify-content-between align-items-center gap-2 mb-3"><div><div class="page-kicker">Audit Monitor</div><h2 class="h5 panel-title mb-0">آخر نشاط بالنظام</h2></div><span class="status-badge status-primary">audit</span></div>
                <div class="audit-stream"><?php $__empty_1 = true; $__currentLoopData = $recentActivity; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $activity): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><div class="audit-stream-row"><span class="audit-dot"></span><div class="min-w-0"><strong><?php echo e($activity->title); ?></strong><small><?php echo e($activity->actor?->name ?? 'النظام'); ?> <?php if($activity->order): ?>• <?php echo e($activity->order->number); ?><?php endif; ?></small><?php if($activity->description): ?><p><?php echo e($activity->description); ?></p><?php endif; ?></div><time><?php echo e($activity->created_at?->diffForHumans()); ?></time></div><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><div class="empty-state-compact">سيظهر نشاط النظام هنا بعد تشغيل Migration الجديدة.</div><?php endif; ?></div>
            </section>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\laragon\www\Salltak\resources\views/admin/dashboard.blade.php ENDPATH**/ ?>