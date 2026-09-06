@extends('layouts.admin')
@section('title','لوحة الإدارة')
@section('admin-content')
<div class="d-flex flex-column flex-md-row align-items-md-end justify-content-between gap-3 mb-4">
    <div><div class="small text-primary fw-bold mb-1">نظرة عامة</div><h1 class="page-heading">لوحة الإدارة</h1><p class="page-subtitle">ملخص سريع لحركة المنصة وأحدث السلات.</p></div>
</div>

@php($cards = [
    'users' => ['المستخدمون','♙'],
    'carts' => ['السلات','▣'],
    'items' => ['المنتجات','□'],
    'stores' => ['المواقع','◎'],
])
<div class="row g-3 g-xl-4 mb-4">
    @foreach($cards as $k => [$label,$icon])
        <div class="col-6 col-xl-3"><div class="surface-card stat-card"><div class="d-flex align-items-start justify-content-between gap-3"><div><div class="stat-label">{{ $label }}</div><div class="stat-value">{{ $stats[$k] }}</div></div><div class="stat-icon">{{ $icon }}</div></div></div></div>
    @endforeach
</div>

<div class="surface-card overflow-hidden">
    <div class="p-3 p-md-4 border-bottom d-flex align-items-center justify-content-between gap-2"><div><h2 class="h5 fw-bold mb-1">آخر السلات</h2><div class="small text-secondary">آخر العمليات المحفوظة في النظام</div></div><a class="btn btn-outline-primary btn-sm" href="{{ route('admin.carts.index') }}">عرض الكل</a></div>
    <div class="table-responsive"><table class="table table-modern"><thead><tr><th>الرقم</th><th>العميل</th><th>الموقع</th><th>القيمة</th><th></th></tr></thead><tbody>@forelse($latestCarts as $cart)<tr><td class="fw-semibold">{{ $cart->number }}</td><td>{{ $cart->user->name }}</td><td>{{ $cart->store?->name ?? '-' }}</td><td class="fw-bold">{{ number_format((float)$cart->total_lyd,2) }} د.ل</td><td class="text-nowrap"><a class="btn btn-soft btn-sm" href="{{ route('admin.carts.show',$cart) }}">عرض</a></td></tr>@empty<tr><td colspan="5" class="text-center py-5 text-secondary">لا توجد سلات بعد.</td></tr>@endforelse</tbody></table></div>
</div>
@endsection
