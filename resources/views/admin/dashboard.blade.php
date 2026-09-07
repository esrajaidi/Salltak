@extends('layouts.admin')
@section('title','لوحة الإدارة')
@section('admin-content')
@php
$statusLabels=['submitted'=>'جديد','under_review'=>'تحت المراجعة','needs_customer_action'=>'يحتاج رد العميل','approved'=>'معتمد','awaiting_deposit'=>'بانتظار العربون','awaiting_payment'=>'بانتظار الدفع','deposit_paid'=>'العربون مدفوع','purchasing'=>'جاري الشراء','ordered'=>'تم الطلب','shipped'=>'الشحن','arrived_libya'=>'وصل ليبيا','awaiting_balance'=>'باقي المبلغ','ready_for_delivery'=>'جاهز للتسليم','out_for_delivery'=>'خارج للتسليم','delivered'=>'تم التسليم','rejected'=>'مرفوض','cancelled'=>'ملغي'];
@endphp
<div class="dashboard-command-center">
    <header class="dashboard-welcome mb-4">
        <div><div class="page-kicker">Operations Center</div><h1 class="h3 fw-black mb-1">صباح النشاط، {{ auth()->user()->name }} 👋</h1><p class="text-secondary mb-0">هذه أهم الحالات التي تحتاج انتباهك الآن، مع مراقبة حركة النظام لحظة بلحظة.</p></div>
        <div class="d-flex gap-2 flex-wrap"><a class="btn btn-primary" href="{{ route('admin.orders.index',['status'=>'submitted']) }}">الطلبات الجديدة</a>@if(auth()->user()->role === 'admin')<a class="btn btn-ghost" href="{{ route('admin.payment-methods.index') }}">طرق الدفع</a>@endif</div>
    </header>

    <div class="monitoring-grid mb-4">
        <div class="command-metric"><span class="command-icon">✓</span><div><small>كل الطلبات</small><strong>{{ number_format($stats['orders']) }}</strong><em>{{ number_format($stats['pending_orders']) }} تحتاج متابعة</em></div></div>
        <div class="command-metric is-warning"><span class="command-icon">د</span><div><small>دفعات تنتظر التحقق</small><strong>{{ number_format($stats['pending_payments']) }}</strong><em>راجع الإثباتات قبل الاعتماد</em></div></div>
        <div class="command-metric is-success"><span class="command-icon">ل</span><div><small>مدفوع ومعتمد</small><strong>{{ number_format((float)$stats['paid'],2) }}</strong><em>د.ل إجمالي الدفعات</em></div></div>
        <div class="command-metric"><span class="command-icon">◎</span><div><small>العملاء</small><strong>{{ number_format($stats['users']) }}</strong><em>{{ number_format($stats['delivered']) }} طلب مكتمل</em></div></div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xl-7">
            <section class="admin-panel p-3 p-md-4 h-100">
                <div class="d-flex justify-content-between align-items-center gap-2 mb-3"><div><div class="page-kicker">Priority Queue</div><h2 class="h5 panel-title mb-0">طلبات تحتاج تدخل</h2></div><a class="small fw-bold text-decoration-none" href="{{ route('admin.orders.index') }}">عرض الكل ←</a></div>
                <div class="dashboard-order-list">@forelse($needsAction as $order)<a href="{{ route('admin.orders.show',$order) }}" class="dashboard-order-row"><span class="order-row-code ltr">{{ $order->number }}</span><span class="min-w-0"><strong>{{ $order->user->name }}</strong><small>{{ $order->cart?->store?->name ?? 'سلة' }} • {{ $order->updated_at->diffForHumans() }}</small></span><span class="status-badge {{ $order->status==='needs_customer_action'?'status-warning':'status-primary' }}">{{ $statusLabels[$order->status] ?? $order->status }}</span><strong>{{ number_format((float)$order->total_lyd,2) }} د.ل</strong></a>@empty<div class="empty-state-compact">لا توجد طلبات تحتاج تدخل مباشر الآن.</div>@endforelse</div>
            </section>
        </div>
        <div class="col-xl-5">
            <section class="admin-panel p-3 p-md-4 h-100">
                <div class="d-flex justify-content-between align-items-center gap-2 mb-3"><div><div class="page-kicker">Payments</div><h2 class="h5 panel-title mb-0">دفعات تنتظر التحقق</h2></div><span class="status-badge status-warning">{{ $stats['pending_payments'] }}</span></div>
                <div class="dashboard-payment-list">@forelse($pendingPayments as $payment)<a href="{{ route('admin.orders.show',$payment->order) }}" class="dashboard-payment-row"><span class="payment-row-icon">د</span><span class="min-w-0"><strong>{{ number_format((float)$payment->amount,2) }} د.ل</strong><small>{{ $payment->order?->number }} • {{ $payment->method?->name }}</small></span><span class="text-secondary small">{{ $payment->created_at->diffForHumans() }}</span></a>@empty<div class="empty-state-compact">لا توجد دفعات معلقة.</div>@endforelse</div>
            </section>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-5">
            <section class="admin-panel p-3 p-md-4 h-100">
                <div class="page-kicker">SLA Watch</div><h2 class="h5 panel-title mb-3">طلبات متأخرة أكثر من 24 ساعة</h2>
                <div class="d-grid gap-2">@forelse($agingOrders as $order)<a href="{{ route('admin.orders.show',$order) }}" class="aging-order-card"><div><strong class="ltr">{{ $order->number }}</strong><small>{{ $order->user->name }} • {{ $statusLabels[$order->status] ?? $order->status }}</small></div><span>{{ $order->updated_at->diffForHumans() }}</span></a>@empty<div class="empty-state-compact">ممتاز، لا توجد طلبات متأخرة حاليًا.</div>@endforelse</div>
            </section>
        </div>
        <div class="col-xl-7">
            <section class="admin-panel p-3 p-md-4 h-100 audit-monitor-card">
                <div class="d-flex justify-content-between align-items-center gap-2 mb-3"><div><div class="page-kicker">Audit Monitor</div><h2 class="h5 panel-title mb-0">آخر نشاط بالنظام</h2></div><span class="status-badge status-primary">audit</span></div>
                <div class="audit-stream">@forelse($recentActivity as $activity)<div class="audit-stream-row"><span class="audit-dot"></span><div class="min-w-0"><strong>{{ $activity->title }}</strong><small>{{ $activity->actor?->name ?? 'النظام' }} @if($activity->order)• {{ $activity->order->number }}@endif</small>@if($activity->description)<p>{{ $activity->description }}</p>@endif</div><time>{{ $activity->created_at?->diffForHumans() }}</time></div>@empty<div class="empty-state-compact">سيظهر نشاط النظام هنا بعد تشغيل Migration الجديدة.</div>@endforelse</div>
            </section>
        </div>
    </div>
</div>
@endsection
