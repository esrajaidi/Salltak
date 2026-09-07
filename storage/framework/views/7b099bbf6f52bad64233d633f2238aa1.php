<?php $__env->startSection('title',$cart->number); ?>
<?php $__env->startSection('admin-content'); ?>
<div class="admin-page-header reveal is-visible d-flex flex-column flex-md-row align-items-md-end justify-content-between gap-3 mb-4">
    <div><div class="small text-primary fw-bold mb-1">تفاصيل السلة</div><h1 class="page-heading"><?php echo e($cart->number); ?></h1><p class="page-subtitle"><?php echo e($cart->user->name); ?> — <?php echo e($cart->user->email); ?></p></div>
    <a class="btn btn-outline-primary" href="<?php echo e(route('admin.carts.index')); ?>">الرجوع للسلات</a>
</div>

<div class="surface-card admin-panel reveal p-3 p-md-4">
    <div class="row g-3 mb-4">
        <div class="col-md-4"><div class="summary-tile"><div class="summary-label">الإجمالي بالدينار</div><div class="summary-value text-primary"><?php echo e(number_format((float)$cart->total_lyd,2)); ?> د.ل</div></div></div>
        <div class="col-md-4"><div class="summary-tile"><div class="summary-label">حالة الاستيراد</div><div class="summary-value"><?php echo e($cart->import_status); ?></div></div></div>
        <div class="col-md-4"><div class="summary-tile"><div class="summary-label">عدد المنتجات</div><div class="summary-value"><?php echo e($cart->items->count()); ?></div></div></div>
    </div>

    <form method="POST" action="<?php echo e(route('admin.carts.status',$cart)); ?>" class="row g-2 align-items-end mb-4">
        <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
        <div class="col-md-5 col-lg-3"><label class="form-label">حالة السلة</label><select name="status" class="form-select"><?php $__currentLoopData = ['new','saved','confirmed','cancelled']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($s); ?>" <?php if($cart->status===$s): echo 'selected'; endif; ?>><?php echo e($s); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
        <div class="col-md-auto"><button class="btn btn-primary w-100" type="submit">تحديث الحالة</button></div>
    </form>

    <div class="table-wrap"><div class="table-responsive"><table class="table table-modern"><thead><tr><th>المنتج</th><th>السعر</th><th>الكمية</th></tr></thead><tbody><?php $__empty_1 = true; $__currentLoopData = $cart->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><tr><td><div class="d-flex align-items-center gap-2"><?php if($item->image_url): ?><img src="<?php echo e($item->image_url); ?>" alt="" width="44" height="44" class="rounded object-fit-cover"><?php endif; ?><span class="fw-semibold"><?php echo e($item->name); ?></span></div></td><td><?php echo e(number_format((float)$item->unit_price_original,2)); ?> <?php echo e($item->currency); ?></td><td><?php echo e($item->quantity); ?></td></tr><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><tr><td colspan="3" class="text-center text-secondary py-4">لا توجد منتجات.</td></tr><?php endif; ?></tbody></table></div></div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\laragon\www\Salltak\resources\views/admin/carts/show.blade.php ENDPATH**/ ?>