<?php if(auth()->guard()->check()): ?>
<div class="dropdown notification-dropdown">
    <button class="btn notification-bell-btn position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="الإشعارات">
        <span aria-hidden="true">🔔</span>
        <?php if(($unreadNotificationCount ?? 0) > 0): ?>
            <span class="notification-count"><?php echo e(($unreadNotificationCount ?? 0) > 99 ? '99+' : $unreadNotificationCount); ?></span>
        <?php endif; ?>
    </button>
    <div class="dropdown-menu dropdown-menu-end notification-menu p-0 shadow-lg border-0">
        <div class="notification-menu-head d-flex align-items-center justify-content-between gap-2">
            <div><strong>الإشعارات</strong><small><?php echo e($unreadNotificationCount ?? 0); ?> غير مقروء</small></div>
            <?php if(($unreadNotificationCount ?? 0) > 0): ?>
                <form method="POST" action="<?php echo e(route('notifications.read-all')); ?>"><?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                    <button class="btn btn-link btn-sm text-decoration-none p-0" type="submit">تعليم الكل</button>
                </form>
            <?php endif; ?>
        </div>
        <div class="notification-menu-list">
            <?php $__empty_1 = true; $__currentLoopData = ($navNotifications ?? collect()); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $notification): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <form method="POST" action="<?php echo e(route('notifications.read', $notification)); ?>" class="m-0"><?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                    <button type="submit" class="notification-mini-item <?php echo e($notification->read_at ? '' : 'is-unread'); ?>">
                        <span class="notification-mini-icon"><?php echo e(match($notification->icon){'payment'=>'د','item'=>'▣','message'=>'✉','assignment'=>'◎','status'=>'✓','note'=>'•','customer'=>'◉','new-order'=>'＋',default=>'•'}); ?></span>
                        <span class="min-w-0"><strong><?php echo e($notification->title); ?></strong><?php if($notification->body): ?><small><?php echo e($notification->body); ?></small><?php endif; ?><time><?php echo e($notification->created_at->diffForHumans()); ?></time></span>
                    </button>
                </form>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="notification-empty">لا توجد إشعارات حتى الآن.</div>
            <?php endif; ?>
        </div>
        <a class="notification-view-all" href="<?php echo e(route('notifications.index')); ?>">عرض كل الإشعارات ←</a>
    </div>
</div>
<?php endif; ?>
<?php /**PATH D:\laragon\www\Salltak\resources\views/partials/notification-bell.blade.php ENDPATH**/ ?>