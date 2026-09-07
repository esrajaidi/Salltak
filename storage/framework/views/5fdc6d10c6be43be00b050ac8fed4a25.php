<?php $__env->startSection('title','إنشاء حساب'); ?>
<?php $__env->startSection('body'); ?>
<section class="auth-page">
    <div class="container">
        <div class="auth-shell reveal is-visible">
            <aside class="auth-side"><span class="brand-mark">س</span><h2 class="h2">ابدئي أول سلة في دقائق.</h2><p>حساب واحد يجمع روابط السلات، تفاصيل المنتجات، المقاسات والألوان والتحويل للدينار.</p><div class="auth-benefit"><i>1</i><span>ألصقي رابط مشاركة SHEIN</span></div><div class="auth-benefit"><i>2</i><span>راجعي المنتجات الحقيقية</span></div><div class="auth-benefit"><i>3</i><span>احفظي السلة وارجعي لها</span></div></aside>
            <div class="auth-card">
                <div class="d-flex align-items-center gap-3 mb-4"><div class="auth-brand-icon">س</div><div><div class="small text-primary fw-bold">سلتك — Salltak</div><h1 class="h3 fw-bold mb-0 mt-1">إنشاء حساب</h1></div></div>
                <p class="text-secondary mb-4">حسابك يخليك تحفظي سلاتك وتشوفي USD وLYD جنب بعض.</p>
                <form method="POST" action="<?php echo e(route('register.store')); ?>"><?php echo csrf_field(); ?>
                    <div class="mb-3"><label class="form-label">الاسم</label><input class="form-control <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" name="name" value="<?php echo e(old('name')); ?>" autocomplete="name" required></div>
                    <div class="mb-3"><label class="form-label">البريد الإلكتروني</label><input class="form-control ltr <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" type="email" name="email" value="<?php echo e(old('email')); ?>" autocomplete="email" placeholder="name@example.com" required></div>
                    <div class="mb-3"><label class="form-label">رقم الهاتف</label><input class="form-control ltr <?php $__errorArgs = ['phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" name="phone" value="<?php echo e(old('phone')); ?>" autocomplete="tel" placeholder="09xxxxxxxx"></div>
                    <div class="row g-3"><div class="col-md-6"><label class="form-label">كلمة المرور</label><input class="form-control ltr" type="password" name="password" autocomplete="new-password" required></div><div class="col-md-6"><label class="form-label">تأكيد كلمة المرور</label><input class="form-control ltr" type="password" name="password_confirmation" autocomplete="new-password" required></div></div>
                    <button class="btn btn-primary btn-lg w-100 mt-4" type="submit">إنشاء الحساب</button>
                </form>
                <div class="text-center small text-secondary mt-4">عندك حساب؟ <a class="fw-bold text-decoration-none text-primary" href="<?php echo e(route('login')); ?>">سجّل الدخول</a></div>
            </div>
        </div>
    </div>
</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\laragon\www\Salltak\resources\views/auth/register.blade.php ENDPATH**/ ?>