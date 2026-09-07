<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <meta name="theme-color" content="#0F2744">
    <title><?php echo $__env->yieldContent('title', 'سلتك — Salltak'); ?></title>
    <link rel="stylesheet" href="<?php echo e(asset('vendor/bootstrap/bootstrap.min.css')); ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="<?php echo e(asset('css/app.css')); ?>">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="<?php echo e(asset('js/app-ui.js')); ?>" defer></script>
    <?php echo $__env->yieldPushContent('styles'); ?>
</head>
<body>
<?php if (! (request()->routeIs('admin.*'))): ?>
<nav class="navbar navbar-expand-lg app-navbar sticky-top" aria-label="التنقل الرئيسي">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="<?php echo e(route('home')); ?>">
            <span class="brand-mark" aria-hidden="true">س</span>
            <span class="lh-sm">سلتك <span class="brand-accent">Salltak</span><span class="brand-subtitle">تسوّق عالمي بسعر واضح</span></span>
        </a>

        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="فتح القائمة">
            <span class="navbar-toggler-icon-custom">☰</span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-lg-4 mb-2 mb-lg-0 align-items-lg-center gap-lg-1">
                <li class="nav-item"><a class="nav-link <?php echo e(request()->routeIs('home') ? 'active' : ''); ?>" href="<?php echo e(route('home')); ?>">الرئيسية</a></li>
                <?php if(auth()->guard()->check()): ?>
                    <?php if(in_array(auth()->user()->role, ['admin','order_manager'], true)): ?>
                        <li class="nav-item"><a class="nav-link <?php echo e(request()->routeIs('admin.*') ? 'active' : ''); ?>" href="<?php echo e(route('admin.dashboard')); ?>">لوحة الإدارة</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link <?php echo e(request()->routeIs('orders.*') ? 'active' : ''); ?>" href="<?php echo e(route('orders.index')); ?>">طلباتي</a></li>
                        <li class="nav-item"><a class="nav-link <?php echo e(request()->routeIs('carts.index','carts.show') ? 'active' : ''); ?>" href="<?php echo e(route('carts.index')); ?>">سلاتي</a></li>
                        <li class="nav-item"><a class="nav-link <?php echo e(request()->routeIs('carts.create','carts.preview') ? 'active' : ''); ?>" href="<?php echo e(route('carts.create')); ?>">سلة جديدة</a></li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>

            <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-2 me-lg-auto pt-2 pt-lg-0">
                <?php if(auth()->guard()->check()): ?>
                    <?php echo $__env->make('partials.notification-bell', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <div class="user-chip d-flex align-items-center gap-2">
                        <span class="avatar-circle"><?php echo e(mb_substr(auth()->user()->name, 0, 1)); ?></span>
                        <span class="small fw-semibold"><?php echo e(auth()->user()->name); ?></span>
                    </div>
                    <form method="POST" action="<?php echo e(route('logout')); ?>" class="m-0">
                        <?php echo csrf_field(); ?>
                        <button class="btn btn-ghost btn-sm px-3 w-100" type="submit">تسجيل الخروج</button>
                    </form>
                <?php else: ?>
                    <a class="btn btn-ghost btn-sm px-3" href="<?php echo e(route('login')); ?>">تسجيل الدخول</a>
                    <a class="btn btn-primary btn-sm px-3" href="<?php echo e(route('register')); ?>">إنشاء حساب</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
<?php endif; ?>

<div id="swal-flash" data-swal-success="<?php echo e(session('success')); ?>" data-swal-errors="<?php echo e(json_encode($errors->all(), JSON_UNESCAPED_UNICODE)); ?>" hidden></div>

<main><?php echo $__env->yieldContent('body'); ?></main>

<?php if (! (request()->routeIs('admin.*'))): ?>
<footer class="app-footer mt-auto">
    <div class="container py-4 py-lg-5">
        <div class="row g-4 align-items-start">
            <div class="col-lg-5">
                <div class="d-flex align-items-center gap-2 mb-2"><span class="brand-mark">س</span><div><div class="footer-brand">سلتك — Salltak</div><div class="small text-white-50">منصة ذكية لاستيراد ومراجعة سلات التسوق</div></div></div>
                <p class="small mb-0 mt-3" style="max-width:540px">ألصق رابط السلة، راجع المنتجات الحقيقية، وشوف السعر بالدولار والدينار الليبي قبل الحفظ.</p>
            </div>
            <div class="col-6 col-lg-3">
                <div class="footer-brand mb-2">روابط سريعة</div>
                <div class="d-grid gap-2 small"><a href="<?php echo e(route('home')); ?>">الرئيسية</a><?php if(auth()->guard()->check()): ?> <?php if (! (in_array(auth()->user()->role, ['admin','order_manager'], true))): ?><a href="<?php echo e(route('orders.index')); ?>">طلباتي</a><a href="<?php echo e(route('carts.index')); ?>">سلاتي</a><a href="<?php echo e(route('carts.create')); ?>">سلة جديدة</a><?php endif; ?> <?php endif; ?></div>
            </div>
            <div class="col-6 col-lg-4">
                <div class="footer-brand mb-2">تجربة مصممة لليبيا</div>
                <div class="small">RTL كامل • موبايل وكمبيوتر • USD → LYD • استيراد SHEIN</div>
            </div>
        </div>
        <div class="border-top footer-rule mt-4 pt-3 d-flex flex-column flex-md-row justify-content-between gap-2 small text-white-50"><span>جميع الحقوق محفوظة © <?php echo e(date('Y')); ?> سلتك</span><span>تسوّق عالمي، بوضوح محلي.</span></div>
    </div>
</footer>
<?php endif; ?>

<script src="<?php echo e(asset('vendor/bootstrap/bootstrap.bundle.min.js')); ?>"></script>
<?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH D:\laragon\www\Salltak\resources\views/layouts/app.blade.php ENDPATH**/ ?>