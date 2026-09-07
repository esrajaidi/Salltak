@extends('layouts.admin')
@section('title',$order->number)
@section('admin-content')
@php
$statusLabels=['submitted'=>'تم الإرسال','under_review'=>'تحت المراجعة','needs_customer_action'=>'يحتاج رد العميل','approved'=>'معتمد','awaiting_deposit'=>'بانتظار العربون','awaiting_payment'=>'بانتظار الدفع','deposit_paid'=>'العربون مدفوع','purchasing'=>'جاري الشراء','ordered'=>'تم الطلب من المتجر','shipped'=>'جاري الشحن','arrived_libya'=>'وصل ليبيا','awaiting_balance'=>'بانتظار باقي المبلغ','ready_for_delivery'=>'جاهز للتسليم','out_for_delivery'=>'خرج للتسليم','delivered'=>'تم التسليم','rejected'=>'مرفوض','cancelled'=>'ملغي'];
$itemLabels=['pending'=>'بانتظار المراجعة','approved'=>'تمام','unavailable'=>'غير متوفر','price_changed'=>'السعر تغير','option_issue'=>'مشكلة لون/مقاس','rejected'=>'مرفوض'];
@endphp
<div class="admin-page-header reveal is-visible d-flex flex-column flex-lg-row align-items-lg-end justify-content-between gap-3 mb-4">
<div><div class="small text-primary fw-bold mb-1">إدارة الطلب</div><h1 class="page-heading ltr text-end">{{ $order->number }}</h1><p class="page-subtitle">{{ $order->user->name }} — {{ $order->user->phone ?: $order->user->email }}</p></div>
<div class="d-flex gap-2 flex-wrap"><span class="status-badge status-primary">{{ $statusLabels[$order->status]??$order->status }}</span><span class="status-badge status-success">{{ $order->payment_status }}</span><a class="btn btn-outline-primary" href="{{ route('admin.orders.index') }}">كل الطلبات</a></div>
</div>

<div class="row g-3 mb-4">
<div class="col-6 col-xl-3"><div class="summary-tile"><div class="summary-label">الإجمالي</div><div class="summary-value">{{ number_format((float)$order->total_lyd,2) }} د.ل</div></div></div>
<div class="col-6 col-xl-3"><div class="summary-tile"><div class="summary-label">العربون</div><div class="summary-value">{{ number_format((float)$order->deposit_amount,2) }} د.ل</div></div></div>
<div class="col-6 col-xl-3"><div class="summary-tile"><div class="summary-label">المدفوع</div><div class="summary-value text-success">{{ number_format((float)$order->paid_amount,2) }} د.ل</div></div></div>
<div class="col-6 col-xl-3"><div class="summary-tile is-primary"><div class="summary-label">المتبقي</div><div class="summary-value text-primary">{{ number_format((float)$order->remaining_amount,2) }} د.ل</div></div></div>
</div>

