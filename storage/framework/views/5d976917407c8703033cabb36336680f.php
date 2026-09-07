<?php $__env->startSection('title','إعدادات النظام'); ?>
<?php $__env->startSection('admin-content'); ?>
<div class="admin-page-header mb-4 reveal is-visible"><div class="small text-primary fw-bold mb-1">تخصيص المنصة</div><h1 class="page-heading">إعدادات النظام</h1><p class="page-subtitle">عدّل اسم المنصة وبيانات التواصل ونص الصفحة الرئيسية.</p></div>

<div class="surface-card admin-panel reveal p-3 p-md-4 p-xl-5" style="max-width:900px">
    <form method="POST" action="<?php echo e(route('admin.settings.update')); ?>">
        <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
        <div class="row g-3">
            <div class="col-12"><label class="form-label">اسم المنصة</label><input class="form-control" name="platform_name" value="<?php echo e(old('platform_name',$settings['platform_name']??'سلات ليبيا')); ?>"></div>
            <div class="col-md-6"><label class="form-label">الهاتف</label><input class="form-control ltr" name="contact_phone" value="<?php echo e(old('contact_phone',$settings['contact_phone']??'')); ?>"></div>
            <div class="col-md-6"><label class="form-label">البريد</label><input class="form-control ltr" type="email" name="contact_email" value="<?php echo e(old('contact_email',$settings['contact_email']??'')); ?>"></div>
            <div class="col-12"><label class="form-label">وصف الصفحة الرئيسية</label><textarea class="form-control" name="home_intro"><?php echo e(old('home_intro',$settings['home_intro']??'')); ?></textarea></div>
        </div>
        <div class="d-flex justify-content-end mt-4"><button class="btn btn-primary px-4" type="submit">حفظ التغييرات</button></div>
    </form>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\laragon\www\Salltak\resources\views/admin/settings/edit.blade.php ENDPATH**/ ?>