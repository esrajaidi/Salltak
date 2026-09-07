@extends('layouts.admin')
@section('title','الطلبات')
@section('admin-content')
@php($paymentLabels=['unpaid'=>'غير مدفوع','pending'=>'بانتظار التحقق','deposit_paid'=>'العربون مدفوع','partial'=>'مدفوع جزئيًا','paid'=>'مدفوع بالكامل','failed'=>'فشل','refunded'=>'مسترد'])
@php($statusLabels=['submitted'=>'تم الإرسال','under_review'=>'تحت المراجعة','needs_customer_action'=>'يحتاج رد العميل','approved'=>'معتمد','awaiting_deposit'=>'بانتظار العربون','awaiting_payment'=>'بانتظار الدفع','deposit_paid'=>'العربون مدفوع','purchasing'=>'جاري الشراء','ordered'=>'تم الطلب','shipped'=>'جاري الشحن','arrived_libya'=>'وصل ليبيا','awaiting_balance'=>'بانتظار الباقي','ready_for_delivery'=>'جاهز للتسليم','out_for_delivery'=>'خرج للتسليم','delivered'=>'تم التسليم','rejected'=>'مرفوض','cancelled'=>'ملغي'])
<div class="admin-page-header reveal is-visible mb-4"><div class="small text-primary fw-bold mb-1">دورة الطلب</div><h1 class="page-heading">الطلبات</h1><p class="page-subtitle">مراجعة السلات، إسناد المسؤول، اعتماد المنتجات، متابعة الدفعات والشحن والتسليم.</p></div>

<div class="surface-card admin-panel reveal p-3 p-md-4 mb-4">
<form method="GET" class="row g-2 align-items-end">
    <div class="col-md-4"><label class="form-label">بحث</label><input class="form-control" name="q" value="{{ request('q') }}" placeholder="رقم الطلب / العميل"></div>
    <div class="col-md-3"><label class="form-label">الحالة</label><select class="form-select" name="status"><option value="">كل الحالات</option>@foreach($statusLabels as $key=>$label)<option value="{{ $key }}" @selected(request('status')===$key)>{{ $label }}</option>@endforeach</select></div>
    <div class="col-md-3"><label class="form-label">المسؤول</label><select class="form-select" name="assigned_to"><option value="">الكل</option>@foreach($managers as $manager)<option value="{{ $manager->id }}" @selected((string)request('assigned_to')===(string)$manager->id)>{{ $manager->name }}</option>@endforeach</select></div>
    <div class="col-md-2"><button class="btn btn-primary w-100 icon-text-btn"><x-icon name="filter" size="17" /> تصفية</button></div>
</form>
</div>

<div class="surface-card admin-panel overflow-hidden reveal">
<div class="table-responsive"><table class="table table-modern align-middle"><thead><tr><th>الطلب</th><th>العميل</th><th>الحالة</th><th>الدفع</th><th>الإجمالي</th><th>المتبقي</th><th>المسؤول</th><th></th></tr></thead><tbody>
@forelse($orders as $order)
<tr>
<td><div class="fw-bold ltr text-end">{{ $order->number }}</div><small class="text-secondary">{{ $order->items_count }} منتج</small></td>
<td><div class="fw-semibold">{{ $order->user->name }}</div><small class="text-secondary ltr">{{ $order->user->phone ?: $order->user->email }}</small></td>
<td><span class="status-badge {{ in_array($order->status,['rejected','cancelled'],true)?'status-danger':(in_array($order->status,['delivered','ready_for_delivery'],true)?'status-success':'status-primary') }}">{{ $statusLabels[$order->status]??$order->status }}</span></td>
<td><span class="small fw-semibold">{{ $paymentLabels[$order->payment_status] ?? 'غير محدد' }}</span></td>
<td class="fw-bold">{{ number_format((float)$order->total_lyd,2) }} د.ل</td>
<td class="fw-bold {{ (float)$order->remaining_amount>0?'text-danger':'text-success' }}">{{ number_format((float)$order->remaining_amount,2) }} د.ل</td>
<td>{{ $order->assignee?->name ?? 'غير مسند' }}</td>
<td><a class="btn btn-soft btn-sm" href="{{ route('admin.orders.show',$order) }}">مراجعة</a></td>
</tr>
@empty<tr><td colspan="8" class="text-center py-5 text-secondary">لا توجد طلبات.</td></tr>@endforelse
</tbody></table></div></div>
<div class="mt-4">{{ $orders->links() }}</div>
@endsection
