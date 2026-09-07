@extends('layouts.app')
@section('title','سلة جديدة')
@section('body')
<section class="page-section customer-page">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-9 col-xxl-8">
                <div class="d-flex flex-column flex-md-row align-items-md-end justify-content-between gap-3 mb-4 reveal is-visible">
                    <div><div class="page-kicker">إضافة سلة</div><h1 class="page-heading">ألصق رابط سلتك</h1><p class="page-subtitle">استخدم رابط مشاركة السلة من SHEIN، وسنحاول جلب العناصر الحقيقية وعرض سعرها بالدينار الليبي.</p></div>
                    <a class="btn btn-ghost" href="{{ route('carts.index') }}">سلاتي</a>
                </div>

                <div class="surface-card-elevated reveal p-3 p-md-4 p-lg-5">
                    <form method="POST" action="{{ route('carts.analyze') }}" id="analyzeForm">
                        @csrf
                        <label class="form-label" for="source_url">رابط مشاركة السلة</label>
                        <div class="input-group input-group-lg flex-column flex-sm-row gap-2 gap-sm-0">
                            <input id="source_url" class="form-control ltr @error('source_url') is-invalid @enderror" type="url" name="source_url" value="{{ old('source_url') }}" placeholder="https://m.shein.com/ar/cart/share/landing?..." required>
                            <button class="btn btn-primary px-4" type="submit"><span class="submit-label">جلب السلة</span><span class="spinner-border spinner-border-sm d-none" aria-hidden="true"></span></button>
                        </div>
                        <div class="form-text mt-2">الصق رابط <strong>Share Cart</strong> وليس رابط صفحة السلة العادي.</div>
                    </form>

                    <div class="row g-3 mt-4">
                        <div class="col-sm-4"><div class="summary-tile"><div class="summary-label">1. الصق الرابط</div><div class="summary-value fs-6">رابط مشاركة SHEIN</div></div></div>
                        <div class="col-sm-4"><div class="summary-tile"><div class="summary-label">2. راجع العناصر</div><div class="summary-value fs-6">الصورة والسعر والكمية</div></div></div>
                        <div class="col-sm-4"><div class="summary-tile"><div class="summary-label">3. احفظ</div><div class="summary-value fs-6">السلة في حسابك</div></div></div>
                    </div>

                    <div class="alert alert-light border mt-4 mb-4 small text-secondary">
                        <strong class="text-dark">ملاحظة:</strong> تشغيل الاستيراد يتم في الخلفية. لو SHEIN منع القراءة أو طلب تحققًا أمنيًا، سيظهر لك تنبيه واضح بدون إضافة منتجات غير موجودة في سلتك.
                    </div>

                    <div><div class="small fw-bold text-secondary mb-2">المواقع المفعّلة</div><div class="d-flex flex-wrap gap-2">@foreach($stores as $store)<span class="store-chip"><strong>{{ $store->name }}</strong><span class="small text-secondary">{{ $store->currency }}</span></span>@endforeach</div></div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
@push('scripts')
<script>
document.getElementById('analyzeForm')?.addEventListener('submit', function(){
    const btn=this.querySelector('button[type="submit"]');
    btn.disabled=true; btn.querySelector('.submit-label').textContent='جاري جلب السلة...'; btn.querySelector('.spinner-border').classList.remove('d-none');
});
</script>
@endpush
