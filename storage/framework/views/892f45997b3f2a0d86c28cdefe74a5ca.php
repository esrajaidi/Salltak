<?php $__env->startSection('title','تسجيل الدخول'); ?>
<?php $__env->startSection('body'); ?>
<section class="auth-page">
    <div class="container">
        <div class="surface-card-elevated auth-card">
            <div class="d-flex align-items-center gap-3 mb-4"><div class="auth-brand-icon">س</div><div><div class="small text-primary fw-bold">سلتك — Salltak</div><h1 class="h3 fw-bold mb-0 mt-1">مرحبًا بعودتك</h1></div></div>
            <p class="text-secondary mb-4">سجّل الدخول للوصول إلى سلاتك المحفوظة ومراجعة الأسعار بالدينار الليبي.</p>
            <form method="POST" action="<?php echo e(route('login.store')); ?>">
                <?php echo csrf_field(); ?>
                <div class="mb-3"><label class="form-label" for="login-email">البريد الإلكتروني</label><input id="login-email" class="form-control ltr <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" type="email" name="email" value="<?php echo e(old('email')); ?>" autocomplete="email" placeholder="name@example.com" required></div>
                <div class="mb-3"><label class="form-label" for="login-password">كلمة المرور</label><input id="login-password" class="form-control ltr <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" type="password" name="password" autocomplete="current-password" required></div>
                <div class="form-check mb-4"><input class="form-check-input" type="checkbox" name="remember" value="1" id="remember"><label class="form-check-label" for="remember">تذكرني</label></div>
                <button class="btn btn-primary btn-lg w-100" type="submit">تسجيل الدخول</button>
            </form>
            <div class="text-center small text-secondary mt-4">ليس لديك حساب؟ <a class="fw-bold text-decoration-none" href="<?php echo e(route('register')); ?>">أنشئ حسابًا جديدًا</a></div>
        </div>
    </div>
</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\laragon\www\Salltak\resources\views/auth/login.blade.php ENDPATH**/ ?>