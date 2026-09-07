@extends('layouts.admin')
@section('title','إدارة السلات')
@section('admin-content')
<div class="admin-page-header mb-4 reveal is-visible"><div class="small text-primary fw-bold mb-1">إدارة البيانات</div><h1 class="page-heading">إدارة السلات</h1><p class="page-subtitle">ابحث وفلتر جميع السلات المحفوظة في المنصة.</p></div>

<div class="surface-card admin-panel reveal p-3 p-md-4 mb-4">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-6 col-xl-4"><label class="form-label">بحث</label><input class="form-control" name="q" value="{{ request('q') }}" placeholder="رقم السلة أو اسم العميل"></div>
        <div class="col-md-3 col-xl-3"><label class="form-label">الحالة</label><select class="form-select" name="status"><option value="">كل الحالات</option>@foreach(['saved','new','confirmed','cancelled'] as $s)<option value="{{ $s }}" @selected(request('status')===$s)>{{ $s }}</option>@endforeach</select></div>
        <div class="col-md-auto"><button class="btn btn-primary w-100" type="submit">تطبيق الفلترة</button></div>
        @if(request()->filled('q') || request()->filled('status'))<div class="col-md-auto"><a class="btn btn-outline-secondary w-100" href="{{ route('admin.carts.index') }}">مسح</a></div>@endif
    </form>
</div>

<div class="surface-card admin-panel overflow-hidden reveal"><div class="table-responsive"><table class="table table-modern"><thead><tr><th>الرقم</th><th>العميل</th><th>العناصر</th><th>الإجمالي</th><th>الحالة</th><th></th></tr></thead><tbody>@forelse($carts as $cart)<tr><td class="fw-semibold">{{ $cart->number }}</td><td>{{ $cart->user->name }}</td><td>{{ $cart->items_count }}</td><td class="fw-bold">{{ number_format((float)$cart->total_lyd,2) }} د.ل</td><td><span class="status-badge {{ $cart->status==='cancelled'?'status-danger':($cart->status==='confirmed'?'status-success':'status-primary') }}">{{ $cart->status }}</span></td><td><a class="btn btn-soft btn-sm" href="{{ route('admin.carts.show',$cart) }}">تفاصيل</a></td></tr>@empty<tr><td colspan="6" class="text-center py-5 text-secondary">لا توجد نتائج.</td></tr>@endforelse</tbody></table></div></div>
<div class="mt-4">{{ $carts->links() }}</div>
@endsection
