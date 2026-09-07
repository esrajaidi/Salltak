<?php $__env->startSection('title','إدارة السلات'); ?>
<?php $__env->startSection('admin-content'); ?>
<div class="admin-page-header mb-4 reveal is-visible"><div class="small text-primary fw-bold mb-1">إدارة البيانات</div><h1 class="page-heading">إدارة السلات</h1><p class="page-subtitle">ابحث وفلتر جميع السلات المحفوظة في المنصة.</p></div>

<div class="surface-card admin-panel reveal p-3 p-md-4 mb-4">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-6 col-xl-4"><label class="form-label">بحث</label><input class="form-control" name="q" value="<?php echo e(request('q')); ?>" placeholder="رقم السلة أو اسم العميل"></div>
        <div class="col-md-3 col-xl-3"><label class="form-label">الحالة</label><select class="form-select" name="status"><option value="">كل الحالات</option><?php $__currentLoopData = ['saved','new','confirmed','cancelled']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($s); ?>" <?php if(request('status')===$s): echo 'selected'; endif; ?>><?php echo e($s); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
        <div class="col-md-auto"><button class="btn btn-primary w-100" type="submit">تطبيق الفلترة</button></div>
        <?php if(request()->filled('q') || request()->filled('status')): ?><div class="col-md-auto"><a class="btn btn-outline-secondary w-100" href="<?php echo e(route('admin.carts.index')); ?>">مسح</a></div><?php endif; ?>
    </form>
</div>

<div class="surface-card admin-panel overflow-hidden reveal"><div class="table-responsive"><table class="table table-modern"><thead><tr><th>الرقم</th><th>العميل</th><th>العناصر</th><th>الإجمالي</th><th>الحالة</th><th></th></tr></thead><tbody><?php $__empty_1 = true; $__currentLoopData = $carts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cart): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><tr><td class="fw-semibold"><?php echo e($cart->number); ?></td><td><?php echo e($cart->user->name); ?></td><td><?php echo e($cart->items_count); ?></td><td class="fw-bold"><?php echo e(number_format((float)$cart->total_lyd,2)); ?> د.ل</td><td><span class="status-badge <?php echo e($cart->status==='cancelled'?'status-danger':($cart->status==='confirmed'?'status-success':'status-primary')); ?>"><?php echo e($cart->status); ?></span></td><td><a class="btn btn-soft btn-sm" href="<?php echo e(route('admin.carts.show',$cart)); ?>">تفاصيل</a></td></tr><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><tr><td colspan="6" class="text-center py-5 text-secondary">لا توجد نتائج.</td></tr><?php endif; ?></tbody></table></div></div>
<div class="mt-4"><?php echo e($carts->links()); ?></div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\laragon\www\Salltak\resources\views/admin/carts/index.blade.php ENDPATH**/ ?>