@extends('layouts.admin')
@section('title','أسعار الصرف')
@section('admin-content')
@php($currencyLabels=['USD'=>'دولار أمريكي','LYD'=>'دينار ليبي','EUR'=>'يورو','GBP'=>'جنيه إسترليني','AED'=>'درهم إماراتي','SAR'=>'ريال سعودي'])
<div class="admin-page-header mb-4 reveal is-visible"><div class="small text-primary fw-bold mb-1">الإعدادات المالية</div><h1 class="page-heading">أسعار الصرف</h1><p class="page-subtitle">حدد قيمة كل عملة مقابل الدينار الليبي.</p></div>

<div class="surface-card admin-panel reveal p-3 p-md-4 mb-4">
    <form method="POST" action="{{ route('admin.rates.store') }}" class="row g-2 align-items-end">
        @csrf
        <div class="col-sm-4 col-lg-2"><label class="form-label">العملة</label><input class="form-control text-uppercase ltr" name="currency" placeholder="مثال: دولار أمريكي" maxlength="3" required></div>
        <div class="col-sm-5 col-lg-3"><label class="form-label">1 وحدة = د.ل</label><input class="form-control ltr" type="number" step="0.0001" min="0" name="rate_to_lyd" placeholder="مثال: 7.2500" required></div>
        <div class="col-sm-auto"><button class="btn btn-primary w-100" type="submit">حفظ السعر</button></div>
    </form>
</div>

<div class="surface-card admin-panel overflow-hidden reveal"><div class="table-responsive"><table class="table table-modern"><thead><tr><th>العملة</th><th>1 وحدة = د.ل</th><th>الحالة</th><th></th></tr></thead><tbody>@forelse($rates as $rate)<tr><td class="fw-bold">{{ $currencyLabels[strtoupper((string)$rate->currency)] ?? $rate->currency }}</td><td>{{ $rate->rate_to_lyd }}</td><td><span class="status-badge {{ $rate->is_active?'status-success':'status-neutral' }}">{{ $rate->is_active?'مفعّل':'موقوف' }}</span></td><td><form method="POST" action="{{ route('admin.rates.toggle',$rate) }}">@csrf @method('PATCH')<button class="btn btn-soft btn-sm" type="submit">تبديل الحالة</button></form></td></tr>@empty<tr><td colspan="4" class="text-center text-secondary py-5">لا توجد أسعار صرف.</td></tr>@endforelse</tbody></table></div></div>
@endsection
