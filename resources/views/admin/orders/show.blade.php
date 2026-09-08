@extends('layouts.admin')
@section('title','إدارة '.$order->number)
@section('admin-content')
@php
$statusLabels=['submitted'=>'تم الإرسال','under_review'=>'تحت المراجعة','needs_customer_action'=>'يحتاج رد العميل','approved'=>'معتمد','awaiting_deposit'=>'بانتظار العربون','awaiting_payment'=>'بانتظار الدفع','deposit_paid'=>'العربون مدفوع','purchasing'=>'جاري الشراء','ordered'=>'تم الطلب من المتجر','shipped'=>'جاري الشحن','arrived_libya'=>'وصل ليبيا','awaiting_balance'=>'بانتظار باقي المبلغ','ready_for_delivery'=>'جاهز للتسليم','out_for_delivery'=>'خرج للتسليم','delivered'=>'تم التسليم','rejected'=>'مرفوض','cancelled'=>'ملغي'];
$itemLabels=['pending'=>'بانتظار المراجعة','approved'=>'معتمد','unavailable'=>'غير متوفر','price_changed'=>'السعر تغير','option_issue'=>'مشكلة في المنتج','rejected'=>'مرفوض'];
$paymentLabels=['unpaid'=>'غير مدفوع','pending'=>'بانتظار التحقق','deposit_paid'=>'العربون مدفوع','partial'=>'مدفوع جزئيًا','paid'=>'مدفوع بالكامل'];
$depositLabels=['auto'=>'تلقائي حسب القواعد','none'=>'بدون عربون','percentage'=>'نسبة مئوية','fixed'=>'مبلغ ثابت'];
$issueStatuses=['unavailable','price_changed','option_issue','rejected'];
@endphp
<div class="ops-workspace">
    <header class="admin-order-hero mb-4">
        <div class="d-flex flex-column flex-xl-row justify-content-between gap-3 align-items-xl-center">
            <div>
                <div class="page-kicker">مركز عمليات الطلب</div>
                <div class="d-flex flex-wrap align-items-center gap-2"><h1 class="h3 fw-black mb-0 ltr">{{ $order->number }}</h1><span class="status-badge status-primary">{{ $statusLabels[$order->status] ?? $order->status }}</span><span class="status-badge status-success">{{ $paymentLabels[$order->payment_status] ?? $order->payment_status }}</span></div>
                <div class="d-flex flex-wrap gap-3 mt-2 small text-secondary"><span>العميل: <strong>{{ $order->user->name }}</strong></span><span>{{ $order->user->email }}</span><span>المسؤول: <strong>{{ $order->assignee?->name ?? 'غير مسند' }}</strong></span><span>{{ $order->cart?->store?->name ?? '—' }}</span></div>
            </div>
            <div class="d-flex flex-wrap gap-2"><a class="btn btn-ghost icon-text-btn" href="{{ route('admin.orders.index') }}"><x-icon name="arrow-left" size="17" /> كل الطلبات</a><a class="btn btn-navy" href="{{ route('orders.show',$order) }}" target="_blank">عرض العميل</a></div>
        </div>
    </header>

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3"><div class="dashboard-stat h-100"><span class="metric-label">الإجمالي</span><strong class="metric-value">{{ number_format((float)$order->total_lyd,2) }}</strong><small>د.ل</small></div></div>
        <div class="col-6 col-xl-3"><div class="dashboard-stat h-100"><span class="metric-label">العربون</span><strong class="metric-value">{{ number_format((float)$order->deposit_amount,2) }}</strong><small>{{ $depositLabels[$order->deposit_type] ?? 'غير محدد' }}</small></div></div>
        <div class="col-6 col-xl-3"><div class="dashboard-stat h-100"><span class="metric-label">المدفوع</span><strong class="metric-value text-success">{{ number_format((float)$order->paid_amount,2) }}</strong><small>{{ $paymentLabels[$order->payment_status] ?? $order->payment_status }}</small></div></div>
        <div class="col-6 col-xl-3"><div class="dashboard-stat h-100 is-attention"><span class="metric-label">المتبقي</span><strong class="metric-value">{{ number_format((float)$order->remaining_amount,2) }}</strong><small>د.ل</small></div></div>
    </div>

    <div class="row g-4 align-items-start">
        <div class="col-xxl-8">
            <section class="admin-panel p-3 p-md-4 mb-4">
                <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 align-items-lg-center mb-3">
                    <div>
                        <div class="page-kicker">مراجعة المنتجات</div>
                        <h2 class="h5 panel-title mb-1">راجع الاستثناءات فقط</h2>
                        <p class="small text-secondary mb-0">كل المنتجات سليمة افتراضيًا. حدّد فقط المنتج الذي توجد به مشكلة، ثم أنهِ مراجعة الطلب مرة واحدة.</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="status-badge status-success">{{ $itemsPage->total() - $reviewSummary['issues'] }} دون مشكلة</span>
                        <span class="status-badge {{ $reviewSummary['issues'] ? 'status-danger' : 'status-primary' }}">{{ $reviewSummary['issues'] }} مشكلة</span>
                    </div>
                </div>

                <div class="review-guide mb-3">
                    <div><strong>طريقة العمل:</strong> لا تحتاج إلى اعتماد كل منتج على حدة.</div>
                    <div class="small">إذا كان المنتج سليمًا فاتركه كما هو. عند وجود مشكلة اضغط «تحديد مشكلة» وسجّل نوعها فقط.</div>
                </div>

                <div class="admin-item-grid">
                    @foreach($itemsPage as $item)
                    <article class="admin-review-item {{ in_array($item->review_status,$issueStatuses,true)?'has-issue':'' }}">
                        <div class="d-flex gap-3 align-items-start">
                            @if($item->image_url)<img src="{{ $item->image_url }}" class="admin-item-image" alt="{{ $item->name }}" loading="lazy">@else<div class="admin-item-image product-image-placeholder">—</div>@endif
                            <div class="min-w-0 flex-grow-1">
                                <div class="d-flex justify-content-between gap-2">
                                    <strong class="admin-item-title">{{ $item->name }}</strong>
                                    <span class="status-badge {{ in_array($item->review_status,$issueStatuses,true)?'status-danger':'status-success' }}">{{ in_array($item->review_status,$issueStatuses,true) ? 'توجد مشكلة' : 'سليم افتراضيًا' }}</span>
                                </div>
                                <div class="saved-product-meta d-flex flex-wrap gap-2 mt-2">@if($item->color)<span>اللون: <b>{{ $item->color }}</b></span>@endif @if($item->size)<span>المقاس: <b>{{ $item->size }}</b></span>@endif <span>الكمية: <b>{{ $item->quantity }}</b></span></div>
                                <div class="admin-item-prices mt-2"><span>الأصلي <b class="ltr">{{ number_format((float)$item->unit_price_original,2) }} {{ strtoupper((string)$item->currency)==='USD' ? 'دولار' : ($item->currency ?: 'عملة المصدر') }}</b></span><span>بالدينار <b>{{ number_format((float)$item->unit_price_lyd,2) }} د.ل</b></span>@if($item->reviewed_unit_price_lyd!==null)<span>السعر الجديد <b>{{ number_format((float)$item->reviewed_unit_price_lyd,2) }} د.ل</b></span>@endif</div>
                            </div>
                        </div>

                        @if(in_array($item->review_status,$issueStatuses,true))
                            <div class="item-review-note mt-3"><strong>المشكلة:</strong> {{ $item->review_reason ?: 'تم تسجيل مشكلة على المنتج.' }}</div>
                            @if($item->customer_decision)
                                <div class="small mt-2 {{ $item->customer_decision==='accept'?'text-success':'text-danger' }}"><strong>قرار العميل:</strong> {{ $item->customer_decision==='accept'?'موافق':'غير موافق' }} @if($item->customer_reply)• {{ $item->customer_reply }}@endif</div>
                            @endif
                        @endif

                        <div class="d-flex flex-wrap gap-2 mt-3">
                            <button class="btn {{ in_array($item->review_status,$issueStatuses,true)?'btn-outline-danger':'btn-outline-primary' }} btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#issueForm{{ $item->id }}" aria-expanded="false" aria-controls="issueForm{{ $item->id }}">{{ in_array($item->review_status,$issueStatuses,true) ? 'تعديل المشكلة' : 'تحديد مشكلة' }}</button>
                            @if(in_array($item->review_status,$issueStatuses,true))
                            <form method="POST" action="{{ route('admin.orders.items.review',[$order,$item]) }}" data-confirm data-confirm-title="إزالة المشكلة" data-confirm-text="سيعود المنتج إلى الحالة السليمة الافتراضية.">@csrf @method('PATCH')<input type="hidden" name="review_status" value="pending"><button class="btn btn-ghost btn-sm" type="submit">إزالة المشكلة</button></form>
                            @endif
                        </div>

                        <div class="collapse mt-3" id="issueForm{{ $item->id }}">
                            <form method="POST" action="{{ route('admin.orders.items.review',[$order,$item]) }}" class="issue-review-form border rounded-3 p-3" data-confirm data-confirm-title="حفظ مشكلة المنتج" data-confirm-text="سيتم حفظ المشكلة ضمن مراجعة الطلب.">@csrf @method('PATCH')
                                <div class="row g-2">
                                    <div class="col-12"><label class="form-label small fw-bold">نوع المشكلة</label><select class="form-select" name="issue_type" required><option value="">اختر المشكلة</option><option value="unavailable">غير متوفر</option><option value="size_unavailable">المقاس غير متوفر</option><option value="color_unavailable">اللون غير متوفر</option><option value="price_changed">تغير السعر</option><option value="quantity_unavailable">الكمية غير متوفرة</option><option value="other">مشكلة أخرى</option></select></div>
                                    <div class="col-md-5"><label class="form-label small fw-bold">السعر الجديد بالدينار</label><input type="number" step="0.01" min="0" name="reviewed_unit_price_lyd" class="form-control" value="{{ old('reviewed_unit_price_lyd',$item->reviewed_unit_price_lyd) }}" placeholder="يستخدم عند تغير السعر"></div>
                                    <div class="col-md-7"><label class="form-label small fw-bold">تفاصيل إضافية</label><textarea class="form-control" name="review_reason" rows="2" maxlength="1500" placeholder="اختيارية، وإلزامية عند اختيار «مشكلة أخرى»"></textarea></div>
                                    <div class="col-12 d-flex justify-content-end"><button class="btn btn-danger btn-sm px-3" type="submit">حفظ المشكلة</button></div>
                                </div>
                            </form>
                        </div>
                    </article>
                    @endforeach
                </div>
                @if($itemsPage->hasPages())<div class="mt-4 pagination-shell">{{ $itemsPage->links() }}</div>@endif

                <div class="review-complete-bar mt-4">
                    <div>
                        <strong>إنهاء مراجعة الطلب</strong>
                        <div class="small text-secondary">سيتم اعتماد {{ $reviewSummary['pending'] }} منتجًا لم تسجل عليه مشكلة تلقائيًا. @if($reviewSummary['issues'])وسيتم إرسال {{ $reviewSummary['issues'] }} منتجًا به مشكلة إلى العميل لاتخاذ القرار.@endif</div>
                    </div>
                    <form method="POST" action="{{ route('admin.orders.review.complete',$order) }}" data-confirm data-confirm-title="إنهاء مراجعة الطلب" data-confirm-text="تأكد من أنك سجلت جميع المنتجات التي بها مشكلة. بقية المنتجات ستعتبر سليمة تلقائيًا.">@csrf<button class="btn btn-primary icon-text-btn" type="submit"><x-icon name="check" size="17" /> إنهاء مراجعة الطلب</button></form>
                </div>
            </section>

            <section class="admin-panel p-3 p-md-4 mb-4">
                <div class="d-flex justify-content-between align-items-center gap-2 mb-3"><div><div class="page-kicker">الحركة الكاملة</div><h2 class="h5 panel-title mb-0">سجل الحالات والملاحظات</h2></div><span class="status-badge status-primary">{{ $order->histories->count() }} حركة</span></div>
                <div class="order-timeline admin-activity-timeline">
                    @forelse($order->histories->sortByDesc('created_at') as $history)
                    <div class="timeline-entry"><span></span><div>
                        <div class="d-flex flex-wrap align-items-center gap-2"><strong>{{ $history->event_type==='note'?'ملاحظة':(($statusLabels[$history->to_status] ?? $history->to_status)) }}</strong><span class="status-badge {{ $history->visibility==='internal'?'status-warning':'status-primary' }}">{{ $history->visibility==='internal'?'داخلية':'للعميل' }}</span></div>
                        <small>{{ $history->user?->name ?? 'النظام' }} • {{ $history->created_at->format('Y-m-d H:i') }} @if($history->from_status && $history->from_status!==$history->to_status)• من {{ $statusLabels[$history->from_status] ?? $history->from_status }}@endif</small>
                        @if($history->note)<p>{{ $history->note }}</p>@endif
                    </div></div>
                    @empty<div class="small text-secondary">لا توجد حركات مسجلة.</div>@endforelse
                </div>
            </section>

            <section class="admin-panel p-3 p-md-4 mb-4">
                <div class="page-kicker">التواصل</div><h2 class="h5 panel-title mb-3">المحادثة مع العميل</h2>
                <div class="d-grid gap-2 mb-3">@forelse($order->messages->whereNull('order_item_id') as $message)<div class="message-bubble"><strong>{{ $message->user->name }}:</strong> {{ $message->message }}<small>{{ $message->created_at->format('Y-m-d H:i') }}</small></div>@empty<div class="small text-secondary">لا توجد رسائل عامة.</div>@endforelse</div>
                <form method="POST" action="{{ route('admin.orders.messages.store',$order) }}" class="d-flex flex-column flex-md-row gap-2">@csrf<input class="form-control" name="message" required maxlength="2000" placeholder="اكتب رسالة واضحة للعميل"><button class="btn btn-primary icon-text-btn" type="submit"><x-icon name="message" size="17" /> إرسال</button></form>
            </section>
        </div>

        <aside class="col-xxl-4">
            <div class="admin-sticky-rail">
                <section class="admin-panel p-3 mb-3">
                    <div class="page-kicker">إجراءات الطلب</div><h2 class="h5 panel-title mb-3">التحكم والمتابعة</h2>
                    <div class="accordion ops-accordion" id="orderOpsAccordion">
                        <div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#opsStatus">تغيير الحالة</button></h2><div id="opsStatus" class="accordion-collapse collapse show" data-bs-parent="#orderOpsAccordion"><div class="accordion-body">
                            <form method="POST" action="{{ route('admin.orders.status',$order) }}" class="d-grid gap-2" data-confirm data-confirm-title="تأكيد تغيير الحالة" data-confirm-text="سيتم حفظ الحركة والملاحظة في سجل الطلب.">@csrf @method('PATCH')
                                <select name="status" class="form-select" required>@foreach($statusLabels as $key=>$label)<option value="{{ $key }}" @selected($order->status===$key)>{{ $label }}</option>@endforeach</select>
                                <textarea name="reason" class="form-control" rows="3" maxlength="1500" placeholder="سبب أو ملاحظة على القرار — إلزامية للرفض والإلغاء وطلب رد العميل"></textarea>
                                <input type="hidden" name="visibility" value="auto">
                                <div class="form-text">يحدد النظام ظهور الملاحظة تلقائيًا حسب نوع الإجراء. تغييرات الحالة تظهر للعميل، والملاحظات الداخلية تضاف من قسم «إضافة ملاحظة».</div>
                                <button class="btn btn-primary icon-text-btn" type="submit"><x-icon name="check" size="17" /> حفظ الحالة</button>
                            </form>
                        </div></div></div>

                        <div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#opsNote">إضافة ملاحظة</button></h2><div id="opsNote" class="accordion-collapse collapse" data-bs-parent="#orderOpsAccordion"><div class="accordion-body">
                            <form method="POST" action="{{ route('admin.orders.notes.store',$order) }}" class="d-grid gap-2">@csrf<textarea class="form-control" name="note" rows="3" maxlength="2000" required placeholder="اكتب الملاحظة"></textarea><select class="form-select" name="visibility" required><option value="internal">ملاحظة داخلية</option><option value="customer">تظهر للعميل + إشعار</option></select><button class="btn btn-navy icon-text-btn" type="submit"><x-icon name="activity" size="17" /> حفظ الملاحظة</button></form>
                        </div></div></div>

                        <div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#opsAssign">المسؤول عن الطلب</button></h2><div id="opsAssign" class="accordion-collapse collapse" data-bs-parent="#orderOpsAccordion"><div class="accordion-body">
                            <form method="POST" action="{{ route('admin.orders.assign',$order) }}" class="d-grid gap-2">@csrf @method('PATCH')<select name="assigned_to" class="form-select"><option value="">غير مسند</option>@foreach($managers as $manager)<option value="{{ $manager->id }}" @selected($order->assigned_to===$manager->id)>{{ $manager->name }} — {{ $manager->role==='admin'?'مدير':'مسؤول طلبات' }}</option>@endforeach</select><button class="btn btn-ghost" type="submit">تحديث المسؤول</button></form>
                        </div></div></div>

                        <div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#opsApprove">اعتماد وشروط الدفع</button></h2><div id="opsApprove" class="accordion-collapse collapse" data-bs-parent="#orderOpsAccordion"><div class="accordion-body">
                            <form method="POST" action="{{ route('admin.orders.approve',$order) }}" class="d-grid gap-2" data-confirm data-confirm-title="اعتماد الطلب" data-confirm-text="تأكد من مراجعة كل المنتجات قبل الاعتماد.">@csrf<select name="deposit_mode" class="form-select" required><option value="auto">عربون تلقائي حسب القواعد</option><option value="none">بدون عربون</option><option value="percentage">نسبة مئوية</option><option value="fixed">مبلغ ثابت</option></select><input type="number" step="0.01" min="0" name="deposit_value" class="form-control" placeholder="قيمة النسبة أو المبلغ"><textarea name="decision_note" class="form-control" rows="2" maxlength="1500" placeholder="ملاحظة القبول للعميل (اختيارية)"></textarea><textarea name="payment_terms_note" class="form-control" rows="2" maxlength="1500" placeholder="ملاحظة شروط الدفع"></textarea><button class="btn btn-primary" type="submit">اعتماد وتحديد الدفع</button></form>
                        </div></div></div>

                        <div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#opsTerms">تعديل العربون</button></h2><div id="opsTerms" class="accordion-collapse collapse" data-bs-parent="#orderOpsAccordion"><div class="accordion-body">
                            <form method="POST" action="{{ route('admin.orders.payment-terms',$order) }}" class="d-grid gap-2" data-confirm data-confirm-title="تعديل شروط الدفع" data-confirm-text="سيتم إشعار العميل بالتعديل.">@csrf @method('PATCH')<select name="deposit_mode" class="form-select" required><option value="auto">تلقائي</option><option value="none">بدون عربون</option><option value="percentage">نسبة</option><option value="fixed">مبلغ ثابت</option></select><input type="number" step="0.01" min="0" name="deposit_value" class="form-control" placeholder="القيمة"><textarea name="reason" class="form-control" rows="2" required maxlength="1500" placeholder="سبب التعديل"></textarea><button class="btn btn-ghost" type="submit">تحديث الشروط</button></form>
                        </div></div></div>
                    </div>
                </section>

                <section class="admin-panel p-3 mb-3">
                    <div class="d-flex align-items-center justify-content-between gap-2 mb-3"><div><div class="page-kicker">التحصيل</div><h2 class="h6 panel-title mb-0">الدفعات</h2></div><span class="status-badge status-primary">{{ $order->payments->count() }}</span></div>
                    <div class="d-grid gap-2">
                    @forelse($order->payments->sortByDesc('created_at') as $payment)
                        <div class="payment-admin-row"><div><strong>{{ number_format((float)$payment->amount,2) }} د.ل</strong><small>{{ $payment->method?->name }} • {{ $payment->number }}</small>@if($payment->transaction_ref)<small>مرجع: {{ $payment->transaction_ref }}</small>@endif</div><span class="status-badge {{ $payment->status==='verified'?'status-success':($payment->status==='rejected'?'status-danger':'status-warning') }}">{{ $payment->status==='verified'?'معتمدة':($payment->status==='rejected'?'مرفوضة':'تنتظر التحقق') }}</span>
                        @if($payment->status==='pending_verification')<form method="POST" action="{{ route('admin.orders.payments.verify',[$order,$payment]) }}" class="w-100 d-grid gap-2 mt-2" data-confirm data-confirm-title="التحقق من الدفعة" data-confirm-text="تأكد من المرجع أو الإيصال قبل اعتماد الدفعة.">@csrf @method('PATCH')<input name="reason" class="form-control form-control-sm" placeholder="سبب الرفض عند الحاجة"><div class="d-flex gap-2"><button class="btn btn-success btn-sm flex-fill" name="decision" value="verified" type="submit">اعتماد</button><button class="btn btn-outline-danger btn-sm flex-fill" name="decision" value="rejected" type="submit">رفض</button></div></form>@endif</div>
                    @empty<div class="small text-secondary">لا توجد دفعات حتى الآن.</div>@endforelse
                    </div>
                </section>
            </div>
        </aside>
    </div>
</div>
@endsection
