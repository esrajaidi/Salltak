@extends('layouts.admin')
@section('title',$cart->number)
@section('admin-content')
<div class="admin-page-header reveal is-visible d-flex flex-column flex-md-row align-items-md-end justify-content-between gap-3 mb-4">
    <div><div class="small text-primary fw-bold mb-1">تفاصيل السلة</div><h1 class="page-heading">{{ $cart->number }}</h1><p class="page-subtitle">{{ $cart->user->name }} — {{ $cart->user->email }}</p></div>
    <a class="btn btn-outline-primary" href="{{ route('admin.carts.index') }}">الرجوع للسلات</a>
</div>

<div class="surface-card admin-panel reveal p-3 p-md-4">
    <div class="row g-3 mb-4">
        <div class="col-md-4"><div class="summary-tile"><div class="summary-label">الإجمالي بالدينار</div><div class="summary-value text-primary">{{ number_format((float)$cart->total_lyd,2) }} د.ل</div></div></div>
        <div class="col-md-4"><div class="summary-tile"><div class="summary-label">حالة الاستيراد</div><div class="summary-value">{{ $cart->import_status }}</div></div></div>
        <div class="col-md-4"><div class="summary-tile"><div class="summary-label">عدد المنتجات</div><div class="summary-value">{{ $cart->items->count() }}</div></div></div>
    </div>

    <form method="POST" action="{{ route('admin.carts.status',$cart) }}" class="row g-2 align-items-end mb-4">
        @csrf @method('PATCH')
        <div class="col-md-5 col-lg-3"><label class="form-label">حالة السلة</label><select name="status" class="form-select">@foreach(['new','saved','confirmed','cancelled'] as $s)<option value="{{ $s }}" @selected($cart->status===$s)>{{ $s }}</option>@endforeach</select></div>
        <div class="col-md-auto"><button class="btn btn-primary w-100" type="submit">تحديث الحالة</button></div>
    </form>

    <div class="table-wrap"><div class="table-responsive"><table class="table table-modern"><thead><tr><th>المنتج</th><th>السعر</th><th>الكمية</th></tr></thead><tbody>@forelse($cart->items as $item)<tr><td><div class="d-flex align-items-center gap-2">@if($item->image_url)<img src="{{ $item->image_url }}" alt="" width="44" height="44" class="rounded object-fit-cover">@endif<span class="fw-semibold">{{ $item->name }}</span></div></td><td>{{ number_format((float)$item->unit_price_original,2) }} {{ $item->currency }}</td><td>{{ $item->quantity }}</td></tr>@empty<tr><td colspan="3" class="text-center text-secondary py-4">لا توجد منتجات.</td></tr>@endforelse</tbody></table></div></div>
</div>
@endsection
