@extends('layouts.app')
@section('title',$order->number)
@section('body')
@php
$statusLabels = [
'submitted'=>'تم الإرسال','under_review'=>'تحت المراجعة','needs_customer_action'=>'يحتاج ردك','approved'=>'معتمد','awaiting_deposit'=>'بانتظار العربون','awaiting_payment'=>'بانتظار الدفع','deposit_paid'=>'العربون مدفوع','ready_for_purchase'=>'جاهز للشراء','purchasing'=>'جاري الشراء','ordered'=>'تم الطلب من المتجر','shipped'=>'جاري الشحن','arrived_libya'=>'وصل ليبيا','awaiting_balance'=>'بانتظار باقي المبلغ','ready_for_delivery'=>'جاهز للتسليم','out_for_delivery'=>'خرج للتسليم','delivered'=>'تم التسليم','rejected'=>'مرفوض','cancelled'=>'ملغي'];
$itemLabels=['pending'=>'بانتظار المراجعة','approved'=>'تمام','unavailable'=>'غير متوفر','price_changed'=>'السعر تغير','option_issue'=>'مشكلة في المنتج','rejected'=>'مرفوض'];
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
$statusRank=['submitted'=>0,'under_review'=>1,'needs_customer_action'=>1,'approved'=>2,'awaiting_deposit'=>3,'awaiting_payment'=>3,'deposit_paid'=>3,'ready_for_purchase'=>4,'purchasing'=>4,'ordered'=>4,'shipped'=>5,'arrived_libya'=>6,'awaiting_balance'=>6,'ready_for_delivery'=>6,'out_for_delivery'=>7,'delivered'=>7,'rejected'=>0,'cancelled'=>0];
$currentRank=$statusRank[$order->status] ?? 0;
$cancelStatuses = ['submitted','under_review','needs_customer_action','approved','awaiting_deposit','awaiting_payment','deposit_paid'];
$paidAmountForCancel = max(0, (float) $order->paid_amount);
$depositAmountForCancel = max(0, (float) $order->deposit_amount);
$forfeitedDepositAmount = min($paidAmountForCancel, $depositAmountForCancel);
$hasPaidDepositForCancel = $forfeitedDepositAmount > 0.009;
$hasPaymentBeyondDeposit = $paidAmountForCancel > $depositAmountForCancel + 0.009 || ($paidAmountForCancel > 0.009 && $depositAmountForCancel <= 0.009);
$canCustomerCancel = in_array($order->status, $cancelStatuses, true) && ! $hasPaymentBeyondDeposit;
$customerHistories=$order->histories->filter(fn($history)=>$history->event_type!=='note' || $history->visibility==='customer')->sortByDesc('created_at');
@endphp
<section class="page-section customer-page order-detail-premium">
<div class="container">
    <header class="order-hero-card reveal is-visible mb-4">
        <div class="order-hero-main">
            <div>
                <div class="page-kicker">متابعة الطلب</div>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2"><h1 class="page-heading ltr text-end mb-0">{{ $order->number }}</h1><span class="status-badge status-primary">{{ $statusLabels[$order->status] ?? $order->status }}</span></div>
                <p class="page-subtitle mb-0">{{ $order->cart?->store?->name ?? 'سلة تسوق' }} • أرسل {{ optional($order->submitted_at)->format('Y-m-d H:i') }}</p>
            </div>
            <div class="order-hero-payment"><small>حالة الدفع</small><strong>{{ $paymentLabels[$order->payment_status] ?? $order->payment_status }}</strong><span>{{ number_format((float)$order->paid_amount,2) }} / {{ number_format((float)$order->total_lyd,2) }} د.ل</span></div>
        </div>
    </header>

    @if($order->status==='rejected')<div class="alert alert-danger border-0 shadow-sm"><strong>تم رفض الطلب.</strong><div class="mt-1">السبب: {{ $order->rejection_reason }}</div></div>@endif
    @if($order->status==='needs_customer_action')<div class="alert alert-warning border-0 shadow-sm"><strong>مطلوب ردك.</strong> راجع المنتجات المعلّمة وحدد موافق أو غير موافق.</div>@endif

    @unless(in_array($order->status,['rejected','cancelled'],true))
    <section class="surface-card-elevated p-3 p-md-4 mb-4 reveal is-visible">
        <div class="order-progress" aria-label="مراحل الطلب">
            @foreach($progressSteps as $index=>$step)
                <div class="order-progress-step {{ $index < $currentRank ? 'is-done' : ($index === $currentRank ? 'is-current' : '') }}">
                    <span class="order-progress-dot">@if($index < $currentRank)<x-icon name="check" size="16"/>@else{{ $step['icon'] }}@endif</span><small>{{ $step['label'] }}</small>
                </div>
            @endforeach
        </div>
    </section>
    @endunless

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3"><div class="summary-tile h-100"><div class="summary-label">إجمالي الطلب</div><div class="summary-value">{{ number_format((float)$order->total_lyd,2) }} د.ل</div></div></div>
        <div class="col-6 col-xl-3"><div class="summary-tile h-100"><div class="summary-label">العربون</div><div class="summary-value">{{ number_format((float)$order->deposit_amount,2) }} د.ل</div></div></div>
        <div class="col-6 col-xl-3"><div class="summary-tile h-100"><div class="summary-label">المدفوع</div><div class="summary-value text-success">{{ number_format((float)$order->paid_amount,2) }} د.ل</div></div></div>
        <div class="col-6 col-xl-3"><div class="summary-tile is-primary h-100"><div class="summary-label">المتبقي</div><div class="summary-value text-primary">{{ number_format((float)$order->remaining_amount,2) }} د.ل</div></div></div>
    </div>

    <div class="row g-4 align-items-start">
        <div class="col-xl-8">
            <section class="surface-card-elevated p-3 p-md-4 reveal is-visible mb-4">
                <div class="d-flex flex-column flex-md-row justify-content-between gap-3 align-items-md-center mb-3">
                    <div><div class="page-kicker">تفاصيل السلة</div><h2 class="h5 fw-bold mb-1">منتجات الطلب</h2><p class="small text-secondary mb-0">الأسعار الأصلية من المتجر للعرض فقط ولا يمكن تعديلها من حساب العميل.</p></div>
                    <span class="status-badge status-primary">{{ $itemsPage->total() }} منتج</span>
                </div>
                <div class="d-grid gap-3">
                    @foreach($itemsPage as $item)
                        @php($issue=in_array($item->review_status,['unavailable','price_changed','option_issue','rejected'],true))
                        <article class="order-item-card premium-order-item {{ $issue?'has-issue':'' }}">
                            <div class="order-item-main">
                                @if($item->image_url)<img src="{{ $item->image_url }}" class="order-item-image" alt="{{ $item->name }}" loading="lazy">@else<div class="order-item-image product-image-placeholder">—</div>@endif
                                <div class="min-w-0 flex-grow-1">
                                    <div class="d-flex flex-wrap justify-content-between gap-2"><h3 class="h6 fw-bold mb-1">{{ $item->name }}</h3><span class="status-badge {{ $issue?'status-danger':'status-success' }}">{{ $itemLabels[$item->review_status] ?? $item->review_status }}</span></div>
                                    <div class="saved-product-meta d-flex flex-wrap gap-2 mt-2">@if($item->color)<span>اللون: <strong>{{ $item->color }}</strong></span>@endif @if($item->size)<span>المقاس: <strong>{{ $item->size }}</strong></span>@endif @if($item->variant)<span>رمز الخيار: <strong class="ltr">{{ $item->variant }}</strong></span>@endif <span>الكمية: <strong>{{ $item->quantity }}</strong></span></div>
                                    <div class="readonly-price mt-3">
                                        <div><small>السعر بالدولار</small><strong class="ltr">{{ number_format((float)$item->unit_price_original,2) }} {{ strtoupper((string)($item->currency ?: 'USD')) === 'USD' ? 'دولار' : ($item->currency ?: 'عملة المصدر') }}</strong></div>
                                        <div><small>السعر بالدينار</small><strong>{{ number_format((float)($item->reviewed_unit_price_lyd ?? $item->unit_price_lyd),2) }} د.ل</strong></div>
                                        <div><small>إجمالي الصنف</small><strong>{{ number_format((float)$item->line_total_lyd,2) }} د.ل</strong></div>
                                    </div>
                                    @if($item->reviewed_unit_price_lyd !== null && (float)$item->reviewed_unit_price_lyd !== (float)$item->unit_price_lyd)
                                        <div class="price-change-caption">السعر قبل مراجعة المسؤول: {{ number_format((float)$item->unit_price_lyd,2) }} د.ل</div>
                                    @endif
                                </div>
                            </div>
                            @if($item->review_reason)<div class="item-review-note"><strong>ملاحظة المسؤول:</strong> {{ $item->review_reason }}</div>@endif
                            @if(in_array($item->review_status,['unavailable','price_changed','option_issue'],true))
                                <form method="POST" action="{{ route('orders.items.reply',[$order,$item]) }}" class="row g-2 mt-2" data-confirm data-confirm-title="تأكيد ردك" data-confirm-text="سيتم إرسال هذا القرار لمسؤول الطلب.">@csrf @method('PATCH')
                                    <div class="col-md-3"><select name="decision" class="form-select" required><option value="">ردك</option><option value="accept" @selected($item->customer_decision==='accept')>موافق</option><option value="reject" @selected($item->customer_decision==='reject')>غير موافق</option></select></div>
                                    <div class="col-md"><input name="reply" class="form-control" value="{{ $item->customer_reply }}" placeholder="اكتب ردك أو البديل المطلوب"></div>
                                    <div class="col-md-auto"><button class="btn btn-primary w-100" type="submit">إرسال الرد</button></div>
                                </form>
                            @endif
                            @foreach($item->messages as $message)<div class="message-bubble mt-2"><strong>{{ $message->user->name }}:</strong> {{ $message->message }}<small>{{ $message->created_at->format('m-d H:i') }}</small></div>@endforeach
                        </article>
                    @endforeach
                </div>
                @if($itemsPage->hasPages())<div class="mt-4 pagination-shell">{{ $itemsPage->links() }}</div>@endif
            </section>

            <section class="surface-card-elevated p-3 p-md-4 reveal is-visible mb-4">
                <div class="d-flex align-items-center justify-content-between gap-2 mb-3"><div><div class="page-kicker">آخر التطورات</div><h2 class="h5 fw-bold mb-0">سجل الطلب</h2></div><span class="status-badge status-primary">{{ $customerHistories->count() }} حركة</span></div>
                <div class="customer-activity-timeline order-timeline">
                    @forelse($customerHistories as $history)
                        @php($isNote=$history->event_type==='note')
                        <div class="timeline-entry {{ $isNote?'is-note':'' }}"><span></span><div>
                            <strong>{{ $isNote ? 'ملاحظة على الطلب' : (($statusLabels[$history->to_status] ?? $history->to_status)) }}</strong>
                            <small>{{ $history->user?->name ?? 'النظام' }} • {{ $history->created_at->format('Y-m-d H:i') }}</small>
                            @if($history->note && $history->visibility==='customer')<p>{{ $history->note }}</p>@endif
                        </div></div>
                    @empty<div class="small text-secondary">لا توجد حركات مسجلة بعد.</div>@endforelse
                </div>
            </section>

            <section class="surface-card-elevated p-3 p-md-4 reveal is-visible mb-4">
                <div class="page-kicker">تواصل مباشر</div><h2 class="h5 fw-bold mb-3">المحادثة مع المسؤول</h2>
                <div class="d-grid gap-2 mb-3">@forelse($order->messages->whereNull('order_item_id') as $message)<div class="message-bubble"><strong>{{ $message->user->name }}:</strong> {{ $message->message }}<small>{{ $message->created_at->format('Y-m-d H:i') }}</small></div>@empty<div class="small text-secondary">لا توجد رسائل عامة بعد.</div>@endforelse</div>
                <form method="POST" action="{{ route('orders.messages.store',$order) }}" class="d-flex flex-column flex-md-row gap-2">@csrf<input name="message" class="form-control" required maxlength="2000" placeholder="اكتب سؤالك أو ملاحظتك للمسؤول"><button class="btn btn-primary icon-text-btn" type="submit"><x-icon name="message" size="17" /> إرسال</button></form>
            </section>
        </div>

        <aside class="col-xl-4">
            <div class="order-sticky-rail">
                <section class="surface-card-elevated p-3 p-md-4 reveal is-visible mb-4">
                    <div class="page-kicker">الحساب</div><h2 class="h5 fw-bold mb-3">ملخص الدفع</h2>
                    <div class="order-finance-list"><div><span>الإجمالي</span><strong>{{ number_format((float)$order->total_lyd,2) }} د.ل</strong></div><div><span>العربون</span><strong>{{ number_format((float)$order->deposit_amount,2) }} د.ل</strong></div><div><span>المدفوع</span><strong class="text-success">{{ number_format((float)$order->paid_amount,2) }} د.ل</strong></div><div class="is-total"><span>المتبقي</span><strong>{{ number_format((float)$order->remaining_amount,2) }} د.ل</strong></div></div>
                    @if($order->payment_terms_note)<div class="small text-secondary mt-3">{{ $order->payment_terms_note }}</div>@endif
                </section>

                @if($canPay)
                <section class="surface-card-elevated p-3 p-md-4 reveal is-visible mb-4">
                    <h2 class="h5 fw-bold mb-3">إرسال دفعة</h2>
                    <div class="payment-due-box mb-3"><span>{{ $depositDue>0?'العربون المستحق الآن':'الرصيد المتبقي' }}</span><strong>{{ number_format($depositDue>0?$depositDue:(float)$order->remaining_amount,2) }} د.ل</strong></div>
                    @if($paymentMethods->isEmpty())<div class="alert alert-warning mb-0">لا توجد طريقة دفع مفعلة لهذا المبلغ حاليًا.</div>@else
                    <form method="POST" action="{{ route('orders.payments.store',$order) }}" enctype="multipart/form-data" class="d-grid gap-3" data-confirm data-confirm-title="تأكيد إرسال الدفعة" data-confirm-text="تأكد من المبلغ وطريقة الدفع قبل الإرسال.">@csrf
                        <div><label class="form-label fw-bold">طريقة الدفع</label><div class="payment-choice-grid">
                        @foreach($paymentMethods as $method)
                            @php($details=$method->customerDetails())
                            @php($externalUrl=$method->externalPaymentUrl())
                            @php($qrImage=$method->qrImageUrl())
                            <label class="payment-choice-card"><input class="form-check-input mt-1" type="radio" name="payment_method_id" value="{{ $method->id }}" required @checked(old('payment_method_id')==$method->id)><span class="payment-choice-body"><span class="d-flex justify-content-between gap-2"><strong>{{ $method->name }}</strong>@if((float)$method->fee_value>0)<small>{{ $method->fee_type==='percentage'?$method->fee_value.'%':number_format((float)$method->fee_value,2).' د.ل' }}</small>@endif</span>@if($method->instructions)<small class="d-block text-secondary mt-1">{{ $method->instructions }}</small>@endif @if($method->type==='cash')<span class="cash-payment-note"><x-icon name="cash" size="16" />الدفع النقدي يُسجّل كدفعة معلقة، ولا يُعتبر مدفوعًا إلا بعد أن يؤكد المسؤول استلام المبلغ.</span>@endif @if($details)<span class="payment-choice-details mt-2">@foreach($details as $key=>$value)<small><span>{{ $method->customerDetailLabels()[$key] ?? $key }}</span><strong class="ltr">{{ $value }}</strong></small>@endforeach</span>@endif @if($qrImage)<span class="d-block mt-2"><img src="{{ $qrImage }}" alt="رمز دفع {{ $method->name }}" class="payment-qr-preview"></span>@endif @if($externalUrl)<span class="d-block mt-2"><a href="{{ $externalUrl }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary" onclick="event.stopPropagation()">فتح بوابة الدفع الخارجية</a></span>@endif</span></label>
                        @endforeach
                        </div></div>
                        <div><label class="form-label fw-bold">المبلغ</label><input type="number" step="0.01" min="1" max="{{ (float)$order->remaining_amount }}" name="amount" class="form-control" value="{{ old('amount', number_format($depositDue>0?$depositDue:(float)$order->remaining_amount,2,'.','')) }}" required></div>
                        <div><label class="form-label">رقم العملية</label><input name="transaction_ref" class="form-control ltr text-end" value="{{ old('transaction_ref') }}" placeholder="حسب طريقة الدفع"></div>
                        <div><label class="form-label">إيصال الدفع</label><input type="file" name="receipt" class="form-control" accept=".jpg,.jpeg,.png,.pdf"></div>
                        <div><label class="form-label">ملاحظة</label><textarea name="notes" class="form-control" rows="2" maxlength="1000">{{ old('notes') }}</textarea></div>
                        <button class="btn btn-primary btn-lg icon-text-btn" type="submit"><x-icon name="payment" size="18" /> إرسال الدفعة للتحقق</button>
                    </form>
                    @endif
                </section>
                @endif

                <section class="surface-card-elevated p-3 p-md-4 reveal is-visible mb-4">
                    <h2 class="h6 fw-bold mb-3">الدفعات المسجلة</h2>
                    <div class="d-grid gap-2">@forelse($order->payments as $payment)<div class="payment-row"><div><strong>{{ number_format((float)$payment->amount,2) }} د.ل</strong><small>{{ $payment->method?->name }} • {{ $payment->number }}</small></div><span class="status-badge {{ $payment->status==='verified'?'status-success':($payment->status==='rejected'?'status-danger':'status-warning') }}">{{ $payment->status==='verified'?'معتمدة':($payment->status==='rejected'?'مرفوضة':'تحت التحقق') }}</span></div>@empty<div class="small text-secondary">لا توجد دفعات بعد.</div>@endforelse</div>
                </section>

                @if($canCustomerCancel)
                <section class="surface-card p-3 p-md-4 mt-3 order-cancel-panel">
                    <h2 class="h6 fw-bold text-danger mb-2">إلغاء الطلب</h2>
                    <p class="small text-secondary mb-3">يرجى كتابة سبب الإلغاء. سيتم حفظ السبب ضمن سجل الطلب.</p>
                    <form method="POST" action="{{ route('orders.cancel',$order) }}"
                          data-confirm
                          data-confirm-title="تأكيد إلغاء الطلب"
                          data-confirm-text="{{ $hasPaidDepositForCancel ? 'سيؤدي إلغاء الطلب إلى فقدان العربون المدفوع وفق سياسة الإلغاء. هل تريد المتابعة؟' : 'سيتم إلغاء الطلب وتسجيل سبب الإلغاء في سجل الطلب. هل تريد المتابعة؟' }}"
                          data-confirm-button="نعم، إلغاء الطلب"
                          data-confirm-icon="warning">
                        @csrf
                        @method('PATCH')
                        <div class="mb-3">
                            <label class="form-label fw-bold" for="cancellation_reason">سبب الإلغاء</label>
                            <textarea id="cancellation_reason" name="reason" class="form-control" rows="3" maxlength="1500" required placeholder="اكتب سبب إلغاء الطلب بوضوح">{{ old('reason') }}</textarea>
                        </div>

                        @if($hasPaidDepositForCancel)
                        <div class="form-check text-end mb-3">
                            <input class="form-check-input" type="checkbox" value="1" id="cancellation_policy_acknowledged" name="cancellation_policy_acknowledged" required>
                            <label class="form-check-label fw-semibold" for="cancellation_policy_acknowledged">
                                أقر بأنني قرأت سياسة الإلغاء وأفهم أن العربون غير قابل للاسترداد عند إلغاء الطلب.
                            </label>
                        </div>
                        @endif

                        <button class="btn btn-outline-danger w-100" type="submit">إلغاء الطلب</button>

                        @if($hasPaidDepositForCancel)
                        <div class="alert alert-warning small mt-3 mb-0" role="alert">
                            <strong>تنبيه:</strong> في حال إلغاء الطلب بعد دفع العربون، فإن العربون غير قابل للاسترداد وفق سياسة الإلغاء المعتمدة.
                            قيمة العربون المدفوع: <strong>{{ number_format($forfeitedDepositAmount,2) }} د.ل</strong>،
                            ويتم الاحتفاظ به لتغطية تكاليف تجهيز ومعالجة الطلب.
                        </div>
                        @endif
                    </form>
                </section>
                @elseif(in_array($order->status, $cancelStatuses, true) && $hasPaymentBeyondDeposit)
                <div class="alert alert-info small mt-3 mb-0">
                    تم تسجيل دفعة تتجاوز قيمة العربون على هذا الطلب. لإتمام الإلغاء وتسوية المبلغ، يرجى التواصل مع المسؤول.
                </div>
                @endif
            </div>
        </aside>
    </div>
</div>
</section>
@endsection
