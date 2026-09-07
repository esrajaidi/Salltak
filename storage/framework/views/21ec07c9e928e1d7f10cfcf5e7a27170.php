<?php $__env->startSection('title','طرق الدفع'); ?>
<?php $__env->startSection('admin-content'); ?>
<?php
    $activeCount = $methods->where('is_active', true)->count();
    $readyCount = $methods->filter(fn($m) => $m->activationIssues() === [])->count();
    $docLabels = [
        'public_docs_partner_api_restricted' => 'وثائق عامة + API عبر جهة مرخصة',
        'public_standard' => 'معيار رسمي منشور',
        'merchant_docs_required' => 'يحتاج وثائق/عقد التاجر',
        'acquirer_docs_required' => 'يحتاج وثائق المصرف/المعالج',
        'merchant_account_required' => 'يحتاج بيانات حساب التاجر',
        'internal' => 'إعداد داخلي',
        'custom' => 'مخصص',
    ];
?>

<div class="admin-page-header reveal is-visible mb-4">
    <div class="small text-primary fw-bold mb-1">المدفوعات</div>
    <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-between gap-3">
        <div>
            <h1 class="page-heading">طرق الدفع في ليبيا</h1>
            <p class="page-subtitle mb-0">فعّل فقط الطريقة التي أكملت بيانات استقبالها. العميل لا يرى أي طريقة ناقصة أو متوقفة.</p>
        </div>
        <form method="POST" action="<?php echo e(route('admin.payment-methods.install-libya')); ?>">
            <?php echo csrf_field(); ?>
            <button class="btn btn-primary" type="submit">تثبيت / تحديث الدليل الليبي</button>
        </form>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3"><div class="summary-tile h-100"><div class="summary-label">إجمالي الطرق</div><div class="summary-value"><?php echo e($methods->count()); ?></div></div></div>
    <div class="col-6 col-xl-3"><div class="summary-tile h-100"><div class="summary-label">المفعلة</div><div class="summary-value text-success"><?php echo e($activeCount); ?></div></div></div>
    <div class="col-6 col-xl-3"><div class="summary-tile h-100"><div class="summary-label">جاهزة للتفعيل</div><div class="summary-value"><?php echo e($readyCount); ?></div></div></div>
    <div class="col-6 col-xl-3"><div class="summary-tile h-100"><div class="summary-label">تحتاج إعداد</div><div class="summary-value text-warning"><?php echo e($methods->count() - $readyCount); ?></div></div></div>
</div>

<div class="surface-card admin-panel p-3 mb-4 reveal is-visible">
    <div class="row g-2 align-items-center">
        <div class="col-lg-5">
            <label class="visually-hidden" for="paymentMethodSearch">بحث</label>
            <input id="paymentMethodSearch" class="form-control" type="search" placeholder="ابحث باسم الطريقة أو المزود..." data-payment-filter="search">
        </div>
        <div class="col-lg-7">
            <div class="payment-filter-pills" data-payment-filter="buttons">
                <button type="button" class="btn btn-sm btn-primary active" data-filter="all">الكل</button>
                <button type="button" class="btn btn-sm btn-light" data-filter="active">المفعلة</button>
                <button type="button" class="btn btn-sm btn-light" data-filter="inactive">المتوقفة</button>
                <button type="button" class="btn btn-sm btn-light" data-filter="ready">جاهزة</button>
                <button type="button" class="btn btn-sm btn-light" data-filter="needs_config">تحتاج إعداد</button>
            </div>
        </div>
    </div>
</div>