<div class="row g-4">
<div class="col-xxl-8">
<section class="surface-card admin-panel p-3 p-md-4 mb-4 reveal">
<div class="d-flex align-items-center justify-content-between gap-3 mb-3"><div><h2 class="h5 panel-title mb-1">مراجعة المنتجات</h2><p class="small text-secondary mb-0">حدد حالة كل Item، والسعر الجديد إن تغير، واكتب السبب ليظهر للعميل.</p></div><span class="status-badge status-primary">{{ $order->items->count() }} منتج</span></div>
<div class="d-grid gap-3">
@foreach($order->items as $item)
<article class="admin-review-item">
<div class="d-flex gap-3 align-items-start">
@if($item->image_url)<img src="{{ $item->image_url }}" class="order-item-image" alt="">@else<div class="order-item-image product-image-placeholder">—</div>@endif
<div class="flex-grow-1 min-w-0"><div class="d-flex flex-wrap justify-content-between gap-2"><h3 class="h6 fw-bold mb-1">{{ $item->name }}</h3><span class="status-badge {{ in_array($item->review_status,['unavailable','price_changed','option_issue','rejected'],true)?'status-danger':'status-primary' }}">{{ $itemLabels[$item->review_status]??$item->review_status }}</span></div>
<div class="saved-product-meta d-flex flex-wrap gap-2">@if($item->color)<span>اللون: <strong>{{ $item->color }}</strong></span>@endif @if($item->size)<span>المقاس: <strong>{{ $item->size }}</strong></span>@endif @if($item->variant)<span>SKU: <strong class="ltr">{{ $item->variant }}</strong></span>@endif</div>
<div class="small text-secondary mt-2">السعر الحالي: {{ number_format((float)$item->unit_price_lyd,2) }} د.ل • الكمية: {{ $item->quantity }} @if($item->customer_decision) • رد العميل: <strong>{{ $item->customer_decision==='accept'?'موافق':'غير موافق' }}</strong>@endif</div>
@if($item->customer_reply)<div class="item-review-note mt-2"><strong>رد العميل:</strong> {{ $item->customer_reply }}</div>@endif
</div></div>
<form method="POST" action="{{ route('admin.orders.items.review',[$order,$item]) }}" class="row g-2 mt-3">@csrf @method('PATCH')
<div class="col-md-3"><select name="review_status" class="form-select" required>@foreach($itemLabels as $key=>$label)<option value="{{ $key }}" @selected($item->review_status===$key)>{{ $label }}</option>@endforeach</select></div>
<div class="col-md-3"><input class="form-control" type="number" step="0.01" min="0" name="reviewed_unit_price_lyd" value="{{ $item->reviewed_unit_price_lyd }}" placeholder="السعر الجديد د.ل"></div>
<div class="col-md"><input class="form-control" name="review_reason" value="{{ $item->review_reason }}" placeholder="السبب / الملاحظة"></div>
<div class="col-md-auto"><button class="btn btn-primary w-100">حفظ المراجعة</button></div>
</form>
@foreach($item->messages as $message)<div class="message-bubble mt-2"><strong>{{ $message->user->name }}:</strong> {{ $message->message }}<small>{{ $message->created_at->format('m-d H:i') }}</small></div>@endforeach
</article>
@endforeach
</div>
</section>

<section class="surface-card admin-panel p-3 p-md-4 mb-4 reveal">
<h2 class="h5 panel-title mb-3">الدفعات والتحقق</h2>
<div class="d-grid gap-3">@forelse($order->payments as $payment)
<div class="payment-admin-row"><div><div class="fw-bold">{{ number_format((float)$payment->amount,2) }} د.ل — {{ $payment->method?->name ?? 'طريقة محذوفة' }}</div><div class="small text-secondary ltr text-end">{{ $payment->number }} @if($payment->transaction_ref) • Ref: {{ $payment->transaction_ref }} @endif</div>@if($payment->receipt_path)<a target="_blank" href="{{ asset('storage/'.$payment->receipt_path) }}" class="small">عرض الإيصال</a>@endif @if($payment->notes)<div class="small mt-1">{{ $payment->notes }}</div>@endif @if($payment->rejection_reason)<div class="small text-danger">سبب الرفض: {{ $payment->rejection_reason }}</div>@endif</div>
<div><span class="status-badge {{ $payment->status==='verified'?'status-success':($payment->status==='rejected'?'status-danger':'status-primary') }}">{{ $payment->status }}</span></div>
@if(in_array($payment->status,['pending_verification','pending_gateway'],true))<form method="POST" action="{{ route('admin.orders.payments.verify',[$order,$payment]) }}" class="d-flex gap-2 flex-wrap">@csrf @method('PATCH')<input class="form-control form-control-sm" style="min-width:210px" name="reason" placeholder="سبب الرفض عند الحاجة"><button name="decision" value="verified" class="btn btn-success btn-sm">اعتماد</button><button name="decision" value="rejected" class="btn btn-danger-soft btn-sm">رفض</button></form>@endif</div>
@empty<div class="small text-secondary">لا توجد دفعات بعد.</div>@endforelse</div>
</section>

<section class="surface-card admin-panel p-3 p-md-4 mb-4 reveal">
<h2 class="h5 panel-title mb-3">المحادثة مع العميل</h2>
<div class="d-grid gap-2 mb-3">@forelse($order->messages->whereNull('order_item_id') as $message)<div class="message-bubble"><strong>{{ $message->user->name }}:</strong> {{ $message->message }}<small>{{ $message->created_at->format('Y-m-d H:i') }}</small></div>@empty<div class="small text-secondary">لا توجد رسائل عامة.</div>@endforelse</div>
<form method="POST" action="{{ route('admin.orders.messages.store',$order) }}" class="d-flex flex-column flex-md-row gap-2">@csrf<input class="form-control" name="message" required maxlength="2000" placeholder="رسالة للعميل"><button class="btn btn-primary">إرسال</button></form>
</section>
</div>

