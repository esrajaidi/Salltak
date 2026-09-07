@extends('layouts.app')
@section('title','مراجعة السلة')

@section('body')
@php
    $currencyLabel = match(strtoupper($currency)) {
        'USD' => 'دولار',
        'LYD' => 'دينار ليبي',
        'EUR' => 'يورو',
        'GBP' => 'جنيه إسترليني',
        'AED' => 'درهم إماراتي',
        'SAR' => 'ريال سعودي',
        default => strtoupper($currency),
    };
    $countryLabel = match(strtoupper((string)($result->meta['local_country'] ?? ''))) {
        'AE' => 'الإمارات',
        'SA' => 'السعودية',
        'LY' => 'ليبيا',
        default => $result->meta['local_country'] ?? null,
    };
@endphp
<section class="page-section customer-page">
    <div class="container">
        <div class="d-flex flex-column flex-md-row align-items-md-end justify-content-between gap-3 mb-4 reveal is-visible">
            <div>
                <div class="page-kicker">قبل الحفظ</div>
                <h1 class="page-heading">راجع سلتك</h1>
                <p class="page-subtitle">الأسعار مستوردة من المتجر وثابتة. تقدر تغيّر الكمية أو تحذف منتج قبل حفظ السلة.</p>
            </div>
            <a class="btn btn-ghost icon-text-btn" href="{{ route('carts.create') }}"><x-icon name="arrow-left" size="18" /> تغيير الرابط</a>
        </div>

        <div class="surface-card-elevated reveal is-visible p-3 p-md-4">
            <div class="alert {{ $result->status === 'success' ? 'alert-success' : 'alert-warning' }} border-0 import-alert mb-3">
                <div class="import-alert-icon"><x-icon :name="$result->status === 'success' ? 'check' : 'warning'" size="22" /></div>
                <div class="flex-grow-1">
                    <div class="fw-bold">{{ $result->status === 'success' ? 'تم جلب السلة' : 'تحتاج مراجعة' }}</div>
                    <div class="small mt-1">{{ $result->message }}</div>
                    @if($result->status === 'success')
                        <div class="small mt-1">تم العثور على <strong>{{ count($previewItems) }}</strong> منتج/منتجات من السلة.</div>
                    @endif
                </div>
            </div>

            @if(!empty($result->meta['group_id']))
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 p-3 rounded-4 border bg-light mb-4">
                    <div>
                        <div class="small text-secondary">سلة SHEIN المشتركة</div>
                        <div class="fw-bold mt-1">رقم المجموعة <span class="ltr d-inline-block">#{{ $result->meta['group_id'] }}</span></div>
                        @if($countryLabel)<div class="small text-secondary mt-1">بلد السلة: {{ $countryLabel }}</div>@endif
                    </div>
                    <form method="POST" action="{{ route('carts.analyze') }}" class="m-0">
                        @csrf
                        <input type="hidden" name="source_url" value="{{ $sourceUrl }}">
                        <button class="btn btn-outline-primary btn-sm icon-text-btn" type="submit"><x-icon name="activity" size="16" /> إعادة جلب السلة</button>
                    </form>
                </div>
            @endif

            @if(empty($previewItems))
                <div class="empty-state-card text-center py-5">
                    <div class="empty-state-icon mx-auto mb-3"><x-icon name="carts" size="30" /></div>
                    <h2 class="h5 fw-bold">ما لقيناش منتجات قابلة للحفظ</h2>
                    <p class="text-secondary mb-2">أعد جلب الرابط، ولو استمرت المشكلة تأكد أن رابط مشاركة السلة مازال صالح.</p>
                    <div class="small text-secondary mb-3">عملة التسعير المعتمدة: <strong>{{ $currencyLabel }}</strong></div>
                    <a class="btn btn-primary" href="{{ route('carts.create') }}">جلب سلة من جديد</a>
                </div>
            @else
                <form method="POST" action="{{ route('carts.store') }}" id="cartForm">
                    @csrf
                    <input type="hidden" name="preview_token" value="{{ $previewToken }}">

                    <div class="row g-2 g-md-3 mb-4">
                        <div class="col-6 col-lg-3"><div class="summary-tile"><div class="summary-label">المتجر</div><div class="summary-value">{{ $store?->name ?? 'موقع خارجي' }}</div></div></div>
                        <div class="col-6 col-lg-3"><div class="summary-tile"><div class="summary-label">العملة</div><div class="summary-value">{{ $currencyLabel }}</div></div></div>
                        <div class="col-6 col-lg-3"><div class="summary-tile"><div class="summary-label">سعر الصرف</div><div class="summary-value">1 {{ $currencyLabel }} = {{ number_format((float)$rate, 4) }} د.ل</div></div></div>
                        <div class="col-6 col-lg-3"><div class="summary-tile"><div class="summary-label">عدد المنتجات</div><div class="summary-value" id="itemsCount">{{ count($previewItems) }}</div></div></div>
                    </div>

                    <div class="cart-toolbar d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-3">
                        <div>
                            <h2 class="h5 fw-bold mb-1">منتجات السلة</h2>
                            <div class="small text-secondary">سعر ثابت من المتجر — لا يمكن تغييره من حساب العميل.</div>
                        </div>
                        <div class="price-lock-pill"><x-icon name="check" size="16" /> الأسعار محمية</div>
                    </div>

                    <div id="items" class="cart-preview-items">
                        @foreach($previewItems as $i => $item)
                            <article class="product-editor immutable-product-card" data-row data-price="{{ (float)($item['unit_price_original'] ?? 0) }}" data-page-item>
                                <input type="hidden" name="items[{{ $i }}][key]" value="{{ $item['_key'] }}">
                                <div class="row g-3 align-items-start">
                                    <div class="col-auto">
                                        @if(!empty($item['image_url']))
                                            <img class="product-image" src="{{ $item['image_url'] }}" alt="{{ $item['name'] ?? 'منتج' }}" loading="lazy">
                                        @else
                                            <div class="product-image product-image-placeholder"><x-icon name="box" size="24" /></div>
                                        @endif
                                    </div>

                                    <div class="col min-w-0">
                                        <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                                            <div class="small text-secondary fw-bold">المنتج {{ $i + 1 }}</div>
                                            <button class="btn btn-danger-soft btn-sm remove icon-text-btn" type="button"><x-icon name="trash" size="15" /> حذف</button>
                                        </div>
                                        <h3 class="product-title-static">{{ $item['name'] ?? 'منتج' }}</h3>

                                        <div class="product-meta-chips mt-2">
                                            @if(!empty($item['color']))<span><strong>اللون:</strong> {{ $item['color'] }}</span>@endif
                                            @if(!empty($item['size']) && $item['size'] !== '?')<span><strong>المقاس:</strong> {{ $item['size'] }}</span>@endif
                                            @if(!empty($item['variant']))<span><strong>رمز الخيار:</strong> <bdi>{{ $item['variant'] }}</bdi></span>@endif
                                        </div>

                                        @if(!empty($item['external_id']))
                                            <div class="small text-secondary mt-2">معرّف المنتج: <code class="ltr">{{ $item['external_id'] }}</code></div>
                                        @endif
                                        @if(!empty($item['product_url']))
                                            <a class="small product-source-link" href="{{ $item['product_url'] }}" target="_blank" rel="noopener">فتح المنتج في المتجر</a>
                                        @endif
                                    </div>

                                    <div class="col-12 col-lg-5 col-xl-4">
                                        <div class="product-price-panel">
                                            <div class="price-pair mb-2">
                                                <div class="price-box">
                                                    <span class="price-label">سعر القطعة</span>
                                                    <div class="price-value original-price">{{ number_format((float)($item['unit_price_original'] ?? 0), 2) }} {{ $currencyLabel }}</div>
                                                    <div class="price-lock-note"><x-icon name="check" size="14" /> سعر ثابت من المتجر</div>
                                                </div>
                                                <div class="price-box lyd">
                                                    <span class="price-label">بالدينار الليبي</span>
                                                    <span class="price-value unit-lyd">0.00 د.ل</span>
                                                </div>
                                            </div>

                                            <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-end justify-content-between gap-2">
                                                <div>
                                                    <div class="small text-secondary fw-bold mb-1">الكمية</div>
                                                    <div class="quantity-control">
                                                        <button class="qty-btn qty-minus" type="button" aria-label="نقص الكمية"><x-icon name="minus" size="15" /></button>
                                                        <input class="form-control qty" type="number" min="1" max="999" name="items[{{ $i }}][quantity]" value="{{ $item['quantity'] ?? 1 }}" required>
                                                        <button class="qty-btn qty-plus" type="button" aria-label="زيادة الكمية"><x-icon name="plus" size="15" /></button>
                                                    </div>
                                                </div>
                                                <div class="text-sm-end">
                                                    <div class="small text-secondary fw-bold">إجمالي المنتج</div>
                                                    <div class="fw-bold line-total-original">0.00 {{ $currencyLabel }}</div>
                                                    <div class="fw-bold text-primary line-total-lyd">0.00 د.ل</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <nav class="preview-pagination mt-3" id="previewPagination" aria-label="صفحات منتجات السلة"></nav>

                    <div class="row g-3 mt-3 align-items-stretch">
                        <div class="col-lg-5 ms-lg-auto">
                            <div class="cart-total-card h-100">
                                <div class="d-flex align-items-center justify-content-between gap-3 mb-2"><span class="summary-label">إجمالي السلة</span><strong id="subtotal">0.00 {{ $currencyLabel }}</strong></div>
                                <div class="d-flex align-items-end justify-content-between gap-3"><div><div class="summary-label">الإجمالي بالدينار الليبي</div><div class="small opacity-75 mt-1">حسب سعر الصرف {{ number_format((float)$rate,4) }}</div></div><div class="lyd-grand text-nowrap" id="lyd">0.00 د.ل</div></div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-column-reverse flex-sm-row justify-content-end gap-2 mt-4">
                        <a class="btn btn-ghost" href="{{ route('carts.create') }}">إلغاء</a>
                        <button class="btn btn-primary px-4 icon-text-btn" type="submit"><x-icon name="check" size="18" /> حفظ السلة</button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</section>
