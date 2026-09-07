@extends('layouts.app')
@section('title',$cart->number)
@section('body')
<section class="page-section customer-page">
    <div class="container">
        @php($statusClass = $cart->status === 'cancelled' ? 'status-danger' : (in_array($cart->status,['confirmed','submitted'],true) ? 'status-success' : 'status-primary'))
        @php($statusLabels=['new'=>'جديدة','saved'=>'محفوظة','submitted'=>'تم إرسال الطلب','confirmed'=>'مؤكدة','cancelled'=>'ملغاة'])
        @php($currencyLabels=['USD'=>'دولار أمريكي','LYD'=>'دينار ليبي','EUR'=>'يورو','GBP'=>'جنيه إسترليني','AED'=>'درهم إماراتي','SAR'=>'ريال سعودي'])
        @php($sourceCurrencyLabel=$currencyLabels[strtoupper((string)$cart->source_currency)] ?? $cart->source_currency)
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
            <div><div class="page-kicker">تفاصيل السلة</div><h1 class="page-heading">{{ $cart->number }}</h1><p class="page-subtitle">{{ $cart->store?->name ?? $cart->source_host }} • {{ $cart->created_at->format('Y-m-d H:i') }}</p></div>
            <span class="status-badge {{ $statusClass }} align-self-start align-self-md-center">{{ $statusLabels[$cart->status] ?? $cart->status }}</span>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3"><div class="summary-tile h-100"><div class="summary-label">عدد المنتجات</div><div class="summary-value">{{ $itemsPage->total() }}</div></div></div>
            <div class="col-6 col-lg-3"><div class="summary-tile h-100"><div class="summary-label">سعر الصرف</div><div class="summary-value"><span class="ltr">1 {{ $sourceCurrencyLabel }}</span> = {{ number_format((float)$cart->exchange_rate,4) }} د.ل</div></div></div>
            <div class="col-6 col-lg-3"><div class="summary-tile h-100"><div class="summary-label">الإجمالي الأصلي</div><div class="summary-value ltr text-end">{{ number_format((float)$cart->subtotal_original,2) }} {{ $sourceCurrencyLabel }}</div></div></div>
            <div class="col-6 col-lg-3"><div class="summary-tile is-primary h-100"><div class="summary-label">الإجمالي بالدينار</div><div class="summary-value text-primary fs-5">{{ number_format((float)$cart->total_lyd,2) }} د.ل</div></div></div>
        </div>

        <div class="surface-card-elevated reveal is-visible p-3 p-md-4">
            <div class="d-flex align-items-center justify-content-between gap-3 mb-2">
                <div><h2 class="h5 fw-bold mb-1">منتجات السلة</h2><div class="small text-secondary">السعر الأصلي والسعر المحول محفوظان حسب سعر الصرف وقت إنشاء السلة.</div></div>
            </div>

            <div class="mt-2">
                @forelse($itemsPage as $item)
                    @php($unitLyd = (float)$item->unit_price_original * (float)$cart->exchange_rate)
                    @php($lineLyd = (float)$item->line_total_original * (float)$cart->exchange_rate)
                    <article class="saved-product">
                        @if($item->image_url)
                            <img class="saved-product-image" src="{{ $item->image_url }}" alt="{{ $item->name }}" loading="lazy">
                        @else
                            <div class="saved-product-image product-image-placeholder">بدون صورة</div>
                        @endif
                        <div class="min-w-0">
                            <div class="saved-product-title">{{ $item->name }}</div>
                            <div class="saved-product-meta d-flex flex-wrap gap-2">
                                @if($item->color)<span>اللون: <strong>{{ $item->color }}</strong></span>@endif
                                @if($item->size)<span>المقاس: <strong>{{ $item->size }}</strong></span>@endif
                                @if($item->variant)<span>الخيار: <strong>{{ $item->variant }}</strong></span>@endif
                            </div>
                            <div class="small text-secondary mt-2">الكمية: <strong>{{ $item->quantity }}</strong> • إجمالي المنتج: <span class="ltr d-inline-block">{{ number_format((float)$item->line_total_original,2) }} {{ $currencyLabels[strtoupper((string)$item->currency)] ?? $item->currency }}</span> / <strong class="text-primary">{{ number_format($lineLyd,2) }} د.ل</strong></div>
                        </div>
                        <div class="saved-product-prices">
                            <div class="small text-secondary">سعر القطعة</div>
                            <div class="original ltr">{{ number_format((float)$item->unit_price_original,2) }} {{ $currencyLabels[strtoupper((string)$item->currency)] ?? $item->currency }}</div>
                            <div class="lyd">{{ number_format($unitLyd,2) }} د.ل</div>
                        </div>
                    </article>
                @empty
                    <div class="empty-state py-5"><div class="empty-state-icon">—</div><h3 class="h5 fw-bold">لا توجد منتجات</h3><p class="text-secondary mb-0">هذه السلة لا تحتوي على عناصر محفوظة.</p></div>
                @endforelse
            </div>
            @if($itemsPage->hasPages())<div class="mt-4 pagination-shell">{{ $itemsPage->links() }}</div>@endif

            <div class="row g-3 mt-4 justify-content-end">
                <div class="col-lg-5"><div class="cart-total-card"><div class="d-flex justify-content-between gap-3 mb-2"><span class="summary-label">الإجمالي الأصلي</span><strong class="ltr">{{ number_format((float)$cart->subtotal_original,2) }} {{ $sourceCurrencyLabel }}</strong></div><div class="d-flex align-items-end justify-content-between gap-3"><span class="summary-label">الإجمالي بالدينار</span><strong class="lyd-grand">{{ number_format((float)$cart->total_lyd,2) }} د.ل</strong></div></div></div>
            </div>

            @if($cart->order)
                <div class="order-request-banner mt-4">
                    <div>
                        <div class="page-kicker">تم إرسال الطلب</div>
                        <h3 class="h5 fw-bold mb-1">تم إرسال هذه السلة كطلب {{ $cart->order->number }}</h3>
                        <p class="small text-secondary mb-0">لا يمكن إرسال السلة مرة أخرى أو حذفها بعد إنشاء الطلب. يمكنك متابعة جميع الإجراءات والملاحظات من صفحة الطلب.</p>
                    </div>
                    <a class="btn btn-primary" href="{{ route('orders.show',$cart->order) }}">متابعة الطلب</a>
                </div>
            @elseif($cart->status === 'saved')
                <div class="order-request-banner mt-4">
                    <div><div class="page-kicker">جاهز للشراء؟</div><h3 class="h5 fw-bold mb-1">إرسال السلة للمراجعة</h3><p class="small text-secondary mb-0">سيقوم المسؤول بمراجعة كل منتج والسعر، وبعد الاعتماد سيحدد العربون أو الدفعة المطلوبة.</p></div>
                    <form method="POST" action="{{ route('orders.from-cart',$cart) }}">@csrf<button class="btn btn-primary" type="submit">إرسال الطلب</button></form>
                </div>
            @endif

            <div class="d-flex flex-column flex-sm-row gap-2 mt-4">
                @if(!$cart->order && $cart->status === 'saved')<form method="POST" action="{{ route('carts.cancel',$cart) }}">@csrf @method('PATCH')<button class="btn btn-danger-soft btn-mobile-full" type="submit">إلغاء السلة</button></form>@endif
                @if(!$cart->order)<form method="POST" action="{{ route('carts.destroy',$cart) }}" data-confirm data-confirm-title="حذف السلة" data-confirm-text="سيتم حذف السلة نهائيًا من حسابك.">@csrf @method('DELETE')<button class="btn btn-ghost btn-mobile-full" type="submit">حذف السلة</button></form>@endif
                <a class="btn btn-outline-primary btn-mobile-full me-sm-auto" href="{{ route('carts.index') }}">الرجوع إلى سلاتي</a>
            </div>
        </div>
    </div>
</section>
@endsection
