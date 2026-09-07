<?php $__env->startSection('title','أسعار الصرف'); ?>
<?php $__env->startSection('admin-content'); ?>
<div class="admin-page-header mb-4 reveal is-visible"><div class="small text-primary fw-bold mb-1">الإعدادات المالية</div><h1 class="page-heading">أسعار الصرف</h1><p class="page-subtitle">حدد قيمة كل عملة مقابل الدينار الليبي.</p></div>

<div class="surface-card admin-panel reveal p-3 p-md-4 mb-4">
    <form method="POST" action="<?php echo e(route('admin.rates.store')); ?>" class="row g-2 align-items-end">
        <?php echo csrf_field(); ?>
        <div class="col-sm-4 col-lg-2"><label class="form-label">العملة</label><input class="form-control text-uppercase ltr" name="currency" placeholder="USD" maxlength="3" required></div>
        <div class="col-sm-5 col-lg-3"><label class="form-label">1 وحدة = د.ل</label><input class="form-control ltr" type="number" step="0.0001" min="0" name="rate_to_lyd" placeholder="مثال: 7.2500" required></div>
        <div class="col-sm-auto"><button class="btn btn-primary w-100" type="submit">حفظ السعر</button></div>
    </form>
</div>

<div class="surface-card admin-panel overflow-hidden reveal"><div class="table-responsive"><table class="table table-modern"><thead><tr><th>العملة</th><th>1 وحدة = د.ل</th><th>الحالة</th><th></th></tr></thead><tbody><?php $__empty_1 = true; $__currentLoopData = $rates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rate): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><tr><td class="fw-bold"><?php echo e($rate->currency); ?></td><td><?php echo e($rate->rate_to_lyd); ?></td><td><span class="status-badge <?php echo e($rate->is_active?'status-success':'status-neutral'); ?>"><?php echo e($rate->is_active?'مفعّل':'موقوف'); ?></span></td><td><form method="POST" action="<?php echo e(route('admin.rates.toggle',$rate)); ?>"><?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?><button class="btn btn-soft btn-sm" type="submit">تبديل الحالة</button></form></td></tr><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><tr><td colspan="4" class="text-center text-secondary py-5">لا توجد أسعار صرف.</td></tr><?php endif; ?></tbody></table></div></div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\laragon\www\Salltak\resources\views/admin/exchange-rates/index.blade.php ENDPATH**/ ?>