@extends('layouts.app')
@section('body')
<div class="admin-layout d-lg-flex">
    <aside class="offcanvas-lg offcanvas-start admin-sidebar" tabindex="-1" id="adminSidebar" aria-labelledby="adminSidebarLabel">
        <div class="offcanvas-header d-lg-none border-bottom border-light border-opacity-10">
            <div class="admin-sidebar-brand"><span class="brand-mark">س</span><div><h5 class="offcanvas-title text-white mb-0" id="adminSidebarLabel">إدارة سلتك</h5><small class="text-white-50">Salltak Backoffice</small></div></div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#adminSidebar" aria-label="إغلاق"></button>
        </div>
        <div class="offcanvas-body d-block p-3 p-lg-4">
            <div class="admin-sidebar-head mb-4 d-none d-lg-block"><div class="admin-sidebar-brand"><span class="brand-mark">س</span><div><div class="small text-white-50">لوحة التحكم</div><div class="fw-bold text-white fs-5">Salltak</div></div></div></div>
            <nav class="nav nav-pills flex-column gap-1 admin-nav" aria-label="قائمة الإدارة">
                <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}"><span class="nav-symbol">⌂</span><span>الرئيسية</span></a>
                <a class="nav-link {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}" href="{{ route('admin.orders.index') }}"><span class="nav-symbol">✓</span><span>الطلبات</span></a>
                <a class="nav-link {{ request()->routeIs('admin.carts.*') ? 'active' : '' }}" href="{{ route('admin.carts.index') }}"><span class="nav-symbol">▣</span><span>السلات</span></a>
                @if(auth()->user()->isAdmin())
                    <div class="admin-nav-label">إدارة النظام</div>
                    <a class="nav-link {{ request()->routeIs('admin.payment-methods.*') ? 'active' : '' }}" href="{{ route('admin.payment-methods.index') }}"><span class="nav-symbol">د</span><span>طرق الدفع</span></a>
                    <a class="nav-link {{ request()->routeIs('admin.deposit-rules.*') ? 'active' : '' }}" href="{{ route('admin.deposit-rules.index') }}"><span class="nav-symbol">%</span><span>قواعد العربون</span></a>
                    <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}"><span class="nav-symbol">◎</span><span>المستخدمون والمسؤولون</span></a>
                    <a class="nav-link {{ request()->routeIs('admin.stores.*') ? 'active' : '' }}" href="{{ route('admin.stores.index') }}"><span class="nav-symbol">◇</span><span>المواقع</span></a>
                    <a class="nav-link {{ request()->routeIs('admin.rates.*') ? 'active' : '' }}" href="{{ route('admin.rates.index') }}"><span class="nav-symbol">$</span><span>أسعار الصرف</span></a>
                    <a class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}" href="{{ route('admin.settings.edit') }}"><span class="nav-symbol">⚙</span><span>الإعدادات</span></a>
                @endif
            </nav>
            <div class="admin-help d-none d-lg-block"><div class="admin-help-icon">?</div><div class="fw-bold text-white mb-1">مسار طلب كامل</div><div class="small">مراجعة المنتجات، العربون، الدفعات، الشراء، الشحن والتسليم من مكان واحد.</div></div>
        </div>
    </aside>
    <section class="admin-main flex-grow-1 min-vh-100">
        <div class="admin-topbar d-flex align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-2"><button class="btn btn-ghost btn-sm d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#adminSidebar" aria-controls="adminSidebar">☰</button><div><div class="fw-bold text-dark">{{ auth()->user()->isAdmin() ? 'لوحة الإدارة' : 'إدارة الطلبات' }}</div><small class="text-secondary d-none d-sm-block">مرحبًا {{ auth()->user()->name }}</small></div></div>
            <div class="admin-search d-none d-md-block flex-grow-1">سلتك • إدارة الطلبات والمدفوعات</div>
            <div class="user-chip d-flex align-items-center gap-2"><span class="avatar-circle">{{ mb_substr(auth()->user()->name,0,1) }}</span><span class="small fw-semibold d-none d-sm-inline">{{ auth()->user()->name }}</span></div>
        </div>
        <div class="container-fluid admin-content p-3 p-md-4 p-xl-5">@yield('admin-content')</div>
    </section>
</div>
@endsection
