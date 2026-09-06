@extends('layouts.app')
@section('body')
<div class="admin-layout d-lg-flex">
    <aside class="offcanvas-lg offcanvas-start admin-sidebar" tabindex="-1" id="adminSidebar" aria-labelledby="adminSidebarLabel">
        <div class="offcanvas-header d-lg-none border-bottom border-light border-opacity-10">
            <h5 class="offcanvas-title text-white" id="adminSidebarLabel">إدارة سلتك</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#adminSidebar" aria-label="إغلاق"></button>
        </div>
        <div class="offcanvas-body d-block p-3 p-lg-4">
            <div class="admin-sidebar-head mb-4 d-none d-lg-flex align-items-center gap-3"><span class="brand-mark">س</span><div><div class="small text-white-50">لوحة التحكم</div><div class="fw-bold text-white fs-5">Salltak</div></div></div>
            <nav class="nav nav-pills flex-column gap-1 admin-nav">
                <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}"><span class="nav-symbol">01</span> الرئيسية</a>
                <a class="nav-link {{ request()->routeIs('admin.carts.*') ? 'active' : '' }}" href="{{ route('admin.carts.index') }}"><span class="nav-symbol">02</span> السلات</a>
                <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}"><span class="nav-symbol">03</span> المستخدمون</a>
                <a class="nav-link {{ request()->routeIs('admin.stores.*') ? 'active' : '' }}" href="{{ route('admin.stores.index') }}"><span class="nav-symbol">04</span> المواقع</a>
                <a class="nav-link {{ request()->routeIs('admin.rates.*') ? 'active' : '' }}" href="{{ route('admin.rates.index') }}"><span class="nav-symbol">05</span> أسعار الصرف</a>
                <a class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}" href="{{ route('admin.settings.edit') }}"><span class="nav-symbol">06</span> الإعدادات</a>
            </nav>
        </div>
    </aside>
    <section class="admin-main flex-grow-1 min-vh-100">
        <div class="admin-mobile-bar d-lg-none px-3 py-2 border-bottom bg-white sticky-top d-flex align-items-center justify-content-between gap-2"><strong>لوحة الإدارة</strong><button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="offcanvas" data-bs-target="#adminSidebar" aria-controls="adminSidebar">القائمة ☰</button></div>
        <div class="container-fluid admin-content p-3 p-md-4 p-xl-5">@yield('admin-content')</div>
    </section>
</div>
@endsection
