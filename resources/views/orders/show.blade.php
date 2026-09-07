@extends('layouts.app')
@section('title',$order->number)
@section('body')
@php
$statusLabels = [
'submitted'=>'تم الإرسال','under_review'=>'تحت المراجعة','needs_customer_action'=>'يحتاج ردك','approved'=>'معتمد','awaiting_deposit'=>'بانتظار العربون','awaiting_payment'=>'بانتظار الدفع','deposit_paid'=>'العربون مدفوع','purchasing'=>'جاري الشراء','ordered'=>'تم الطلب من المتجر','shipped'=>'جاري الشحن','arrived_libya'=>'وصل ليبيا','awaiting_balance'=>'بانتظار باقي المبلغ','ready_for_delivery'=>'جاهز للتسليم','out_for_delivery'=>'خرج للتسليم','delivered'=>'تم التسليم','rejected'=>'مرفوض','cancelled'=>'ملغي'];
$itemLabels=['pending'=>'بانتظار المراجعة','approved'=>'تمام','unavailable'=>'غير متوفر','price_changed'=>'السعر تغير','option_issue'=>'مشكلة لون/مقاس','rejected'=>'مرفوض'];
$paymentLabels=['unpaid'=>'غير مدفوع','pending'=>'بانتظار التحقق','deposit_paid'=>'العربون مدفوع','partial'=>'مدفوع جزئيًا','paid'=>'مدفوع بالكامل','failed'=>'فشل','refunded'=>'مسترد'];
$canPay=in_array($order->status,['awaiting_deposit','awaiting_payment','deposit_paid','arrived_libya','awaiting_balance','ready_for_delivery','out_for_delivery'],true) && (float)$order->remaining_amount>0;
$depositDue=max(0,(float)$order->deposit_amount-(float)$order->paid_amount);
@endphp
<section class="page-section customer-page">
<div class="container">
    <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-between gap-3 mb-4 reveal is-visible">
        <div><div class="page-kicker">تفاصيل الطلب</div><h1 class="page-heading ltr text-end">{{ $order->number }}</h1><p class="page-subtitle">{{ $order->cart?->store?->name ?? 'سلة تسوق' }} • أرسل {{ optional($order->submitted_at)->format('Y-m-d H:i') }}</p></div>
        <div class="d-flex flex-wrap gap-2"><span class="status-badge status-primary">{{ $statusLabels[$order->status] ?? $order->status }}</span><span class="status-badge status-success">{{ $paymentLabels[$order->payment_status] ?? $order->payment_status }}</span></div>
    </div>

    @if($order->status==='rejected')<div class="alert alert-danger border-0 shadow-sm"><strong>تم رفض الطلب.</strong><div class="mt-1">السبب: {{ $order->rejection_reason }}</div></div>@endif
    @if($order->status==='needs_customer_action')<div class="alert alert-warning border-0 shadow-sm"><strong>مطلوب ردك.</strong> راجع المنتجات المعلّمة أدناه وحدد موافق/غير موافق.</div>@endif

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3"><div class="summary-tile h-100"><div class="summary-label">إجمالي الطلب</div><div class="summary-value">{{ number_format((float)$order->total_lyd,2) }} د.ل</div></div></div>
        <div class="col-6 col-lg-3"><div class="summary-tile h-100"><div class="summary-label">العربون المطلوب</div><div class="summary-value">{{ number_format((float)$order->deposit_amount,2) }} د.ل</div></div></div>
        <div class="col-6 col-lg-3"><div class="summary-tile h-100"><div class="summary-label">المدفوع</div><div class="summary-value text-success">{{ number_format((float)$order->paid_amount,2) }} د.ل</div></div></div>
        <div class="col-6 col-lg-3"><div class="summary-tile is-primary h-100"><div class="summary-label">المتبقي</div><div class="summary-value text-primary">{{ number_format((float)$order->remaining_amount,2) }} د.ل</div></div></div>
    </div>

    <div class="row g-4">
        <div class="col-xl-8">
            <section class="surface-card-elevated p-3 p-md-4 reveal mb-4">
                <div class="d-flex justify-content-between gap-3 align-items-center mb-3"><div><h2 class="h5 fw-bold mb-1">منتجات الطلب</h2><p class="small text-secondary mb-0">كل ملاحظة أو تغيير يظهر على نفس المنتج ويمكنك الرد عليه.</p></div><span class="status-badge status-primary">{{ $order->items->count() }} منتج</span></div>
                <div class="d-grid gap-3">
                    @foreach($order->items as $item)
                        @php($issue=in_array($item->review_status,['unavailable','price_changed','option_issue'],true))
                        <article class="order-item-card {{ $issue?'has-issue':'' }}">
                            <div class="order-item-main">
                                @if($item->image_url)<img src="{{ $item->image_url }}" class="order-item-image" alt="{{ $item->name }}" loading="lazy">@else<div class="order-item-image product-image-placeholder">—</div>@endif
                                <div class="min-w-0 flex-grow-1">
                                    <div class="d-flex flex-wrap justify-content-between gap-2"><h3 class="h6 fw-bold mb-1">{{ $item->name }}</h3><span class="status-badge {{ $issue?'status-danger':'status-primary' }}">{{ $itemLabels[$item->review_status] ?? $item->review_status }}</span></div>
                                    <div class="saved-product-meta d-flex flex-wrap gap-2 mt-1">@if($item->color)<span>اللون: <strong>{{ $item->color }}</strong></span>@endif @if($item->size)<span>المقاس: <strong>{{ $item->size }}</strong></span>@endif @if($item->variant)<span>SKU: <strong class="ltr">{{ $item->variant }}</strong></span>@endif</div>
                                    <div class="small text-secondary mt-2">الكمية: {{ $item->quantity }} • السعر المعتمد: <strong>{{ number_format((float)($item->reviewed_unit_price_lyd ?? $item->unit_price_lyd),2) }} د.ل</strong></div>
                                </div>
                            </div>
                            @if($item->review_reason)<div class="item-review-note"><strong>ملاحظة المسؤول:</strong> {{ $item->review_reason }}</div>@endif
                            @if($issue)
                                <form method="POST" action="{{ route('orders.items.reply',[$order,$item]) }}" class="row g-2 mt-2">@csrf @method('PATCH')
                                    <div class="col-md-3"><select name="decision" class="form-select" required><option value="">ردك</option><option value="accept" @selected($item->customer_decision==='accept')>موافق</option><option value="reject" @selected($item->customer_decision==='reject')>غير موافق</option></select></div>
                                    <div class="col-md"><input name="reply" class="form-control" value="{{ $item->customer_reply }}" placeholder="اكتب ردك أو البديل المطلوب"></div>
                                    <div class="col-md-auto"><button class="btn btn-primary w-100" type="submit">إرسال الرد</button></div>
                                </form>
                            @endif
                            @foreach($item->messages as $message)<div class="message-bubble mt-2"><strong>{{ $message->user->name }}:</strong> {{ $message->message }}<small>{{ $message->created_at->format('m-d H:i') }}</small></div>@endforeach
                        </article>
                    @endforeach
                </div>
            </section>

            <section class="surface-card-elevated p-3 p-md-4 reveal mb-4">
                <h2 class="h5 fw-bold mb-3">المحادثة على الطلب</h2>
                <div class="d-grid gap-2 mb-3">@forelse($order->messages->whereNull('order_item_id') as $message)<div class="message-bubble"><strong>{{ $message->user->name }}:</strong> {{ $message->message }}<small>{{ $message->created_at->format('Y-m-d H:i') }}</small></div>@empty<div class="small text-secondary">لا توجد رسائل عامة بعد.</div>@endforelse</div>
                <form method="POST" action="{{ route('orders.messages.store',$order) }}" class="d-flex flex-column flex-md-row gap-2">@csrf<input name="message" class="form-control" required maxlength="2000" placeholder="اكتب سؤالك أو ملاحظتك للمسؤول"><button class="btn btn-primary" type="submit">إرسال</button></form>
            </section>
        </div>

        <div class="col-xl-4">
            <section class="surface-card-elevated p-3 p-md-4 reveal is-visible mb-4">
                <h2 class="h5 fw-bold mb-3">الدفع</h2>
                @if($order->payment_terms_note)<div class="small text-secondary mb-3">{{ $order->payment_terms_note }}</div>@endif
                @if($canPay)
                    <div class="payment-due-box mb-3"><span>{{ $depositDue>0?'العربون المستحق الآن':'الرصيد المتبقي' }}</span><strong>{{ number_format($depositDue>0?$depositDue:(float)$order->remaining_amount,2) }} د.ل</strong></div>
                    @if($paymentMethods->isEmpty())
                        <div class="alert alert-warning mb-0">لا توجد طريقة دفع مفعلة لهذا المبلغ حاليًا. تواصل مع إدارة سلتك.</div>
                    @else
                        <form method="POST" action="{{ route('orders.payments.store',$order) }}" enctype="multipart/form-data" class="d-grid gap-3">@csrf
                            <div>
                                <label class="form-label fw-bold">اختار طريقة الدفع المتاحة</label>
                                <div class="payment-choice-grid">
                                    @foreach($paymentMethods as $method)
                                        @php($details=$method->customerDetails())
                                        @php($detailLabels=$method->customerDetailLabels())
                                        @php($externalUrl=$method->externalPaymentUrl())
                                        @php($qrImage=$method->qrImageUrl())
                                        <label class="payment-choice-card">
                                            <input class="form-check-input mt-1" type="radio" name="payment_method_id" value="{{ $method->id }}" required @checked(old('payment_method_id')==$method->id)>
                                            <span class="payment-choice-body">
                                                <span class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                                                    <strong>{{ $method->name }}</strong>
                                                    @if((float)$method->fee_value>0)
                                                        <small class="status-badge status-primary">رسوم: {{ $method->fee_type==='percentage'?number_format((float)$method->fee_value,2).'%':number_format((float)$method->fee_value,2).' د.ل' }}</small>
                                                    @endif
                                                </span>
                                                @if($method->instructions)<small class="text-secondary d-block mt-1">{{ $method->instructions }}</small>@endif
                                                @if($details)
                                                    <span class="payment-choice-details mt-2">
                                                        @foreach($details as $key=>$value)
                                                            @continue($key==='qr_image_url')
                                                            <small><b>{{ $detailLabels[$key] ?? $key }}:</b> <span class="ltr">{{ $value }}</span></small>
                                                        @endforeach
                                                    </span>
                                                @endif
                                                @if($qrImage)
                                                    <span class="d-block mt-2"><img src="{{ $qrImage }}" alt="QR {{ $method->name }}" class="payment-qr-preview"></span>
                                                @endif
                                                @if($externalUrl)
                                                    <span class="d-block mt-2"><a class="btn btn-outline-primary btn-sm" href="{{ $externalUrl }}" target="_blank" rel="noopener" onclick="event.stopPropagation()">فتح بوابة الدفع</a></span>
                                                @endif
                                                @if($method->min_amount || $method->max_amount)
                                                    <small class="text-secondary d-block mt-2">الحدود: {{ $method->min_amount?number_format((float)$method->min_amount,2).' د.ل':'بدون حد أدنى' }} — {{ $method->max_amount?number_format((float)$method->max_amount,2).' د.ل':'بدون حد أعلى' }}</small>
                                                @endif
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                            <div><label class="form-label">المبلغ (د.ل)</label><input class="form-control" type="number" step="0.01" min="1" max="{{ (float)$order->remaining_amount }}" name="amount" value="{{ old('amount',number_format($depositDue>0?$depositDue:(float)$order->remaining_amount,2,'.','')) }}" required></div>
                            <div><label class="form-label">رقم العملية</label><input class="form-control" name="transaction_ref" value="{{ old('transaction_ref') }}" placeholder="اكتبه إذا كانت طريقة الدفع تعطي رقم عملية"></div>
                            <div><label class="form-label">إيصال الدفع</label><input class="form-control" type="file" name="receipt" accept=".jpg,.jpeg,.png,.pdf"><div class="form-text">بعض الطرق تطلب رقم العملية أو الإيصال. JPG / PNG / PDF حتى 5MB.</div></div>
                            <div><label class="form-label">ملاحظة</label><textarea class="form-control" name="notes" rows="2">{{ old('notes') }}</textarea></div>
                            <button class="btn btn-primary" type="submit">إرسال الدفعة للتحقق</button>
                        </form>
                    @endif
                @elseif((float)$order->remaining_amount<=0)
                    <div class="alert alert-success mb-0">تم سداد الطلب بالكامل.</div>
                @else
                    <div class="small text-secondary">سيظهر الدفع هنا بعد اعتماد الطلب وتحديد العربون أو المبلغ المطلوب.</div>
                @endif
            </section>

            <section class="surface-card-elevated p-3 p-md-4 reveal mb-4">
                <h2 class="h5 fw-bold mb-3">سجل الدفعات</h2>
                <div class="d-grid gap-2">@forelse($order->payments as $payment)<div class="payment-row"><div><strong>{{ number_format((float)$payment->amount,2) }} د.ل</strong><small>{{ $payment->method?->name }} • {{ $payment->number }}</small></div><span class="status-badge {{ $payment->status==='verified'?'status-success':($payment->status==='rejected'?'status-danger':'status-primary') }}">{{ $payment->status }}</span></div>@if($payment->rejection_reason)<div class="small text-danger">{{ $payment->rejection_reason }}</div>@endif @empty<div class="small text-secondary">لا توجد دفعات بعد.</div>@endforelse</div>
            </section>

            <section class="surface-card-elevated p-3 p-md-4 reveal mb-4">
                <h2 class="h5 fw-bold mb-3">تتبع الطلب</h2>
                <div class="order-timeline">@foreach($order->histories->sortByDesc('created_at') as $history)<div class="timeline-entry"><span></span><div><strong>{{ $statusLabels[$history->to_status] ?? $history->to_status }}</strong><small>{{ $history->created_at->format('Y-m-d H:i') }} @if($history->user) • {{ $history->user->name }} @endif</small>@if($history->note)<p>{{ $history->note }}</p>@endif</div></div>@endforeach</div>
            </section>

            @if(in_array($order->status,['submitted','under_review','needs_customer_action','approved','awaiting_deposit','awaiting_payment'],true) && (float)$order->paid_amount<=0)
                <form method="POST" action="{{ route('orders.cancel',$order) }}" onsubmit="return confirm('إلغاء الطلب؟')">@csrf @method('PATCH')<button class="btn btn-danger-soft w-100" type="submit">إلغاء الطلب</button></form>
            @endif
        </div>
    </div>
</div>
</section>
@endsection
