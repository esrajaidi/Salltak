@extends('layouts.admin')
@section('title','طرق الدفع')
@section('admin-content')
@php
    $activeCount = $activeTotal;
    $readyCount = $readyTotal;
    $docLabels = [
        'public_docs_partner_api_restricted' => 'وثائق عامة + ربط برمجي عبر جهة مرخصة',
        'public_standard' => 'معيار رسمي منشور',
        'merchant_docs_required' => 'يحتاج وثائق/عقد التاجر',
        'acquirer_docs_required' => 'يحتاج وثائق المصرف/المعالج',
        'merchant_account_required' => 'يحتاج بيانات حساب التاجر',
        'internal' => 'إعداد داخلي',
        'custom' => 'مخصص',
    ];
@endphp

<div class="admin-page-header reveal is-visible mb-4">
    <div class="small text-primary fw-bold mb-1">المدفوعات</div>
    <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-between gap-3">
        <div>
            <h1 class="page-heading">طرق الدفع في ليبيا</h1>
            <p class="page-subtitle mb-0">فعّل فقط الطريقة التي أكملت بيانات استقبالها. العميل لا يرى أي طريقة ناقصة أو متوقفة.</p>
        </div>
        <form method="POST" action="{{ route('admin.payment-methods.install-libya') }}" data-confirm data-confirm-title="تحديث دليل طرق الدفع" data-confirm-text="سيتم تحديث الطرق مع الحفاظ على بيانات التاجر الموجودة.">
            @csrf
            <button class="btn btn-primary" type="submit">تثبيت / تحديث الدليل الليبي</button>
        </form>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3"><div class="summary-tile h-100"><div class="summary-label">إجمالي الطرق</div><div class="summary-value">{{ $methodsTotal }}</div></div></div>
    <div class="col-6 col-xl-3"><div class="summary-tile h-100"><div class="summary-label">المفعلة</div><div class="summary-value text-success">{{ $activeCount }}</div></div></div>
    <div class="col-6 col-xl-3"><div class="summary-tile h-100"><div class="summary-label">جاهزة للتفعيل</div><div class="summary-value">{{ $readyCount }}</div></div></div>
    <div class="col-6 col-xl-3"><div class="summary-tile h-100"><div class="summary-label">تحتاج إعداد</div><div class="summary-value text-warning">{{ $methodsTotal - $readyCount }}</div></div></div>
</div>

<div class="surface-card admin-panel p-3 mb-4 reveal is-visible">
    <div class="row g-2 align-items-center">
        <div class="col-lg-5">
            <label class="visually-hidden" for="paymentMethodSearch">بحث</label>
            <input id="paymentMethodSearch" class="form-control" type="search" placeholder="ابحث باسم الطريقة أو المزود..." data-payment-filter="search">
        </div>
        <div class="col-lg-7">
            <div class="payment-filter-pills" data-payment-filter="buttons">
                <button type="button" class="btn btn-sm btn-primary active" data-filter="all">الكل</button>
                <button type="button" class="btn btn-sm btn-light" data-filter="active">المفعلة</button>
                <button type="button" class="btn btn-sm btn-light" data-filter="inactive">المتوقفة</button>
                <button type="button" class="btn btn-sm btn-light" data-filter="ready">جاهزة</button>
                <button type="button" class="btn btn-sm btn-light" data-filter="needs_config">تحتاج إعداد</button>
            </div>
        </div>
    </div>
</div>

