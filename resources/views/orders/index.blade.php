@extends('layouts.app')
@section('title','طلباتي')
@section('body')
<section class="page-section customer-page">
<div class="container">
    <div class="d-flex flex-column flex-md-row align-items-md-end justify-content-between gap-3 mb-4 reveal is-visible">
        <div><div class="page-kicker">متابعة الشراء</div><h1 class="page-heading">طلباتي</h1><p class="page-subtitle">تابع المراجعة، العربون، الدفعات، الشراء والشحن حتى التسليم.</p></div>
        <a class="btn btn-primary" href="{{ route('carts.index') }}">اختيار سلة محفوظة</a>
    </div>

    @php($labels = [
        'submitted'=>'تم الإرسال','under_review'=>'تحت المراجعة','needs_customer_action'=>'يحتاج ردك','approved'=>'معتمد',
        'awaiting_deposit'=>'بانتظار العربون','awaiting_payment'=>'بانتظار الدفع','deposit_paid'=>'العربون مدفوع','purchasing'=>'جاري الشراء',
        'ordered'=>'تم الطلب من المتجر','shipped'=>'جاري الشحن','arrived_libya'=>'وصل ليبيا','awaiting_balance'=>'بانتظار باقي المبلغ',
        'ready_for_delivery'=>'جاهز للتسليم','out_for_delivery'=>'خرج للتسليم','delivered'=>'تم التسليم','rejected'=>'مرفوض','cancelled'=>'ملغي'
    ])
    <div class="row g-3 g-lg-4">
        @forelse($orders as $order)
            @php($danger = in_array($order->status,['rejected','cancelled'],true))
            @php($success = in_array($order->status,['delivered','ready_for_delivery'],true))
            <div class="col-md-6 col-xl-4">
                <article class="surface-card order-card reveal h-100">
                    <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                        <span class="cart-number ltr">{{ $order->number }}</span>
                        <span class="status-badge {{ $danger?'status-danger':($success?'status-success':'status-primary') }}">{{ $labels[$order->status] ?? $order->status }}</span>
                    </div>
                    <div class="small text-secondary mb-1">{{ $order->cart?->store?->name ?? 'سلة تسوق' }} • {{ $order->items_count }} منتج</div>
                    <div class="order-money-grid my-3">
                        <div><span>الإجمالي</span><strong>{{ number_format((float)$order->total_lyd,2) }} د.ل</strong></div>
                        <div><span>المدفوع</span><strong>{{ number_format((float)$order->paid_amount,2) }} د.ل</strong></div>
                        <div><span>المتبقي</span><strong>{{ number_format((float)$order->remaining_amount,2) }} د.ل</strong></div>
                    </div>
                    @if($order->assignee)<div class="small text-secondary mb-3">المسؤول: <strong class="text-dark">{{ $order->assignee->name }}</strong></div>@endif
                    <a class="btn btn-soft w-100 mt-auto" href="{{ route('orders.show',$order) }}">عرض ومتابعة الطلب</a>
                </article>
            </div>
        @empty
            <div class="col-12"><div class="surface-card empty-state"><div class="empty-state-icon"><x-icon name="orders" size="26" /></div><h2 class="h4 fw-bold">ما عندكش طلبات بعد</h2><p class="text-secondary">احفظ سلة أولًا، وبعدها اضغط «اطلب هذه السلة» لإرسالها للمراجعة.</p><a class="btn btn-primary" href="{{ route('carts.index') }}">الذهاب إلى سلاتي</a></div></div>
        @endforelse
    </div>
    <div class="mt-4">{{ $orders->links() }}</div>
</div>
</section>
@endsection
