@extends('layouts.admin')
@section('title','المستخدمون')
@section('admin-content')
<div class="mb-4"><div class="small text-primary fw-bold mb-1">إدارة الحسابات</div><h1 class="page-heading">المستخدمون</h1><p class="page-subtitle">البحث في حسابات العملاء والتحكم في حالة الحساب.</p></div>

<div class="surface-card p-3 p-md-4 mb-4">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-7 col-xl-5"><label class="form-label">بحث</label><input class="form-control" name="q" value="{{ request('q') }}" placeholder="الاسم / البريد / الهاتف"></div>
        <div class="col-md-auto"><button class="btn btn-primary w-100" type="submit">بحث</button></div>
        @if(request()->filled('q'))<div class="col-md-auto"><a class="btn btn-outline-secondary w-100" href="{{ route('admin.users.index') }}">مسح</a></div>@endif
    </form>
</div>

<div class="surface-card overflow-hidden"><div class="table-responsive"><table class="table table-modern"><thead><tr><th>الاسم</th><th>البريد</th><th>السلات</th><th>الحالة</th><th></th></tr></thead><tbody>@forelse($users as $user)<tr><td><div class="d-flex align-items-center gap-2"><span class="avatar-circle border">{{ mb_substr($user->name,0,1) }}</span><span class="fw-semibold">{{ $user->name }}</span></div></td><td class="ltr">{{ $user->email }}</td><td>{{ $user->carts_count }}</td><td><span class="status-badge {{ $user->is_active?'status-success':'status-danger' }}">{{ $user->is_active?'فعال':'موقوف' }}</span></td><td><form method="POST" action="{{ route('admin.users.toggle',$user) }}">@csrf @method('PATCH')<button class="btn btn-soft btn-sm" type="submit">تبديل الحالة</button></form></td></tr>@empty<tr><td colspan="5" class="text-center text-secondary py-5">لا توجد نتائج.</td></tr>@endforelse</tbody></table></div></div>
<div class="mt-4">{{ $users->links() }}</div>
@endsection