@endsection

@if(!empty($previewItems))
@push('scripts')
<script>
(() => {
    const rate = {{ (float)$rate }};
    const currencyLabel = @json($currencyLabel);
    const pageSize = 10;
    let currentPage = 1;

    const money = value => (Number(value) || 0).toFixed(2);
    const allRows = () => Array.from(document.querySelectorAll('[data-row]'));

    function calc(){
        let subtotal = 0;
        const rows = allRows();
        rows.forEach(row => {
            const price = Math.max(0, parseFloat(row.dataset.price) || 0);
            const qtyInput = row.querySelector('.qty');
            const qty = Math.max(1, Math.min(999, parseInt(qtyInput?.value) || 1));
            if (qtyInput && Number(qtyInput.value) !== qty) qtyInput.value = qty;
            const line = price * qty;
            subtotal += line;
            row.querySelector('.unit-lyd').textContent = money(price * rate) + ' د.ل';
            row.querySelector('.line-total-original').textContent = money(line) + ' ' + currencyLabel;
            row.querySelector('.line-total-lyd').textContent = money(line * rate) + ' د.ل';
        });
        document.getElementById('subtotal').textContent = money(subtotal) + ' ' + currencyLabel;
        document.getElementById('lyd').textContent = money(subtotal * rate) + ' د.ل';
        document.getElementById('itemsCount').textContent = rows.length;
    }

    function renderPagination(){
        const rows = allRows();
        const totalPages = Math.max(1, Math.ceil(rows.length / pageSize));
        if (currentPage > totalPages) currentPage = totalPages;
        rows.forEach((row, index) => row.hidden = Math.floor(index / pageSize) + 1 !== currentPage);

        const nav = document.getElementById('previewPagination');
        if (!nav) return;
        if (totalPages <= 1) { nav.innerHTML = ''; return; }

        let buttons = `<button type="button" class="preview-page-btn" data-preview-page="${Math.max(1,currentPage-1)}" ${currentPage === 1 ? 'disabled' : ''}>السابق</button>`;
        for (let page = 1; page <= totalPages; page++) {
            buttons += `<button type="button" class="preview-page-btn ${page === currentPage ? 'active' : ''}" data-preview-page="${page}">${page}</button>`;
        }
        buttons += `<button type="button" class="preview-page-btn" data-preview-page="${Math.min(totalPages,currentPage+1)}" ${currentPage === totalPages ? 'disabled' : ''}>التالي</button>`;
        nav.innerHTML = buttons;
    }

    document.addEventListener('input', event => {
        if (event.target.matches('.qty')) calc();
    });

    document.addEventListener('click', event => {
        const pageButton = event.target.closest('[data-preview-page]');
        if (pageButton && !pageButton.disabled) {
            currentPage = Number(pageButton.dataset.previewPage) || 1;
            renderPagination();
            document.getElementById('items')?.scrollIntoView({behavior:'smooth',block:'start'});
            return;
        }

        const plus = event.target.closest('.qty-plus');
        const minus = event.target.closest('.qty-minus');
        if (plus || minus){
            const input = event.target.closest('.quantity-control').querySelector('.qty');
            const value = Math.max(1, parseInt(input.value) || 1);
            input.value = plus ? Math.min(999, value + 1) : Math.max(1, value - 1);
            calc();
            return;
        }

        const remove = event.target.closest('.remove');
        if (remove){
            const rows = allRows();
            if (rows.length <= 1) {
                if (window.Swal) Swal.fire({icon:'info',title:'لازم يبقى منتج واحد على الأقل',confirmButtonText:'حسنًا'});
                return;
            }
            const execute = () => {
                remove.closest('[data-row]').remove();
                calc();
                renderPagination();
            };
            if (window.Swal) {
                Swal.fire({icon:'warning',title:'حذف المنتج؟',text:'لن يتم حفظ هذا المنتج ضمن السلة.',showCancelButton:true,confirmButtonText:'نعم، حذف',cancelButtonText:'إلغاء',reverseButtons:true})
                    .then(result => { if (result.isConfirmed) execute(); });
            } else if (window.confirm('حذف المنتج من السلة؟')) execute();
        }
    });

    calc();
    renderPagination();
})();
</script>
@endpush
@endif
