@extends('layouts.app')
@section('title','سلاتي')
@section('body')
@php($statusLabels=['new'=>'جديدة','saved'=>'محفوظة','submitted'=>'تم إرسال الطلب','confirmed'=>'مؤكدة','cancelled'=>'ملغاة'])
@php($currencyLabels=['USD'=>'دولار أمريكي','LYD'=>'دينار ليبي','EUR'=>'يورو','GBP'=>'جنيه إسترليني','AED'=>'درهم إماراتي','SAR'=>'ريال سعودي'])
<section class="page-section customer-page">
    <div class="container">
        <div class="d-flex flex-column flex-md-row align-items-md-end justify-content-between gap-3 mb-4 reveal is-visible">
            <div><div class="page-kicker">حسابي</div><h1 class="page-heading">سلاتي</h1><p class="page-subtitle">السلات التي حفظتها، مرتبة من الأحدث للأقدم.</p></div>
            <a class="btn btn-primary icon-text-btn" href="{{ route('carts.create') }}"><x-icon name="plus" size="17"/>سلة جديدة</a>
        </div>

        <div class="row g-3 g-lg-4">
            @forelse($carts as $cart)
                @php($statusClass = $cart->status === 'cancelled' ? 'status-danger' : (in_array($cart->status,['confirmed','submitted'],true) ? 'status-success' : 'status-primary'))
                <div class="col-md-6 col-xl-4">
                    <article class="surface-card cart-card reveal">
                        <div class="d-flex justify-content-between align-items-center gap-2 mb-3"><span class="cart-number ltr">{{ $cart->number }}</span><span class="status-badge {{ $statusClass }}">{{ $statusLabels[$cart->status] ?? 'غير محددة' }}</span></div>
                        <div class="d-flex align-items-center justify-content-between gap-3 mb-1"><div class="fw-bold">{{ $cart->store?->name ?? $cart->source_host ?? 'موقع خارجي' }}</div><span class="small fw-bold text-primary">{{ $currencyLabels[strtoupper((string)$cart->source_currency)] ?? $cart->source_currency }}</span></div>
                        <div class="small text-secondary mb-4">{{ $cart->items_count }} منتج • {{ $cart->created_at->format('Y-m-d H:i') }}</div>
                        <div class="small text-secondary">إجمالي السلة</div>
                        <div class="cart-total mb-3">{{ number_format((float)$cart->total_lyd,2) }} <small class="fs-6">د.ل</small></div>
                        <a class="btn btn-soft w-100" href="{{ route('carts.show',$cart) }}">عرض السلة</a>@if($cart->order)<a class="btn btn-outline-primary w-100 mt-2" href="{{ route('orders.show',$cart->order) }}">متابعة الطلب</a>@endif
                    </article>
                </div>
            @empty
                <div class="col-12"><div class="surface-card empty-state"><div class="empty-state-icon">س</div><h2 class="h4 fw-bold">لا توجد سلات محفوظة</h2><p class="text-secondary">ألصق رابط أول سلة، ثم راجع المنتجات والأسعار واحفظها في حسابك.</p><a class="btn btn-primary" href="{{ route('carts.create') }}">أضف أول سلة</a></div></div>
            @endforelse
        </div>
        <div class="mt-4">{{ $carts->links() }}</div>
    </div>
</section>
@endsection
