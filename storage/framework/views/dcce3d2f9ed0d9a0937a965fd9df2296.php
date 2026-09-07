<?php $__env->startSection('title','المواقع المدعومة'); ?>
<?php $__env->startSection('admin-content'); ?>
<div class="admin-page-header mb-4 reveal is-visible"><div class="small text-primary fw-bold mb-1">تكامل المتاجر</div><h1 class="page-heading">المواقع المدعومة</h1><p class="page-subtitle">أضف المواقع والعملات وحدد نوع الـAdapter المستخدم للاستيراد.</p></div>

<div class="surface-card admin-panel reveal p-3 p-md-4 mb-4">
    <h2 class="h5 fw-bold mb-3">إضافة موقع جديد</h2>
    <form method="POST" action="<?php echo e(route('admin.stores.store')); ?>" class="row g-3">
        <?php echo csrf_field(); ?>
        <div class="col-md-6 col-xl-3"><label class="form-label">اسم الموقع</label><input class="form-control" name="name" placeholder="مثال: SHEIN" required></div>
        <div class="col-md-6 col-xl-4"><label class="form-label">النطاقات</label><input class="form-control ltr" name="domains_text" placeholder="shein.com, onelink.shein.com" required></div>
        <div class="col-6 col-md-3 col-xl-2"><label class="form-label">العملة</label><input class="form-control ltr text-uppercase" name="currency" value="USD" maxlength="3" required></div>
        <div class="col-6 col-md-3 col-xl-3"><label class="form-label">Adapter</label><select class="form-select" name="adapter"><option value="generic">Generic</option><option value="shein">SHEIN</option></select></div>
        <div class="col-md-9"><label class="form-label">رابط الشعار <span class="text-secondary fw-normal">(اختياري)</span></label><input class="form-control ltr" name="logo_url" placeholder="https://..."></div>
        <div class="col-md-3 d-flex align-items-end"><button class="btn btn-primary w-100" type="submit">إضافة الموقع</button></div>
    </form>
</div>

<div class="surface-card admin-panel overflow-hidden reveal"><div class="table-responsive"><table class="table table-modern"><thead><tr><th>الموقع</th><th>النطاقات</th><th>العملة</th><th>الحالة</th></tr></thead><tbody><?php $__empty_1 = true; $__currentLoopData = $stores; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $store): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><tr><td class="fw-bold"><?php echo e($store->name); ?></td><td class="ltr small"><?php echo e(implode(', ',$store->domains)); ?></td><td><?php echo e($store->currency); ?></td><td><span class="status-badge <?php echo e($store->is_active?'status-success':'status-neutral'); ?>"><?php echo e($store->is_active?'مفعّل':'موقوف'); ?></span></td></tr><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><tr><td colspan="4" class="text-center text-secondary py-5">لا توجد مواقع.</td></tr><?php endif; ?></tbody></table></div></div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\laragon\www\Salltak\resources\views/admin/stores/index.blade.php ENDPATH**/ ?>