<div class="accordion mb-4" id="customPaymentAccordion">
    <div class="accordion-item border-0 surface-card overflow-hidden">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#customPaymentForm">إضافة طريقة دفع مخصصة</button>
        </h2>
        <div id="customPaymentForm" class="accordion-collapse collapse" data-bs-parent="#customPaymentAccordion">
            <div class="accordion-body">
                <form method="POST" action="{{ route('admin.payment-methods.store') }}" class="row g-3">@csrf
                    <div class="col-md-4"><label class="form-label">الاسم</label><input class="form-control" name="name" required></div>
                    <div class="col-md-4"><label class="form-label">الكود</label><input class="form-control ltr" name="code" required placeholder="custom_method"></div>
                    <div class="col-md-4"><label class="form-label">النوع</label><select class="form-select" name="type"><option value="manual">يدوي</option><option value="bank">مصرفي</option><option value="wallet">محفظة</option><option value="api">بوابة/بطاقات</option><option value="cash">نقدي</option></select></div>
                    <div class="col-md-3"><label class="form-label">الترتيب</label><input class="form-control" type="number" name="sort_order" value="999" min="0"></div>
                    <div class="col-md-3"><label class="form-label">أقل مبلغ</label><input class="form-control" type="number" step="0.01" name="min_amount"></div>
                    <div class="col-md-3"><label class="form-label">أعلى مبلغ</label><input class="form-control" type="number" step="0.01" name="max_amount"></div>
                    <div class="col-md-3"><label class="form-label">نوع الرسوم</label><select class="form-select" name="fee_type"><option value="none">بدون</option><option value="percentage">نسبة</option><option value="fixed">مبلغ ثابت</option></select></div>
                    <div class="col-md-3"><label class="form-label">قيمة الرسوم</label><input class="form-control" type="number" step="0.01" name="fee_value" value="0"></div>
                    <div class="col-md-3"><label class="form-label">وضع الربط</label><select class="form-select" name="config[integration_mode]"><option value="manual_verification">تحقق يدوي</option><option value="external_link">رابط دفع خارجي</option></select></div>
                    <div class="col-md-3"><label class="form-label">إثبات الدفع</label><select class="form-select" name="config[proof_mode]"><option value="reference_or_receipt">رقم أو إيصال</option><option value="reference">رقم عملية</option><option value="receipt">إيصال</option><option value="none">بدون</option></select></div>
                    <div class="col-md-3"><label class="form-label">العملة</label><input class="form-control ltr" name="config[currency]" value="LYD"></div>
                    <input type="hidden" name="config[availability]" value="online">
                    <div class="col-12"><label class="form-label">تعليمات العميل</label><textarea class="form-control" name="instructions" rows="2"></textarea></div>
                    <div class="col-12"><button class="btn btn-primary">إضافة الطريقة</button></div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="payment-method-grid" id="paymentMethodGrid">