<div class="accordion mb-4" id="customPaymentAccordion">
    <div class="accordion-item border-0 surface-card overflow-hidden">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#customPaymentForm">إضافة طريقة دفع مخصصة</button>
        </h2>
        <div id="customPaymentForm" class="accordion-collapse collapse" data-bs-parent="#customPaymentAccordion">
            <div class="accordion-body">
                <form method="POST" action="<?php echo e(route('admin.payment-methods.store')); ?>" class="row g-3"><?php echo csrf_field(); ?>
                    <div class="col-md-4"><label class="form-label">الاسم</label><input class="form-control" name="name" required></div>
                    <div class="col-md-4"><label class="form-label">الكود</label><input class="form-control ltr" name="code" required placeholder="custom_method"></div>
                    <div class="col-md-4"><label class="form-label">النوع</label><select class="form-select" name="type"><option value="manual">يدوي</option><option value="bank">مصرفي</option><option value="wallet">محفظة</option><option value="api">بوابة/بطاقات</option><option value="cash">نقدي</option></select></div>
                    <div class="col-md-3"><label class="form-label">الترتيب</label><input class="form-control" type="number" name="sort_order" value="999" min="0"></div>
                    <div class="col-md-3"><label class="form-label">أقل مبلغ</label><input class="form-control" type="number" step="0.01" name="min_amount"></div>
                    <div class="col-md-3"><label class="form-label">أعلى مبلغ</label><input class="form-control" type="number" step="0.01" name="max_amount"></div>
                    <div class="col-md-3"><label class="form-label">نوع الرسوم</label><select class="form-select" name="fee_type"><option value="none">بدون</option><option value="percentage">نسبة</option><option value="fixed">مبلغ ثابت</option></select></div>
                    <div class="col-md-3"><label class="form-label">قيمة الرسوم</label><input class="form-control" type="number" step="0.01" name="fee_value" value="0"></div>
                    <div class="col-md-3"><label class="form-label">وضع الربط</label><select class="form-select" name="config[integration_mode]"><option value="manual_verification">تحقق يدوي</option><option value="external_link">رابط دفع خارجي</option></select></div>
                    <div class="col-md-3"><label class="form-label">إثبات الدفع</label><select class="form-select" name="config[proof_mode]"><option value="reference_or_receipt">رقم أو إيصال</option><option value="reference">رقم عملية</option><option value="receipt">إيصال</option><option value="none">بدون</option></select></div>
                    <div class="col-md-3"><label class="form-label">العملة</label><input class="form-control ltr" name="config[currency]" value="LYD"></div>
                    <input type="hidden" name="config[availability]" value="online">
                    <div class="col-12"><label class="form-label">تعليمات العميل</label><textarea class="form-control" name="instructions" rows="2"></textarea></div>
                    <div class="col-12"><button class="btn btn-primary">إضافة الطريقة</button></div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="payment-method-grid" id="paymentMethodGrid">