<div class="col-xxl-4">
<section class="surface-card admin-panel p-3 p-md-4 mb-4 reveal">
<h2 class="h5 panel-title mb-3">المسؤول عن الطلب</h2>
<form method="POST" action="{{ route('admin.orders.assign',$order) }}" class="d-grid gap-2">@csrf @method('PATCH')<select name="assigned_to" class="form-select"><option value="">غير مسند</option>@foreach($managers as $manager)<option value="{{ $manager->id }}" @selected($order->assigned_to===$manager->id)>{{ $manager->name }} — {{ $manager->role }}</option>@endforeach</select><button class="btn btn-soft">حفظ الإسناد</button></form>
</section>

<section class="surface-card admin-panel p-3 p-md-4 mb-4 reveal">
<h2 class="h5 panel-title mb-2">اعتماد الطلب وشروط الدفع</h2><p class="small text-secondary">بعد إنهاء مراجعة المنتجات، حدد العربون تلقائيًا أو يدويًا.</p>
<form method="POST" action="{{ route('admin.orders.approve',$order) }}" class="d-grid gap-3">@csrf
<div><label class="form-label">العربون</label><select name="deposit_mode" class="form-select"><option value="auto">تلقائي حسب القواعد</option><option value="percentage">نسبة يدوية</option><option value="fixed">مبلغ ثابت</option><option value="none">بدون عربون / دفع كامل لاحقًا</option></select></div>
<div><label class="form-label">القيمة عند الاختيار اليدوي</label><input type="number" step="0.01" min="0" name="deposit_value" class="form-control" placeholder="مثلاً 40 للنسبة أو 150 للمبلغ"></div>
<div><label class="form-label">ملاحظة شروط الدفع</label><textarea class="form-control" name="payment_terms_note" rows="2" placeholder="متى يستحق الباقي أو أي تعليمات"></textarea></div>
<button class="btn btn-primary">اعتماد وتحديد الدفع</button>
</form>
</section>

<section class="surface-card admin-panel p-3 p-md-4 mb-4 reveal">
<h2 class="h5 panel-title mb-2">تعديل العربون لطلب خاص</h2><p class="small text-secondary">يُسجل السبب في سجل الطلب.</p>
<form method="POST" action="{{ route('admin.orders.payment-terms',$order) }}" class="d-grid gap-2">@csrf @method('PATCH')<select name="deposit_mode" class="form-select"><option value="auto">حسب القواعد</option><option value="percentage">نسبة</option><option value="fixed">مبلغ ثابت</option><option value="none">بدون عربون</option></select><input type="number" step="0.01" min="0" name="deposit_value" class="form-control" placeholder="القيمة"><textarea name="reason" class="form-control" rows="2" required placeholder="سبب التعديل"></textarea><button class="btn btn-soft">تعديل شروط الدفع</button></form>
</section>

<section class="surface-card admin-panel p-3 p-md-4 mb-4 reveal">
<h2 class="h5 panel-title mb-3">تغيير حالة الطلب</h2>
<form method="POST" action="{{ route('admin.orders.status',$order) }}" class="d-grid gap-2">@csrf @method('PATCH')<select name="status" class="form-select">@foreach($statusLabels as $key=>$label)<option value="{{ $key }}" @selected($order->status===$key)>{{ $label }}</option>@endforeach</select><textarea name="reason" class="form-control" rows="2" placeholder="سبب/ملاحظة — إلزامي عند الرفض"></textarea><button class="btn btn-navy">تحديث الحالة</button></form>
@if((float)$order->remaining_amount>0)<div class="small text-danger mt-2">لن يسمح النظام بحالة «تم التسليم» قبل سداد {{ number_format((float)$order->remaining_amount,2) }} د.ل.</div>@endif
</section>

<section class="surface-card admin-panel p-3 p-md-4 reveal">
<h2 class="h5 panel-title mb-3">سجل الطلب</h2><div class="order-timeline">@foreach($order->histories->sortByDesc('created_at') as $history)<div class="timeline-entry"><span></span><div><strong>{{ $statusLabels[$history->to_status]??$history->to_status }}</strong><small>{{ $history->created_at->format('Y-m-d H:i') }} @if($history->user) • {{ $history->user->name }}@endif</small>@if($history->note)<p>{{ $history->note }}</p>@endif</div></div>@endforeach</div>
</section>
</div>
</div>
@endsection
