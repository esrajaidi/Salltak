@extends('layouts.admin')
@section('title','جلسة حساب SHEIN')
@section('admin-content')
<div class="admin-page-header mb-4">
    <div class="small text-primary fw-bold mb-1">إعدادات الاستيراد</div>
    <h1 class="page-heading">جلسة حساب الشراء في SHEIN</h1>
    <p class="page-subtitle">إدارة جلسة الحساب الخاصة بالشراء، للاختبار على Mac ثم استعمالها اختياريًا في سلتك المحلية أو أونلاين.</p>
</div>

<div class="alert alert-warning border-0 p-3 mb-4" role="alert">
    <strong>تنبيه أمني:</strong>
    ملف الجلسة يمنح وصولًا لحساب SHEIN مثل تسجيل الدخول. لا ترسليه بالمحادثات، ولا تحفظيه في GitHub.
    ارفعيه هنا فقط من لوحة المدير على اتصال HTTPS. لا يعني استعمال الجلسة أن SHEIN ستُرجع سعر تطبيقك دائمًا،
    وقد تفرض SHEIN قيودًا على الاستعمال الآلي. لا تتجاوزي Captcha أو أي تحقق أمني.
</div>

<div class="row g-3 mb-4">
    <div class="col-sm-6">
        <div class="surface-card admin-panel p-4 h-100">
            <div class="small text-secondary mb-2">حالة حفظ الجلسة</div>
            <div class="h5 fw-bold">{{ $connection['connected'] ? 'محفوظة ومشفّرة' : 'غير متصلة' }}</div>
            <div class="small text-secondary">آخر تحديث: {{ $connection['updated_at'] ?? '—' }}</div>
        </div>
    </div>
    <div class="col-sm-6">
        <div class="surface-card admin-panel p-4 h-100">
            <div class="small text-secondary mb-2">استعمالها في جلب السلات</div>
            <div class="h5 fw-bold {{ $connection['enabled'] ? 'text-success' : 'text-secondary' }}">
                {{ $connection['enabled'] ? 'مفعّل (تجريبي)' : 'متوقف' }}
            </div>
            <div class="small text-secondary">تسجيل الدخول والجلسة قد تنتهي وتحتاج تحديثًا.</div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-xl-6">
        <section class="surface-card admin-panel p-3 p-md-4 h-100">
            <h2 class="h5 fw-bold mb-3">1. تسجيل الدخول وحفظ الجلسة محليًا</h2>
            <p class="text-secondary small">على Mac، افتحي Terminal داخل مجلد سلتك وشغّلي الأمر التالي، ثم سجّلي دخولك بنفسك في النافذة التي ستظهر. بعد التأكد من تسجيل الدخول، ارجعي للـTerminal واضغطي Enter.</p>
            <div class="bg-light border rounded-3 p-3 mb-3 text-start" dir="ltr"><code>npm run shein:login</code></div>
            <div class="small mb-3">الملف يتخزن محليًا هنا (لا ترسليه لأي شخص):</div>
            <div class="bg-light border rounded-3 p-3 text-start text-break" dir="ltr"><code>storage/app/shein-session/session.json</code></div>
            <hr class="my-4">
            <h2 class="h5 fw-bold mb-3">2. رفع الجلسة مشفرة إلى سلتك أونلاين</h2>
            <form method="POST" action="{{ route('admin.shein-session.upload') }}" enctype="multipart/form-data" class="d-grid gap-3">
                @csrf
                <div><label for="sessionFile" class="form-label fw-bold">ملف Playwright Storage State</label>
                    <input id="sessionFile" type="file" name="storage_state" accept=".json,application/json" class="form-control" required></div>
                <div><label for="sessionPassword" class="form-label fw-bold">كلمة مرور مدير سلتك (ليست SHEIN)</label>
                    <input id="sessionPassword" type="password" name="admin_password" class="form-control" autocomplete="current-password" required></div>
                <button class="btn btn-primary" type="submit">حفظ الجلسة المشفّرة — بدون تفعيل</button>
            </form>
        </section>
    </div>

    <div class="col-xl-6">
        <section class="surface-card admin-panel p-3 p-md-4 h-100">
            <h2 class="h5 fw-bold mb-3">3. اختبار أسعار حسابك</h2>
            <p class="text-secondary small">بعد الرفع، جرّبي رابط سلة SHEIN معروفًا وافحصي هل السعر رجع نفسه اللي تشوفيه في حسابك على التطبيق. الاختبار لا ينشر سعرًا للزبائن.</p>
            @unless($connection['connected'])
                <div class="alert alert-warning py-2 small" role="alert">
                    الجلسة غير محفوظة بعد. ارفعي ملف <code>session.json</code> في الخطوة 2 أولًا. يمكنك الضغط على الاختبار الآن، لكن لن يبدأ جلب الأسعار قبل حفظ الجلسة.
                </div>
            @endunless
            <form method="POST" action="{{ route('admin.shein-session.test') }}" class="d-grid gap-3">
                @csrf
                <div>
                    <label for="testLink" class="form-label fw-bold">رابط سلة SHEIN (القصير أو الطويل)</label>
                    <textarea id="testLink" name="test_url" class="form-control ltr text-start @error('test_url') is-invalid @enderror" dir="ltr" rows="4" maxlength="2500"
                        placeholder="I found some great items at SHEIN!&#10;https://onelink.shein.com/56/..." required>{{ old('test_url') }}</textarea>
                    <div class="form-text">الصقي رابط onelink مباشرة أو رسالة المشاركة الكاملة؛ سلتك تستخرج الرابط تلقائيًا.</div>
                    @error('test_url')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <button class="btn btn-outline-primary" type="submit">اختبار الجلسة (بدون تفعيل للعملاء)</button>
            </form>
            @if(session()->has('shein_session_test_result'))
                @php($test = session('shein_session_test_result'))
                <div class="alert alert-info mt-3 mb-0">
                    <div>حالة الاستيراد: <strong>{{ $test['status'] }}</strong></div>
                    <div>عدد المنتجات: <strong>{{ $test['count'] }}</strong></div>
                    <div>سعر أول منتج: <strong>{{ $test['first_price_usd'] !== null ? number_format((float)$test['first_price_usd'],2).' $' : 'لم يتوفر' }}</strong></div>
                    <div>تطبيق الجلسة: <strong>{{ $test['account_session_applied'] ? 'نعم، تم إرسال الجلسة إلى المتصفح' : 'لا' }}</strong></div>
                    <div>دليل سعر الإمارات: <strong>{{ $test['ae_price_verified'] ? 'متوفر' : 'غير مؤكد' }}</strong></div>
                    <div class="small mt-2">إرسال الجلسة للمتصفح لا يثبت وحده استمرار تسجيل الدخول. قارني السعر يدويًا في حسابك قبل التفعيل.</div>
                </div>
            @endif
            <hr class="my-4">
            <h2 class="h5 fw-bold mb-3">4. التشغيل أو الإيقاف</h2>
            @if($connection['enabled'])
                <form method="POST" action="{{ route('admin.shein-session.disable') }}" class="mb-3">
                    @csrf
                    <button class="btn btn-danger-soft" type="submit">إيقاف استعمال الجلسة لجميع السلات</button>
                </form>
            @elseif($connection['connected'])
                <form method="POST" action="{{ route('admin.shein-session.enable') }}" class="d-grid gap-2 mb-3">
                    @csrf
                    <label class="small"><input type="checkbox" name="confirm" value="1" required> اختبرتُ نفس المنتج وتأكدتُ من سعر حساب الشراء، وفهمتُ أن السعر قد يتغير.</label>
                    <button class="btn btn-primary" type="submit">تفعيل استعمال الجلسة (تجريبي)</button>
                </form>
            @endif

            @if($connection['connected'])
                <form method="POST" action="{{ route('admin.shein-session.destroy') }}" class="d-grid gap-2 mt-4 border-top pt-3">
                    @csrf @method('DELETE')
                    <label for="deletePassword" class="form-label fw-bold">لحذف الجلسة نهائيًا، اكتبي كلمة مرور مدير سلتك</label>
                    <input id="deletePassword" type="password" class="form-control" name="admin_password" required>
                    <button class="btn btn-outline-danger" type="submit">حذف الجلسة المخزنة نهائيًا</button>
                </form>
            @endif
        </section>
    </div>
</div>
@endsection
