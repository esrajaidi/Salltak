<?php $__env->startSection('title',$order->number); ?>
<?php $__env->startSection('body'); ?>
<?php
$statusLabels = [
'submitted'=>'تم الإرسال','under_review'=>'تحت المراجعة','needs_customer_action'=>'يحتاج ردك','approved'=>'معتمد','awaiting_deposit'=>'بانتظار العربون','awaiting_payment'=>'بانتظار الدفع','deposit_paid'=>'العربون مدفوع','purchasing'=>'جاري الشراء','ordered'=>'تم الطلب من المتجر','shipped'=>'جاري الشحن','arrived_libya'=>'وصل ليبيا','awaiting_balance'=>'بانتظار باقي المبلغ','ready_for_delivery'=>'جاهز للتسليم','out_for_delivery'=>'خرج للتسليم','delivered'=>'تم التسليم','rejected'=>'مرفوض','cancelled'=>'ملغي'];
$itemLabels=['pending'=>'بانتظار المراجعة','approved'=>'تمام','unavailable'=>'غير متوفر','price_changed'=>'السعر تغير','option_issue'=>'مشكلة لون/مقاس','rejected'=>'مرفوض'];
$paymentLabels=['unpaid'=>'غير مدفوع','pending'=>'بانتظار التحقق','deposit_paid'=>'العربون مدفوع','partial'=>'مدفوع جزئيًا','paid'=>'مدفوع بالكامل','failed'=>'فشل','refunded'=>'مسترد'];
$canPay=in_array($order->status,['awaiting_deposit','awaiting_payment','deposit_paid','arrived_libya','awaiting_balance','ready_for_delivery','out_for_delivery'],true) && (float)$order->remaining_amount>0;
$depositDue=max(0,(float)$order->deposit_amount-(float)$order->paid_amount);
$progressSteps=[
 ['key'=>'submitted','label'=>'تم الإرسال','icon'=>'1'],
 ['key'=>'under_review','label'=>'المراجعة','icon'=>'2'],
 ['key'=>'approved','label'=>'الاعتماد','icon'=>'3'],
 ['key'=>'payment','label'=>'الدفع','icon'=>'4'],
 ['key'=>'purchasing','label'=>'الشراء','icon'=>'5'],
 ['key'=>'shipped','label'=>'الشحن','icon'=>'6'],
 ['key'=>'arrived_libya','label'=>'وصل ليبيا','icon'=>'7'],
 ['key'=>'delivered','label'=>'التسليم','icon'=>'8'],
];
$statusRank=['submitted'=>0,'under_review'=>1,'needs_customer_action'=>1,'approved'=>2,'awaiting_deposit'=>3,'awaiting_payment'=>3,'deposit_paid'=>3,'purchasing'=>4,'ordered'=>4,'shipped'=>5,'arrived_libya'=>6,'awaiting_balance'=>6,'ready_for_delivery'=>6,'out_for_delivery'=>7,'delivered'=>7,'rejected'=>0,'cancelled'=>0];
$currentRank=$statusRank[$order->status] ?? 0;
$customerHistories=$order->histories->filter(fn($history)=>$history->event_type!=='note' || $history->visibility==='customer')->sortByDesc('created_at');
?>
<section class="page-section customer-page order-detail-premium">
<div class="container">
    <header class="order-hero-card reveal is-visible mb-4">
        <div class="order-hero-main">
            <div>
                <div class="page-kicker">متابعة الطلب</div>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2"><h1 class="page-heading ltr text-end mb-0"><?php echo e($order->number); ?></h1><span class="status-badge status-primary"><?php echo e($statusLabels[$order->status] ?? $order->status); ?></span></div>
                <p class="page-subtitle mb-0"><?php echo e($order->cart?->store?->name ?? 'سلة تسوق'); ?> • أرسل <?php echo e(optional($order->submitted_at)->format('Y-m-d H:i')); ?></p>
            </div>
            <div class="order-hero-payment"><small>حالة الدفع</small><strong><?php echo e($paymentLabels[$order->payment_status] ?? $order->payment_status); ?></strong><span><?php echo e(number_format((float)$order->paid_amount,2)); ?> / <?php echo e(number_format((float)$order->total_lyd,2)); ?> د.ل</span></div>
        </div>
    </header>

    <?php if($order->status==='rejected'): ?><div class="alert alert-danger border-0 shadow-sm"><strong>تم رفض الطلب.</strong><div class="mt-1">السبب: <?php echo e($order->rejection_reason); ?></div></div><?php endif; ?>
    <?php if($order->status==='needs_customer_action'): ?><div class="alert alert-warning border-0 shadow-sm"><strong>مطلوب ردك.</strong> راجع المنتجات المعلّمة وحدد موافق أو غير موافق.</div><?php endif; ?>

    <?php if (! (in_array($order->status,['rejected','cancelled'],true))): ?>
    <section class="surface-card-elevated p-3 p-md-4 mb-4 reveal is-visible">
        <div class="order-progress" aria-label="مراحل الطلب">
            <?php $__currentLoopData = $progressSteps; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index=>$step): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="order-progress-step <?php echo e($index < $currentRank ? 'is-done' : ($index === $currentRank ? 'is-current' : '')); ?>">
                    <span class="order-progress-dot"><?php echo e($index < $currentRank ? '✓' : $step['icon']); ?></span><small><?php echo e($step['label']); ?></small>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </section>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3"><div class="summary-tile h-100"><div class="summary-label">إجمالي الطلب</div><div class="summary-value"><?php echo e(number_format((float)$order->total_lyd,2)); ?> د.ل</div></div></div>
        <div class="col-6 col-xl-3"><div class="summary-tile h-100"><div class="summary-label">العربون</div><div class="summary-value"><?php echo e(number_format((float)$order->deposit_amount,2)); ?> د.ل</div></div></div>
        <div class="col-6 col-xl-3"><div class="summary-tile h-100"><div class="summary-label">المدفوع</div><div class="summary-value text-success"><?php echo e(number_format((float)$order->paid_amount,2)); ?> د.ل</div></div></div>
        <div class="col-6 col-xl-3"><div class="summary-tile is-primary h-100"><div class="summary-label">المتبقي</div><div class="summary-value text-primary"><?php echo e(number_format((float)$order->remaining_amount,2)); ?> د.ل</div></div></div>
    </div>

    <div class="row g-4 align-items-start">
        <div class="col-xl-8">
            <section class="surface-card-elevated p-3 p-md-4 reveal is-visible mb-4">
                <div class="d-flex flex-column flex-md-row justify-content-between gap-3 align-items-md-center mb-3">
                    <div><div class="page-kicker">تفاصيل السلة</div><h2 class="h5 fw-bold mb-1">منتجات الطلب</h2><p class="small text-secondary mb-0">الأسعار الأصلية من المتجر للعرض فقط ولا يمكن تعديلها من حساب العميل.</p></div>
                    <span class="status-badge status-primary"><?php echo e($order->items->count()); ?> منتج</span>
                </div>
                <div class="d-grid gap-3">
                    <?php $__currentLoopData = $order->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php ($issue=in_array($item->review_status,['unavailable','price_changed','option_issue','rejected'],true)); ?>
                        <article class="order-item-card premium-order-item <?php echo e($issue?'has-issue':''); ?>">
                            <div class="order-item-main">
                                <?php if($item->image_url): ?><img src="<?php echo e($item->image_url); ?>" class="order-item-image" alt="<?php echo e($item->name); ?>" loading="lazy"><?php else: ?><div class="order-item-image product-image-placeholder">—</div><?php endif; ?>
                                <div class="min-w-0 flex-grow-1">
                                    <div class="d-flex flex-wrap justify-content-between gap-2"><h3 class="h6 fw-bold mb-1"><?php echo e($item->name); ?></h3><span class="status-badge <?php echo e($issue?'status-danger':'status-success'); ?>"><?php echo e($itemLabels[$item->review_status] ?? $item->review_status); ?></span></div>
                                    <div class="saved-product-meta d-flex flex-wrap gap-2 mt-2"><?php if($item->color): ?><span>اللون: <strong><?php echo e($item->color); ?></strong></span><?php endif; ?> <?php if($item->size): ?><span>المقاس: <strong><?php echo e($item->size); ?></strong></span><?php endif; ?> <?php if($item->variant): ?><span>SKU: <strong class="ltr"><?php echo e($item->variant); ?></strong></span><?php endif; ?> <span>الكمية: <strong><?php echo e($item->quantity); ?></strong></span></div>
                                    <div class="readonly-price mt-3">
                                        <div><small>السعر بالدولار</small><strong class="ltr"><?php echo e(number_format((float)$item->unit_price_original,2)); ?> <?php echo e($item->currency ?: 'USD'); ?></strong></div>
                                        <div><small>السعر بالدينار</small><strong><?php echo e(number_format((float)($item->reviewed_unit_price_lyd ?? $item->unit_price_lyd),2)); ?> د.ل</strong></div>
                                        <div><small>إجمالي الصنف</small><strong><?php echo e(number_format((float)$item->line_total_lyd,2)); ?> د.ل</strong></div>
                                    </div>
                                    <?php if($item->reviewed_unit_price_lyd !== null && (float)$item->reviewed_unit_price_lyd !== (float)$item->unit_price_lyd): ?>
                                        <div class="price-change-caption">السعر قبل مراجعة المسؤول: <?php echo e(number_format((float)$item->unit_price_lyd,2)); ?> د.ل</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if($item->review_reason): ?><div class="item-review-note"><strong>ملاحظة المسؤول:</strong> <?php echo e($item->review_reason); ?></div><?php endif; ?>
                            <?php if(in_array($item->review_status,['unavailable','price_changed','option_issue'],true)): ?>
                                <form method="POST" action="<?php echo e(route('orders.items.reply',[$order,$item])); ?>" class="row g-2 mt-2" data-confirm data-confirm-title="تأكيد ردك" data-confirm-text="سيتم إرسال هذا القرار لمسؤول الطلب."><?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                                    <div class="col-md-3"><select name="decision" class="form-select" required><option value="">ردك</option><option value="accept" <?php if($item->customer_decision==='accept'): echo 'selected'; endif; ?>>موافق</option><option value="reject" <?php if($item->customer_decision==='reject'): echo 'selected'; endif; ?>>غير موافق</option></select></div>
                                    <div class="col-md"><input name="reply" class="form-control" value="<?php echo e($item->customer_reply); ?>" placeholder="اكتب ردك أو البديل المطلوب"></div>
                                    <div class="col-md-auto"><button class="btn btn-primary w-100" type="submit">إرسال الرد</button></div>
                                </form>
                            <?php endif; ?>
                            <?php $__currentLoopData = $item->messages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $message): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><div class="message-bubble mt-2"><strong><?php echo e($message->user->name); ?>:</strong> <?php echo e($message->message); ?><small><?php echo e($message->created_at->format('m-d H:i')); ?></small></div><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </article>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </section>

            <section class="surface-card-elevated p-3 p-md-4 reveal is-visible mb-4">
                <div class="d-flex align-items-center justify-content-between gap-2 mb-3"><div><div class="page-kicker">آخر التطورات</div><h2 class="h5 fw-bold mb-0">سجل الطلب</h2></div><span class="status-badge status-primary"><?php echo e($customerHistories->count()); ?> حركة</span></div>
                <div class="customer-activity-timeline order-timeline">
                    <?php $__empty_1 = true; $__currentLoopData = $customerHistories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $history): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php ($isNote=$history->event_type==='note'); ?>
                        <div class="timeline-entry <?php echo e($isNote?'is-note':''); ?>"><span></span><div>
                            <strong><?php echo e($isNote ? 'ملاحظة على الطلب' : (($statusLabels[$history->to_status] ?? $history->to_status))); ?></strong>
                            <small><?php echo e($history->user?->name ?? 'النظام'); ?> • <?php echo e($history->created_at->format('Y-m-d H:i')); ?></small>
                            <?php if($history->note && $history->visibility==='customer'): ?><p><?php echo e($history->note); ?></p><?php endif; ?>
                        </div></div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><div class="small text-secondary">لا توجد حركات مسجلة بعد.</div><?php endif; ?>
                </div>
            </section>

            <section class="surface-card-elevated p-3 p-md-4 reveal is-visible mb-4">
                <div class="page-kicker">تواصل مباشر</div><h2 class="h5 fw-bold mb-3">المحادثة مع المسؤول</h2>
                <div class="d-grid gap-2 mb-3"><?php $__empty_1 = true; $__currentLoopData = $order->messages->whereNull('order_item_id'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $message): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><div class="message-bubble"><strong><?php echo e($message->user->name); ?>:</strong> <?php echo e($message->message); ?><small><?php echo e($message->created_at->format('Y-m-d H:i')); ?></small></div><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><div class="small text-secondary">لا توجد رسائل عامة بعد.</div><?php endif; ?></div>
                <form method="POST" action="<?php echo e(route('orders.messages.store',$order)); ?>" class="d-flex flex-column flex-md-row gap-2"><?php echo csrf_field(); ?><input name="message" class="form-control" required maxlength="2000" placeholder="اكتب سؤالك أو ملاحظتك للمسؤول"><button class="btn btn-primary" type="submit">إرسال</button></form>
            </section>
        </div>

        <aside class="col-xl-4">
            <div class="order-sticky-rail">
                <section class="surface-card-elevated p-3 p-md-4 reveal is-visible mb-4">
                    <div class="page-kicker">الحساب</div><h2 class="h5 fw-bold mb-3">ملخص الدفع</h2>
                    <div class="order-finance-list"><div><span>الإجمالي</span><strong><?php echo e(number_format((float)$order->total_lyd,2)); ?> د.ل</strong></div><div><span>العربون</span><strong><?php echo e(number_format((float)$order->deposit_amount,2)); ?> د.ل</strong></div><div><span>المدفوع</span><strong class="text-success"><?php echo e(number_format((float)$order->paid_amount,2)); ?> د.ل</strong></div><div class="is-total"><span>المتبقي</span><strong><?php echo e(number_format((float)$order->remaining_amount,2)); ?> د.ل</strong></div></div>
                    <?php if($order->payment_terms_note): ?><div class="small text-secondary mt-3"><?php echo e($order->payment_terms_note); ?></div><?php endif; ?>
                </section>

                <?php if($canPay): ?>
                <section class="surface-card-elevated p-3 p-md-4 reveal is-visible mb-4">
                    <h2 class="h5 fw-bold mb-3">إرسال دفعة</h2>
                    <div class="payment-due-box mb-3"><span><?php echo e($depositDue>0?'العربون المستحق الآن':'الرصيد المتبقي'); ?></span><strong><?php echo e(number_format($depositDue>0?$depositDue:(float)$order->remaining_amount,2)); ?> د.ل</strong></div>
                    <?php if($paymentMethods->isEmpty()): ?><div class="alert alert-warning mb-0">لا توجد طريقة دفع مفعلة لهذا المبلغ حاليًا.</div><?php else: ?>
                    <form method="POST" action="<?php echo e(route('orders.payments.store',$order)); ?>" enctype="multipart/form-data" class="d-grid gap-3" data-confirm data-confirm-title="تأكيد إرسال الدفعة" data-confirm-text="تأكد من المبلغ وطريقة الدفع قبل الإرسال."><?php echo csrf_field(); ?>
                        <div><label class="form-label fw-bold">طريقة الدفع</label><div class="payment-choice-grid">
                        <?php $__currentLoopData = $paymentMethods; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $method): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php ($details=$method->customerDetails()); ?>
                            <?php ($externalUrl=$method->externalPaymentUrl()); ?>
                            <?php ($qrImage=$method->qrImageUrl()); ?>
                            <label class="payment-choice-card"><input class="form-check-input mt-1" type="radio" name="payment_method_id" value="<?php echo e($method->id); ?>" required <?php if(old('payment_method_id')==$method->id): echo 'checked'; endif; ?>><span class="payment-choice-body"><span class="d-flex justify-content-between gap-2"><strong><?php echo e($method->name); ?></strong><?php if((float)$method->fee_value>0): ?><small><?php echo e($method->fee_type==='percentage'?$method->fee_value.'%':number_format((float)$method->fee_value,2).' د.ل'); ?></small><?php endif; ?></span><?php if($method->instructions): ?><small class="d-block text-secondary mt-1"><?php echo e($method->instructions); ?></small><?php endif; ?> <?php if($details): ?><span class="payment-choice-details mt-2"><?php $__currentLoopData = $details; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key=>$value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><small><span><?php echo e($method->customerDetailLabels()[$key] ?? $key); ?></span><strong class="ltr"><?php echo e($value); ?></strong></small><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></span><?php endif; ?> <?php if($qrImage): ?><span class="d-block mt-2"><img src="<?php echo e($qrImage); ?>" alt="QR <?php echo e($method->name); ?>" class="payment-qr-preview"></span><?php endif; ?> <?php if($externalUrl): ?><span class="d-block mt-2"><a href="<?php echo e($externalUrl); ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary" onclick="event.stopPropagation()">فتح بوابة الدفع الخارجية</a></span><?php endif; ?></span></label>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div></div>
                        <div><label class="form-label fw-bold">المبلغ</label><input type="number" step="0.01" min="1" max="<?php echo e((float)$order->remaining_amount); ?>" name="amount" class="form-control" value="<?php echo e(old('amount', number_format($depositDue>0?$depositDue:(float)$order->remaining_amount,2,'.',''))); ?>" required></div>
                        <div><label class="form-label">رقم العملية</label><input name="transaction_ref" class="form-control ltr text-end" value="<?php echo e(old('transaction_ref')); ?>" placeholder="حسب طريقة الدفع"></div>
                        <div><label class="form-label">إيصال الدفع</label><input type="file" name="receipt" class="form-control" accept=".jpg,.jpeg,.png,.pdf"></div>
                        <div><label class="form-label">ملاحظة</label><textarea name="notes" class="form-control" rows="2" maxlength="1000"><?php echo e(old('notes')); ?></textarea></div>
                        <button class="btn btn-primary btn-lg" type="submit">إرسال الدفعة للتحقق</button>
                    </form>
                    <?php endif; ?>
                </section>
                <?php endif; ?>

                <section class="surface-card-elevated p-3 p-md-4 reveal is-visible mb-4">
                    <h2 class="h6 fw-bold mb-3">الدفعات المسجلة</h2>
                    <div class="d-grid gap-2"><?php $__empty_1 = true; $__currentLoopData = $order->payments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $payment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><div class="payment-row"><div><strong><?php echo e(number_format((float)$payment->amount,2)); ?> د.ل</strong><small><?php echo e($payment->method?->name); ?> • <?php echo e($payment->number); ?></small></div><span class="status-badge <?php echo e($payment->status==='verified'?'status-success':($payment->status==='rejected'?'status-danger':'status-warning')); ?>"><?php echo e($payment->status==='verified'?'معتمدة':($payment->status==='rejected'?'مرفوضة':'تحت التحقق')); ?></span></div><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><div class="small text-secondary">لا توجد دفعات بعد.</div><?php endif; ?></div>
                </section>

                <?php if((float)$order->paid_amount<=0 && in_array($order->status,['submitted','under_review','needs_customer_action','approved','awaiting_deposit','awaiting_payment'],true)): ?>
                <form method="POST" action="<?php echo e(route('orders.cancel',$order)); ?>" data-confirm data-confirm-title="إلغاء الطلب" data-confirm-text="سيتم تسجيل الإلغاء في سجل الطلب." data-confirm-icon="warning"><?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?><button class="btn btn-outline-danger w-100" type="submit">إلغاء الطلب</button></form>
                <?php endif; ?>
            </div>
        </aside>
    </div>
</div>
</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\laragon\www\Salltak\resources\views/orders/show.blade.php ENDPATH**/ ?>