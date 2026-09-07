@extends('layouts.admin')
@section('title',$cart->number)
@section('admin-content')
@php($cartStatusLabels=['new'=>'جديدة','saved'=>'محفوظة','confirmed'=>'مؤكدة','cancelled'=>'ملغاة'])
@php($importLabels=['success'=>'تم الجلب','needs_review'=>'تحتاج مراجعة','failed'=>'فشل الجلب'])
@php($currencyLabels=['USD'=>'دولار أمريكي','LYD'=>'دينار ليبي','EUR'=>'يورو','GBP'=>'جنيه إسترليني','AED'=>'درهم إماراتي','SAR'=>'ريال سعودي'])
<div class="admin-page-header reveal is-visible d-flex flex-column flex-md-row align-items-md-end justify-content-between gap-3 mb-4">
    <div><div class="small text-primary fw-bold mb-1">تفاصيل السلة</div><h1 class="page-heading">{{ $cart->number }}</h1><p class="page-subtitle">{{ $cart->user->name }} — {{ $cart->user->email }}</p></div>
    <a class="btn btn-outline-primary icon-text-btn" href="{{ route('admin.carts.index') }}"><x-icon name="arrow-left"/>الرجوع للسلات</a>
</div>
<div class="surface-card admin-panel reveal p-3 p-md-4">
    <div class="row g-3 mb-4">
        <div class="col-md-4"><div class="summary-tile"><div class="summary-label">الإجمالي بالدينار</div><div class="summary-value text-primary">{{ number_format((float)$cart->total_lyd,2) }} د.ل</div></div></div>
        <div class="col-md-4"><div class="summary-tile"><div class="summary-label">حالة الاستيراد</div><div class="summary-value fs-6">{{ $importLabels[$cart->import_status] ?? 'غير محددة' }}</div></div></div>
        <div class="col-md-4"><div class="summary-tile"><div class="summary-label">عدد المنتجات</div><div class="summary-value">{{ $itemsPage->total() }}</div></div></div>
    </div>
    <form method="POST" action="{{ route('admin.carts.status',$cart) }}" class="row g-2 align-items-end mb-4" data-confirm data-confirm-title="تحديث حالة السلة" data-confirm-text="سيتم حفظ الحالة الجديدة.">@csrf @method('PATCH')<div class="col-md-5 col-lg-3"><label class="form-label">حالة السلة</label><select name="status" class="form-select">@foreach($cartStatusLabels as $key=>$label)<option value="{{ $key }}" @selected($cart->status===$key)>{{ $label }}</option>@endforeach</select></div><div class="col-md-auto"><button class="btn btn-primary icon-text-btn w-100" type="submit"><x-icon name="check"/>تحديث الحالة</button></div></form>
    <div class="table-wrap"><div class="table-responsive"><table class="table table-modern"><thead><tr><th>المنتج</th><th>السعر الأصلي</th><th>الكمية</th></tr></thead><tbody>@forelse($itemsPage as $item)<tr><td><div class="d-flex align-items-center gap-2">@if($item->image_url)<img src="{{ $item->image_url }}" alt="" width="44" height="44" class="rounded object-fit-cover">@endif<span class="fw-semibold">{{ $item->name }}</span></div></td><td><span class="ltr">{{ number_format((float)$item->unit_price_original,2) }} {{ $currencyLabels[strtoupper((string)$item->currency)] ?? $item->currency }}</span></td><td>{{ $item->quantity }}</td></tr>@empty<tr><td colspan="3" class="text-center text-secondary py-4">لا توجد منتجات.</td></tr>@endforelse</tbody></table></div></div>
    @if($itemsPage->hasPages())<div class="mt-4 pagination-shell">{{ $itemsPage->links() }}</div>@endif
</div>
@endsection
