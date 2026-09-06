@extends('layouts.admin')
@section('title','إعدادات النظام')
@section('admin-content')
<div class="mb-4"><div class="small text-primary fw-bold mb-1">تخصيص المنصة</div><h1 class="page-heading">إعدادات النظام</h1><p class="page-subtitle">عدّل اسم المنصة وبيانات التواصل ونص الصفحة الرئيسية.</p></div>

<div class="surface-card p-3 p-md-4 p-xl-5" style="max-width:900px">
    <form method="POST" action="{{ route('admin.settings.update') }}">
        @csrf @method('PUT')
        <div class="row g-3">
            <div class="col-12"><label class="form-label">اسم المنصة</label><input class="form-control" name="platform_name" value="{{ old('platform_name',$settings['platform_name']??'سلات ليبيا') }}"></div>
            <div class="col-md-6"><label class="form-label">الهاتف</label><input class="form-control ltr" name="contact_phone" value="{{ old('contact_phone',$settings['contact_phone']??'') }}"></div>
            <div class="col-md-6"><label class="form-label">البريد</label><input class="form-control ltr" type="email" name="contact_email" value="{{ old('contact_email',$settings['contact_email']??'') }}"></div>
            <div class="col-12"><label class="form-label">وصف الصفحة الرئيسية</label><textarea class="form-control" name="home_intro">{{ old('home_intro',$settings['home_intro']??'') }}</textarea></div>
        </div>
        <div class="d-flex justify-content-end mt-4"><button class="btn btn-primary px-4" type="submit">حفظ التغييرات</button></div>
    </form>
</div>
@endsection
