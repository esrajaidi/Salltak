<?php $__env->startSection('title','الطلبات'); ?>
<?php $__env->startSection('admin-content'); ?>
<?php ($statusLabels=['submitted'=>'تم الإرسال','under_review'=>'تحت المراجعة','needs_customer_action'=>'يحتاج رد العميل','approved'=>'معتمد','awaiting_deposit'=>'بانتظار العربون','awaiting_payment'=>'بانتظار الدفع','deposit_paid'=>'العربون مدفوع','purchasing'=>'جاري الشراء','ordered'=>'تم الطلب','shipped'=>'جاري الشحن','arrived_libya'=>'وصل ليبيا','awaiting_balance'=>'بانتظار الباقي','ready_for_delivery'=>'جاهز للتسليم','out_for_delivery'=>'خرج للتسليم','delivered'=>'تم التسليم','rejected'=>'مرفوض','cancelled'=>'ملغي']); ?>
<div class="admin-page-header reveal is-visible mb-4"><div class="small text-primary fw-bold mb-1">دورة الطلب</div><h1 class="page-heading">الطلبات</h1><p class="page-subtitle">مراجعة السلات، إسناد المسؤول، اعتماد المنتجات، متابعة الدفعات والشحن والتسليم.</p></div>

<div class="surface-card admin-panel reveal p-3 p-md-4 mb-4">
<form method="GET" class="row g-2 align-items-end">
    <div class="col-md-4"><label class="form-label">بحث</label><input class="form-control" name="q" value="<?php echo e(request('q')); ?>" placeholder="رقم الطلب / العميل"></div>
    <div class="col-md-3"><label class="form-label">الحالة</label><select class="form-select" name="status"><option value="">كل الحالات</option><?php $__currentLoopData = $statusLabels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key=>$label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($key); ?>" <?php if(request('status')===$key): echo 'selected'; endif; ?>><?php echo e($label); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
    <div class="col-md-3"><label class="form-label">المسؤول</label><select class="form-select" name="assigned_to"><option value="">الكل</option><?php $__currentLoopData = $managers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $manager): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($manager->id); ?>" <?php if((string)request('assigned_to')===(string)$manager->id): echo 'selected'; endif; ?>><?php echo e($manager->name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
    <div class="col-md-2"><button class="btn btn-primary w-100">تصفية</button></div>
</form>
</div>

<div class="surface-card admin-panel overflow-hidden reveal">
<div class="table-responsive"><table class="table table-modern align-middle"><thead><tr><th>الطلب</th><th>العميل</th><th>الحالة</th><th>الدفع</th><th>الإجمالي</th><th>المتبقي</th><th>المسؤول</th><th></th></tr></thead><tbody>
<?php $__empty_1 = true; $__currentLoopData = $orders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
<tr>
<td><div class="fw-bold ltr text-end"><?php echo e($order->number); ?></div><small class="text-secondary"><?php echo e($order->items_count); ?> منتج</small></td>
<td><div class="fw-semibold"><?php echo e($order->user->name); ?></div><small class="text-secondary ltr"><?php echo e($order->user->phone ?: $order->user->email); ?></small></td>
<td><span class="status-badge <?php echo e(in_array($order->status,['rejected','cancelled'],true)?'status-danger':(in_array($order->status,['delivered','ready_for_delivery'],true)?'status-success':'status-primary')); ?>"><?php echo e($statusLabels[$order->status]??$order->status); ?></span></td>
<td><span class="small fw-semibold"><?php echo e($order->payment_status); ?></span></td>
<td class="fw-bold"><?php echo e(number_format((float)$order->total_lyd,2)); ?> د.ل</td>
<td class="fw-bold <?php echo e((float)$order->remaining_amount>0?'text-danger':'text-success'); ?>"><?php echo e(number_format((float)$order->remaining_amount,2)); ?> د.ل</td>
<td><?php echo e($order->assignee?->name ?? 'غير مسند'); ?></td>
<td><a class="btn btn-soft btn-sm" href="<?php echo e(route('admin.orders.show',$order)); ?>">مراجعة</a></td>
</tr>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><tr><td colspan="8" class="text-center py-5 text-secondary">لا توجد طلبات.</td></tr><?php endif; ?>
</tbody></table></div></div>
<div class="mt-4"><?php echo e($orders->links()); ?></div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\laragon\www\Salltak\resources\views/admin/orders/index.blade.php ENDPATH**/ ?>