<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#24135f">
    <title>@yield('title', 'سلات ليبيا')</title>
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @stack('styles')
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark app-navbar sticky-top" aria-label="التنقل الرئيسي">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="{{ route('home') }}">
            <span class="brand-mark" aria-hidden="true">س</span>
            <span>سلتك <span class="brand-accent">Salltak</span></span>
        </a>

        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="فتح القائمة">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-lg-4 mb-2 mb-lg-0 align-items-lg-center gap-lg-1">
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">الرئيسية</a></li>
                @auth
                    @if(auth()->user()->isAdmin())
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.*') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">لوحة الإدارة</a></li>
                    @else
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('carts.index','carts.show') ? 'active' : '' }}" href="{{ route('carts.index') }}">سلاتي</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('carts.create','carts.preview') ? 'active' : '' }}" href="{{ route('carts.create') }}">سلة جديدة</a></li>
                    @endif
                @endauth
            </ul>

            <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-2 me-lg-auto pt-2 pt-lg-0">
                @auth
                    <div class="user-chip d-flex align-items-center gap-2">
                        <span class="avatar-circle">{{ mb_substr(auth()->user()->name, 0, 1) }}</span>
                        <span class="small fw-semibold">{{ auth()->user()->name }}</span>
                    </div>
                    <form method="POST" action="{{ route('logout') }}" class="m-0">
                        @csrf
                        <button class="btn btn-light btn-sm px-3 w-100" type="submit">تسجيل الخروج</button>
                    </form>
                @else
                    <a class="btn btn-link text-white text-decoration-none" href="{{ route('login') }}">تسجيل الدخول</a>
                    <a class="btn btn-light btn-sm px-3" href="{{ route('register') }}">إنشاء حساب</a>
                @endauth
            </div>
        </div>
    </div>
</nav>

@if(session('success') || $errors->any())
    <div class="container pt-3">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
                <strong>تم بنجاح.</strong> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
            </div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
                <strong>يرجى مراجعة البيانات:</strong>
                <ul class="mb-0 mt-2 pe-3">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
            </div>
        @endif
    </div>
@endif

<main>@yield('body')</main>

<footer class="app-footer mt-auto">
    <div class="container py-4 d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
        <div class="fw-semibold">سلتك — Salltak</div>
        <div class="small text-secondary">منصة حفظ وتحويل سلات التسوق إلى الدينار الليبي</div>
    </div>
</footer>

<script src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
@stack('scripts')
</body>
</html>