@forelse($methods as $method)
    @php
        $cfg = $method->config ?? [];
        $schema = $method->schemaDefinition();
        $issues = $method->activationIssues();
        $ready = $issues === [];
        $modeLabel = $schema['modes'][$method->integrationMode()] ?? $method->integrationMode();
        $searchText = strtolower($method->name.' '.($cfg['provider_name']??'').' '.($cfg['official_activity']??'').' '.$method->code);
    @endphp
    <article class="payment-method-card surface-card reveal is-visible"
             data-payment-card
             data-state="{{ $method->is_active ? 'active' : 'inactive' }}"
             data-readiness="{{ $ready ? 'ready' : 'needs_config' }}"
             data-search="{{ $searchText }}">
        <div class="payment-method-card-head">
            <div class="payment-method-icon" aria-hidden="true">
                <x-icon :name="match($method->type){'wallet'=>'wallet','bank'=>'exchange','api'=>'payment','cash'=>'cash',default=>'check'}" size="20" />
            </div>
            <div class="flex-grow-1 min-w-0">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                    <h2 class="h6 fw-bold mb-0 text-truncate">{{ $method->name }}</h2>
                    <span class="status-badge {{ $method->is_active ? 'status-success' : 'status-danger' }}">{{ $method->is_active ? 'مفعلة' : 'متوقفة' }}</span>
                </div>
                <div class="small text-secondary text-truncate">{{ $cfg['provider_name'] ?? $method->code }}</div>
            </div>
        </div>

        <div class="payment-method-meta">
            <span><b>الوضع:</b> {{ $modeLabel }}</span>
            <span><b>الظهور:</b> {{ ($cfg['availability']??'online')==='delivery_only' ? 'التسليم فقط' : (($cfg['availability']??'online')==='all'?'كل مراحل الدفع':'الدفع المسبق') }}</span>
            <span class="{{ $ready ? 'text-success' : 'text-warning' }}"><b>الإعداد:</b> {{ $ready ? 'جاهز' : 'ناقص' }}</span>
        </div>

        @if(!empty($cfg['official_activity']))
            <p class="payment-method-activity">{{ $cfg['official_activity'] }}</p>
        @endif

        @if(!$ready)
            <div class="payment-readiness-note">{{ $issues[0] }}</div>
        @endif

        <div class="payment-method-actions">
            <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#paymentMethodModal{{ $method->id }}">الإعدادات</button>
            <form method="POST" action="{{ route('admin.payment-methods.toggle',$method) }}" data-confirm data-confirm-title="تغيير حالة طريقة الدفع" data-confirm-text="سيتم تحديث ظهور هذه الطريقة للعملاء حسب اكتمال إعدادها.">@csrf @method('PATCH')
                <button class="btn {{ $method->is_active ? 'btn-danger-soft' : 'btn-primary' }} btn-sm" type="submit">{{ $method->is_active ? 'إيقاف' : 'تفعيل' }}</button>
            </form>
        </div>
    </article>

    <div class="modal fade" id="paymentMethodModal{{ $method->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header">
                    <div>
                        <h2 class="modal-title h5 fw-bold mb-1">{{ $method->name }}</h2>
                        <div class="small text-secondary">{{ $schema['title'] ?? 'إعداد طريقة الدفع' }}</div>
                    </div>
                    <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
                <form method="POST" action="{{ route('admin.payment-methods.update',$method) }}">@csrf @method('PUT')
                    <div class="modal-body">
                        <input type="hidden" name="code" value="{{ $method->code }}">
                        <input type="hidden" name="type" value="{{ $method->type }}">
                        <input type="hidden" name="config[documentation_status]" value="{{ $cfg['documentation_status'] ?? 'custom' }}">
                        <input type="hidden" name="config[official_source]" value="{{ $cfg['official_source'] ?? '' }}">
                        <input type="hidden" name="config[provider_name]" value="{{ $cfg['provider_name'] ?? '' }}">
                        <input type="hidden" name="config[official_activity]" value="{{ $cfg['official_activity'] ?? '' }}">

                        <div class="payment-doc-banner mb-4">
                            <div>
                                <strong>{{ $docLabels[$cfg['documentation_status'] ?? 'custom'] ?? ($cfg['documentation_status'] ?? 'مخصص') }}</strong>
                                <div class="small text-secondary mt-1">لا يتم إنشاء ربط برمجي وهمي. أي بيانات سرية هنا تأتي من عقد التاجر أو المصرف فقط.</div>
                            </div>
                            @if(!empty($cfg['official_source']) && str_starts_with($cfg['official_source'],'http'))
                                <a class="btn btn-light btn-sm" href="{{ $cfg['official_source'] }}" target="_blank" rel="noopener">المصدر الرسمي</a>
                            @endif
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6"><label class="form-label">الاسم الظاهر</label><input class="form-control" name="name" value="{{ $method->name }}" required></div>
                            <div class="col-md-2"><label class="form-label">الترتيب</label><input class="form-control" type="number" name="sort_order" value="{{ $method->sort_order }}" min="0" required></div>
                            <div class="col-md-2"><label class="form-label">أقل مبلغ</label><input class="form-control" type="number" step="0.01" name="min_amount" value="{{ $method->min_amount }}"></div>
                            <div class="col-md-2"><label class="form-label">أعلى مبلغ</label><input class="form-control" type="number" step="0.01" name="max_amount" value="{{ $method->max_amount }}"></div>
                            <div class="col-md-3"><label class="form-label">الرسوم</label><select class="form-select" name="fee_type">@foreach(['none'=>'بدون رسوم','percentage'=>'نسبة %','fixed'=>'مبلغ ثابت'] as $k=>$v)<option value="{{ $k }}" @selected($method->fee_type===$k)>{{ $v }}</option>@endforeach</select></div>
                            <div class="col-md-3"><label class="form-label">قيمة الرسوم</label><input class="form-control" type="number" step="0.01" min="0" name="fee_value" value="{{ $method->fee_value }}" required></div>
                            <div class="col-md-3"><label class="form-label">وضع الربط</label><select class="form-select" name="config[integration_mode]">@foreach(($schema['modes']??['manual_verification'=>'تحقق يدوي']) as $k=>$v)<option value="{{ $k }}" @selected($method->integrationMode()===$k)>{{ $v }}</option>@endforeach</select></div>
                            <div class="col-md-3"><label class="form-label">متى يظهر؟</label><select class="form-select" name="config[availability]"><option value="online" @selected(($cfg['availability']??'online')==='online')>الدفع المسبق</option><option value="delivery_only" @selected(($cfg['availability']??'')==='delivery_only')>وقت التسليم أو الرصيد الأخير</option><option value="all" @selected(($cfg['availability']??'')==='all')>كل مراحل الدفع المسموحة</option></select></div>
                            <div class="col-md-3"><label class="form-label">إثبات الدفع</label><select class="form-select" name="config[proof_mode]">@foreach(['reference_or_receipt'=>'رقم عملية أو إيصال','reference'=>'رقم عملية فقط','receipt'=>'إيصال فقط','none'=>'بدون إثبات'] as $k=>$v)<option value="{{ $k }}" @selected($method->proofMode()===$k)>{{ $v }}</option>@endforeach</select></div>
                            <div class="col-md-3"><label class="form-label">العملة</label><input class="form-control ltr" name="config[currency]" value="{{ $cfg['currency'] ?? 'LYD' }}"></div><div class="col-12"><div class="payment-capability-box"><div><strong>استخدام الطريقة داخل دورة الطلب</strong><small>حدد هل تقبل هذه الطريقة العربون أو الرصيد المتبقي.</small></div><label class="form-check form-switch"><input class="form-check-input" type="checkbox" name="config[allow_deposit]" value="1" @checked($method->allowsDeposit())><span class="form-check-label">تسمح بدفع العربون</span></label><label class="form-check form-switch"><input class="form-check-input" type="checkbox" name="config[allow_balance]" value="1" @checked($method->allowsBalance())><span class="form-check-label">تسمح بسداد الرصيد</span></label></div></div>
                        </div>

                        @if(!empty($schema['fields']))
                            <div class="payment-config-section">
                                <h3 class="h6 fw-bold mb-3">بيانات {{ $schema['title'] ?? 'الدفع' }}</h3>
                                <div class="row g-3">
                                @foreach($schema['fields'] as $key=>$field)
                                    @php($isSecret = (bool)($field['secret']??false))
                                    <div class="{{ ($field['type']??'text')==='textarea' ? 'col-12' : 'col-md-6' }}">
                                        <label class="form-label">{{ $field['label'] ?? $key }}</label>
                                        @if(($field['type']??'text')==='textarea')
                                            <textarea class="form-control {{ !empty($field['ltr'])?'ltr':'' }}" name="config[{{ $key }}]" rows="3">{{ $cfg[$key] ?? '' }}</textarea>
                                        @else
                                            <input class="form-control {{ !empty($field['ltr'])?'ltr':'' }}" type="{{ $isSecret ? 'password' : (($field['type']??'text')==='url'?'url':'text') }}" name="config[{{ $key }}]" value="{{ $isSecret ? '' : ($cfg[$key] ?? '') }}" @if($isSecret) placeholder="اتركه فارغًا للاحتفاظ بالقيمة الحالية" @endif>
                                        @endif
                                        @if($isSecret && !empty($cfg[$key]))<div class="form-text text-success">محفوظ ومشفر — اترك الحقل فارغًا للاحتفاظ به.</div>@endif
                                    </div>
                                @endforeach
                                </div>
                            </div>
                        @endif

                        <div class="mt-4"><label class="form-label">تعليمات تظهر للعميل</label><textarea class="form-control" name="instructions" rows="3">{{ $method->instructions }}</textarea></div>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <div class="small {{ $ready ? 'text-success' : 'text-warning' }}">{{ $ready ? 'الإعداد مكتمل ويمكن تفعيل الطريقة.' : implode(' ', $issues) }}</div>
                        <div class="d-flex gap-2"><button type="button" class="btn btn-light" data-bs-dismiss="modal">إلغاء</button><button class="btn btn-primary" type="submit">حفظ الإعدادات</button></div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@empty
    <div class="surface-card p-4 text-center text-secondary">لا توجد طرق دفع. اضغط تثبيت/تحديث الدليل الليبي.</div>
@endforelse
</div>
@if($methodsPage->hasPages())<div class="mt-4 pagination-shell">{{ $methodsPage->links() }}</div>@endif

<script>
(() => {
    const search = document.querySelector('[data-payment-filter="search"]');
    const buttons = [...document.querySelectorAll('[data-filter]')];
    const cards = [...document.querySelectorAll('[data-payment-card]')];
    let filter = 'all';

    const apply = () => {
        const q = (search?.value || '').trim().toLowerCase();
        cards.forEach(card => {
            const stateOk = filter === 'all' || card.dataset.state === filter || card.dataset.readiness === filter;
            const searchOk = !q || (card.dataset.search || '').includes(q);
            card.classList.toggle('d-none', !(stateOk && searchOk));
        });
    };

    search?.addEventListener('input', apply);
    buttons.forEach(btn => btn.addEventListener('click', () => {
        filter = btn.dataset.filter;
        buttons.forEach(b => { b.classList.remove('btn-primary','active'); b.classList.add('btn-light'); });
        btn.classList.remove('btn-light'); btn.classList.add('btn-primary','active');
        apply();
    }));
})();
</script>
@endsection
