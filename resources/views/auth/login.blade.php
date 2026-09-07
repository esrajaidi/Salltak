@extends('layouts.app')
@section('title','تسجيل الدخول')
@section('body')
<section class="auth-page">
    <div class="container">
        <div class="auth-shell reveal is-visible">
            <aside class="auth-side"><span class="brand-mark">س</span><h2 class="h2">سلتك العالمية، مرتبة وسعرها واضح.</h2><p>ادخلي لحسابك وارجعي لكل السلات المحفوظة، راجعي USD وLYD وكمّلي من وين وقفتي.</p><div class="auth-benefit"><i>✓</i><span>سلاتك محفوظة في حسابك</span></div><div class="auth-benefit"><i>$</i><span>السعر بالدولار والدينار</span></div><div class="auth-benefit"><i>▦</i><span>مقاس ولون وصورة المنتج</span></div></aside>
            <div class="auth-card">
                <div class="d-flex align-items-center gap-3 mb-4"><div class="auth-brand-icon">س</div><div><div class="small text-primary fw-bold">سلتك — Salltak</div><h1 class="h3 fw-bold mb-0 mt-1">مرحبًا بعودتك</h1></div></div>
                <p class="text-secondary mb-4">سجّل الدخول للوصول إلى سلاتك المحفوظة ومراجعة الأسعار.</p>
                <form method="POST" action="{{ route('login.store') }}">@csrf
                    <div class="mb-3"><label class="form-label" for="login-email">البريد الإلكتروني</label><input id="login-email" class="form-control ltr @error('email') is-invalid @enderror" type="email" name="email" value="{{ old('email') }}" autocomplete="email" placeholder="name@example.com" required></div>
                    <div class="mb-3"><label class="form-label" for="login-password">كلمة المرور</label><input id="login-password" class="form-control ltr @error('password') is-invalid @enderror" type="password" name="password" autocomplete="current-password" required></div>
                    <div class="form-check mb-4"><input class="form-check-input" type="checkbox" name="remember" value="1" id="remember"><label class="form-check-label" for="remember">تذكرني</label></div>
                    <button class="btn btn-primary btn-lg w-100" type="submit">تسجيل الدخول</button>
                </form>
                <div class="text-center small text-secondary mt-4">ليس لديك حساب؟ <a class="fw-bold text-decoration-none text-primary" href="{{ route('register') }}">أنشئ حسابًا جديدًا</a></div>
            </div>
        </div>
    </div>
</section>
@endsection
