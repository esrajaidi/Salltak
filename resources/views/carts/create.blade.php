@extends('layouts.app')
@section('title','سلة جديدة')
@section('body')
<section class="page-section customer-page">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-9 col-xxl-8">
                <div class="d-flex flex-column flex-md-row align-items-md-end justify-content-between gap-3 mb-4 reveal is-visible">
                    <div><div class="page-kicker">إضافة سلة</div><h1 class="page-heading">ألصق رابط سلتك</h1><p class="page-subtitle">ألصق رابط مشاركة السلة من SHEIN وسنجلب المنتجات الحقيقية والصور والمقاسات والأسعار بالدولار ثم نحسبها بالدينار.</p></div>
                    <a class="btn btn-ghost icon-text-btn" href="{{ route('carts.index') }}"><x-icon name="carts"/>سلاتي</a>
                </div>

                <div class="surface-card-elevated reveal p-3 p-md-4 p-lg-5 import-card">
                    <form method="POST" action="{{ route('carts.analyze') }}" id="analyzeForm" data-cart-import>
                        @csrf
                        <label class="form-label fw-bold" for="source_url">رابط مشاركة السلة</label>
                        <div class="cart-link-entry d-grid d-sm-flex gap-2 align-items-stretch">
                            <input id="source_url" class="form-control form-control-lg ltr w-100 flex-grow-1 @error('source_url') is-invalid @enderror" type="text" inputmode="url" autocomplete="off" name="source_url" value="{{ old('source_url') }}" placeholder="ألصق رابط SHEIN أو نص المشاركة كاملًا" required>
                            <button class="btn btn-primary px-4 icon-text-btn flex-shrink-0" type="submit"><x-icon name="search"/><span class="submit-label">جلب السلة</span><span class="spinner-border spinner-border-sm d-none" aria-hidden="true"></span></button>
                        </div>
                        <div class="form-text mt-2">يمكنك لصق <strong>الرابط فقط</strong> أو <strong>نص المشاركة كاملًا</strong> من SHEIN، وسنستخرج الرابط تلقائيًا.</div>
                    </form>

                    <div class="row g-3 mt-4">
                        <div class="col-sm-4"><div class="import-step-card"><span><x-icon name="globe"/></span><div><strong>1. الصق الرابط</strong><small>رابط المشاركة من المتجر.</small></div></div></div>
                        <div class="col-sm-4"><div class="import-step-card"><span><x-icon name="box"/></span><div><strong>2. نستخرج المنتجات</strong><small>الصور والمقاس واللون والسعر.</small></div></div></div>
                        <div class="col-sm-4"><div class="import-step-card"><span><x-icon name="check"/></span><div><strong>3. راجع واحفظ</strong><small>السعر يظهر فقط ولا يتغير.</small></div></div></div>
                    </div>

                    <div class="import-note mt-4"><x-icon name="clock"/><div><strong>الجلب قد يحتاج عدة ثوانٍ</strong><span>نفتح رابط المشاركة ونقرأ المنتجات من SHEIN. لو طلب الموقع تحققًا أمنيًا سنعطيك رسالة واضحة بدون إضافة منتجات غير موجودة في السلة.</span></div></div>

                    <div class="mt-4"><div class="small fw-bold text-secondary mb-2">المواقع المفعّلة</div><div class="d-flex flex-wrap gap-2">@foreach($stores as $store)<span class="store-chip"><strong>{{ $store->name }}</strong><span class="small text-secondary">{{ $store->currency==='USD'?'دولار أمريكي':$store->currency }}</span></span>@endforeach</div></div>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="cart-import-overlay" id="cart-import-overlay" aria-hidden="true">
    <div class="cart-import-dialog" role="status" aria-live="polite">
        <div class="cart-import-visual"><div class="import-orbit"></div><span class="import-bag"><x-icon name="carts" size="34"/></span></div>
        <div class="page-kicker">جاري تجهيز سلتك</div>
        <h2 class="h4 fw-bold mb-2" data-import-title>استنا شوية... جاري جلب السلة</h2>
        <p class="text-secondary mb-3" data-import-message>لا تقفلي الصفحة، بنجيب المنتجات الحقيقية ونجهزها للعرض.</p>
        <div class="import-progress-line"><span></span></div>
        <div class="import-progress-dots" aria-hidden="true"><i></i><i></i><i></i></div>
        <small class="text-secondary">وقت الجلب يعتمد على عدد المنتجات واستجابة المتجر.</small>
    </div>
</div>
@endsection