<?php $__empty_1 = true; $__currentLoopData = $methods; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $method): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
    <?php
        $cfg = $method->config ?? [];
        $schema = $method->schemaDefinition();
        $issues = $method->activationIssues();
        $ready = $issues === [];
        $modeLabel = $schema['modes'][$method->integrationMode()] ?? $method->integrationMode();
        $searchText = strtolower($method->name.' '.($cfg['provider_name']??'').' '.($cfg['official_activity']??'').' '.$method->code);
    ?>
    <article class="payment-method-card surface-card reveal is-visible"
             data-payment-card
             data-state="<?php echo e($method->is_active ? 'active' : 'inactive'); ?>"
             data-readiness="<?php echo e($ready ? 'ready' : 'needs_config'); ?>"
             data-search="<?php echo e($searchText); ?>">
        <div class="payment-method-card-head">
            <div class="payment-method-icon" aria-hidden="true">
                <?php switch($method->type):
                    case ('wallet'): ?> ◉ <?php break; ?>
                    <?php case ('bank'): ?> ⇄ <?php break; ?>
                    <?php case ('api'): ?> ▣ <?php break; ?>
                    <?php case ('cash'): ?> د.ل <?php break; ?>
                    <?php default: ?> ✓
                <?php endswitch; ?>
            </div>
            <div class="flex-grow-1 min-w-0">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                    <h2 class="h6 fw-bold mb-0 text-truncate"><?php echo e($method->name); ?></h2>
                    <span class="status-badge <?php echo e($method->is_active ? 'status-success' : 'status-danger'); ?>"><?php echo e($method->is_active ? 'مفعلة' : 'متوقفة'); ?></span>
                </div>
                <div class="small text-secondary text-truncate"><?php echo e($cfg['provider_name'] ?? $method->code); ?></div>
            </div>
        </div>

        <div class="payment-method-meta">
            <span><b>الوضع:</b> <?php echo e($modeLabel); ?></span>
            <span><b>الظهور:</b> <?php echo e(($cfg['availability']??'online')==='delivery_only' ? 'التسليم فقط' : 'أونلاين'); ?></span>
            <span class="<?php echo e($ready ? 'text-success' : 'text-warning'); ?>"><b>الإعداد:</b> <?php echo e($ready ? 'جاهز' : 'ناقص'); ?></span>
        </div>

        <?php if(!empty($cfg['official_activity'])): ?>
            <p class="payment-method-activity"><?php echo e($cfg['official_activity']); ?></p>
        <?php endif; ?>

        <?php if(!$ready): ?>
            <div class="payment-readiness-note"><?php echo e($issues[0]); ?></div>
        <?php endif; ?>

        <div class="payment-method-actions">
            <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#paymentMethodModal<?php echo e($method->id); ?>">الإعدادات</button>
            <form method="POST" action="<?php echo e(route('admin.payment-methods.toggle',$method)); ?>"><?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                <button class="btn <?php echo e($method->is_active ? 'btn-danger-soft' : 'btn-primary'); ?> btn-sm" type="submit"><?php echo e($method->is_active ? 'إيقاف' : 'تفعيل'); ?></button>
            </form>
        </div>
    </article>

    <div class="modal fade" id="paymentMethodModal<?php echo e($method->id); ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header">
                    <div>
                        <h2 class="modal-title h5 fw-bold mb-1"><?php echo e($method->name); ?></h2>
                        <div class="small text-secondary"><?php echo e($schema['title'] ?? 'إعداد طريقة الدفع'); ?></div>
                    </div>
                    <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
                <form method="POST" action="<?php echo e(route('admin.payment-methods.update',$method)); ?>"><?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
                    <div class="modal-body">
                        <input type="hidden" name="code" value="<?php echo e($method->code); ?>">
                        <input type="hidden" name="type" value="<?php echo e($method->type); ?>">
                        <input type="hidden" name="config[documentation_status]" value="<?php echo e($cfg['documentation_status'] ?? 'custom'); ?>">
                        <input type="hidden" name="config[official_source]" value="<?php echo e($cfg['official_source'] ?? ''); ?>">
                        <input type="hidden" name="config[provider_name]" value="<?php echo e($cfg['provider_name'] ?? ''); ?>">
                        <input type="hidden" name="config[official_activity]" value="<?php echo e($cfg['official_activity'] ?? ''); ?>">

                        <div class="payment-doc-banner mb-4">
                            <div>
                                <strong><?php echo e($docLabels[$cfg['documentation_status'] ?? 'custom'] ?? ($cfg['documentation_status'] ?? 'مخصص')); ?></strong>
                                <div class="small text-secondary mt-1">لا يتم اختراع API. أي بيانات سرية هنا تأتي من عقد التاجر/المصرف فقط.</div>
                            </div>
                            <?php if(!empty($cfg['official_source']) && str_starts_with($cfg['official_source'],'http')): ?>
                                <a class="btn btn-light btn-sm" href="<?php echo e($cfg['official_source']); ?>" target="_blank" rel="noopener">المصدر الرسمي</a>
                            <?php endif; ?>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6"><label class="form-label">الاسم الظاهر</label><input class="form-control" name="name" value="<?php echo e($method->name); ?>" required></div>
                            <div class="col-md-2"><label class="form-label">الترتيب</label><input class="form-control" type="number" name="sort_order" value="<?php echo e($method->sort_order); ?>" min="0" required></div>
                            <div class="col-md-2"><label class="form-label">أقل مبلغ</label><input class="form-control" type="number" step="0.01" name="min_amount" value="<?php echo e($method->min_amount); ?>"></div>
                            <div class="col-md-2"><label class="form-label">أعلى مبلغ</label><input class="form-control" type="number" step="0.01" name="max_amount" value="<?php echo e($method->max_amount); ?>"></div>
                            <div class="col-md-3"><label class="form-label">الرسوم</label><select class="form-select" name="fee_type"><?php $__currentLoopData = ['none'=>'بدون رسوم','percentage'=>'نسبة %','fixed'=>'مبلغ ثابت']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k=>$v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($k); ?>" <?php if($method->fee_type===$k): echo 'selected'; endif; ?>><?php echo e($v); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
                            <div class="col-md-3"><label class="form-label">قيمة الرسوم</label><input class="form-control" type="number" step="0.01" min="0" name="fee_value" value="<?php echo e($method->fee_value); ?>" required></div>
                            <div class="col-md-3"><label class="form-label">وضع الربط</label><select class="form-select" name="config[integration_mode]"><?php $__currentLoopData = ($schema['modes']??['manual_verification'=>'تحقق يدوي']); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k=>$v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($k); ?>" <?php if($method->integrationMode()===$k): echo 'selected'; endif; ?>><?php echo e($v); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
                            <div class="col-md-3"><label class="form-label">متى يظهر؟</label><select class="form-select" name="config[availability]"><option value="online" <?php if(($cfg['availability']??'online')==='online'): echo 'selected'; endif; ?>>أثناء الدفع أونلاين</option><option value="delivery_only" <?php if(($cfg['availability']??'')==='delivery_only'): echo 'selected'; endif; ?>>وقت التسليم/الرصيد الأخير</option><option value="all" <?php if(($cfg['availability']??'')==='all'): echo 'selected'; endif; ?>>كل المراحل المسموحة</option></select></div>
                            <div class="col-md-3"><label class="form-label">إثبات الدفع</label><select class="form-select" name="config[proof_mode]"><?php $__currentLoopData = ['reference_or_receipt'=>'رقم عملية أو إيصال','reference'=>'رقم عملية فقط','receipt'=>'إيصال فقط','none'=>'بدون إثبات']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k=>$v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($k); ?>" <?php if($method->proofMode()===$k): echo 'selected'; endif; ?>><?php echo e($v); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
                            <div class="col-md-3"><label class="form-label">العملة</label><input class="form-control ltr" name="config[currency]" value="<?php echo e($cfg['currency'] ?? 'LYD'); ?>"></div>
                        </div>

                        <?php if(!empty($schema['fields'])): ?>
                            <div class="payment-config-section">
                                <h3 class="h6 fw-bold mb-3">بيانات <?php echo e($schema['title'] ?? 'الدفع'); ?></h3>
                                <div class="row g-3">
                                <?php $__currentLoopData = $schema['fields']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key=>$field): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php ($isSecret = (bool)($field['secret']??false)); ?>
                                    <div class="<?php echo e(($field['type']??'text')==='textarea' ? 'col-12' : 'col-md-6'); ?>">
                                        <label class="form-label"><?php echo e($field['label'] ?? $key); ?></label>
                                        <?php if(($field['type']??'text')==='textarea'): ?>
                                            <textarea class="form-control <?php echo e(!empty($field['ltr'])?'ltr':''); ?>" name="config[<?php echo e($key); ?>]" rows="3"><?php echo e($cfg[$key] ?? ''); ?></textarea>
                                        <?php else: ?>
                                            <input class="form-control <?php echo e(!empty($field['ltr'])?'ltr':''); ?>" type="<?php echo e($isSecret ? 'password' : (($field['type']??'text')==='url'?'url':'text')); ?>" name="config[<?php echo e($key); ?>]" value="<?php echo e($isSecret ? '' : ($cfg[$key] ?? '')); ?>" <?php if($isSecret): ?> placeholder="اتركه فارغًا للاحتفاظ بالقيمة الحالية" <?php endif; ?>>
                                        <?php endif; ?>
                                        <?php if($isSecret && !empty($cfg[$key])): ?><div class="form-text text-success">محفوظ ومشفر — اترك الحقل فارغًا للاحتفاظ به.</div><?php endif; ?>
                                    </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="mt-4"><label class="form-label">تعليمات تظهر للعميل</label><textarea class="form-control" name="instructions" rows="3"><?php echo e($method->instructions); ?></textarea></div>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <div class="small <?php echo e($ready ? 'text-success' : 'text-warning'); ?>"><?php echo e($ready ? 'الإعداد مكتمل ويمكن تفعيل الطريقة.' : implode(' ', $issues)); ?></div>
                        <div class="d-flex gap-2"><button type="button" class="btn btn-light" data-bs-dismiss="modal">إلغاء</button><button class="btn btn-primary" type="submit">حفظ الإعدادات</button></div>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
    <div class="surface-card p-4 text-center text-secondary">لا توجد طرق دفع. اضغط تثبيت/تحديث الدليل الليبي.</div>
<?php endif; ?>
</div>

<script>
(() => {
    const search = document.querySelector('[data-payment-filter="search"]');
    const buttons = [...document.querySelectorAll('[data-filter]')];
    const cards = [...document.querySelectorAll('[data-payment-card]')];
    let filter = 'all';

    const apply = () => {
        const q = (search?.value || '').trim().toLowerCase();
        cards.forEach(card => {
            const stateOk = filter === 'all' || card.dataset.state === filter || card.dataset.readiness === filter;
            const searchOk = !q || (card.dataset.search || '').includes(q);
            card.classList.toggle('d-none', !(stateOk && searchOk));
        });
    };

    search?.addEventListener('input', apply);
    buttons.forEach(btn => btn.addEventListener('click', () => {
        filter = btn.dataset.filter;
        buttons.forEach(b => { b.classList.remove('btn-primary','active'); b.classList.add('btn-light'); });
        btn.classList.remove('btn-light'); btn.classList.add('btn-primary','active');
        apply();
    }));
})();
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\laragon\www\Salltak\resources\views/admin/payment-methods/index.blade.php ENDPATH**/ ?>