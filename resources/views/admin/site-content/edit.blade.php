@extends('layouts.admin')
@section('title','تعديل '.$siteSection->label)
@section('admin-content')
<div class="d-flex flex-column flex-xl-row align-items-xl-center justify-content-between gap-3 mb-4">
    <div><a href="{{ route('admin.site-content.index') }}" class="small text-decoration-none text-secondary">إدارة الموقع الخارجي /</a><h1 class="admin-page-title mb-1">{{ $siteSection->label }}</h1><p class="text-secondary mb-0">التعديل هنا مسودة فقط إلى أن تضغطي نشر.</p></div>
    <div class="d-flex gap-2"><a target="_blank" href="{{ route('admin.site-content.preview') }}" class="btn btn-ghost icon-text-btn"><x-icon name="eye"/>معاينة</a><form method="POST" action="{{ route('admin.site-content.publish',$siteSection) }}" class="m-0 js-confirm-form" data-confirm="تأكيد النشر" data-confirm-title="نشر هذا القسم الآن؟">@csrf<button class="btn btn-primary icon-text-btn"><x-icon name="publish"/>نشر القسم</button></form></div>
</div>
<form method="POST" action="{{ route('admin.site-content.update',$siteSection) }}" enctype="multipart/form-data" class="row g-4">@csrf @method('PUT')
<div class="col-xl-8">
<div class="admin-panel p-3 p-md-4">
    <div class="cms-form-head"><span><x-icon name="edit"/></span><div><strong>المحتوى</strong><small>اكتبي المحتوى بالعربي، والصور اختيارية ويمكن تغييرها في أي وقت.</small></div></div>
    @if(in_array($siteSection->slug,['hero','how_it_works','stores','showcase','features','payments','testimonials','faq','cta']))
    <div class="row g-3 mt-1">
        <div class="col-md-4"><label class="form-label">العنوان الصغير</label><input class="form-control" name="kicker" value="{{ old('kicker',$content['kicker']??'') }}"></div>
        <div class="col-md-8"><label class="form-label">العنوان الرئيسي</label><input class="form-control" name="title" value="{{ old('title',$content['title']??'') }}"></div>
        @if($siteSection->slug==='hero')<div class="col-12"><label class="form-label">الجملة المميزة</label><input class="form-control" name="accent" value="{{ old('accent',$content['accent']??'') }}"></div>@endif
        @if(in_array($siteSection->slug,['how_it_works','stores','features','payments','testimonials','faq']))<div class="col-12"><label class="form-label">الوصف المختصر</label><textarea class="form-control" rows="2" name="subtitle">{{ old('subtitle',$content['subtitle']??'') }}</textarea></div>@endif
        @if(in_array($siteSection->slug,['hero','showcase','cta']))<div class="col-12"><label class="form-label">الوصف</label><textarea class="form-control" rows="4" name="description">{{ old('description',$content['description']??'') }}</textarea></div>@endif
    </div>
    @endif

    @if($siteSection->slug==='hero')
        <div class="cms-subsection"><h3>الأزرار</h3><div class="row g-3"><div class="col-md-6"><label class="form-label">زر أساسي</label><input class="form-control" name="primary_button_text" value="{{ old('primary_button_text',$content['primary_button_text']??'') }}"></div><div class="col-md-6"><label class="form-label">رابط الزر</label><input class="form-control" name="primary_button_url" value="{{ old('primary_button_url',$content['primary_button_url']??'') }}"></div><div class="col-md-6"><label class="form-label">زر ثانوي</label><input class="form-control" name="secondary_button_text" value="{{ old('secondary_button_text',$content['secondary_button_text']??'') }}"></div><div class="col-md-6"><label class="form-label">رابط الزر الثانوي</label><input class="form-control" name="secondary_button_url" value="{{ old('secondary_button_url',$content['secondary_button_url']??'') }}"></div></div></div>
        @include('admin.site-content.partials.string-list',['field'=>'badges','label'=>'نقاط الثقة','values'=>$content['badges']??[],'max'=>8])
    @endif

    @if($siteSection->slug==='showcase') @include('admin.site-content.partials.string-list',['field'=>'bullets','label'=>'النقاط المميزة','values'=>$content['bullets']??[],'max'=>8]) @endif

    @if(in_array($siteSection->slug,['how_it_works','features']))
        @include('admin.site-content.partials.items',['mode'=>'feature','items'=>$content['items']??[],'iconOptions'=>$iconOptions])
    @elseif($siteSection->slug==='testimonials')
        @include('admin.site-content.partials.items',['mode'=>'testimonial','items'=>$content['items']??[],'iconOptions'=>$iconOptions])
    @elseif($siteSection->slug==='faq')
        @include('admin.site-content.partials.items',['mode'=>'faq','items'=>$content['items']??[],'iconOptions'=>$iconOptions])
    @endif

    @if($siteSection->slug==='cta')<div class="cms-subsection"><h3>زر الدعوة</h3><div class="row g-3"><div class="col-md-6"><label class="form-label">نص الزر</label><input class="form-control" name="button_text" value="{{ old('button_text',$content['button_text']??'') }}"></div><div class="col-md-6"><label class="form-label">الرابط</label><input class="form-control" name="button_url" value="{{ old('button_url',$content['button_url']??'') }}"></div></div></div>@endif

    @if($siteSection->slug==='footer')
        <div class="row g-3"><div class="col-12"><label class="form-label">الشعار النصي</label><input class="form-control" name="slogan" value="{{ old('slogan',$content['slogan']??'') }}"></div><div class="col-12"><label class="form-label">الوصف</label><textarea class="form-control" rows="3" name="description">{{ old('description',$content['description']??'') }}</textarea></div><div class="col-md-6"><label class="form-label">الهاتف</label><input class="form-control" name="phone" value="{{ old('phone',$content['phone']??'') }}"></div><div class="col-md-6"><label class="form-label">البريد</label><input type="email" class="form-control" name="email" value="{{ old('email',$content['email']??'') }}"></div><div class="col-md-6"><label class="form-label">واتساب</label><input class="form-control" name="whatsapp" value="{{ old('whatsapp',$content['whatsapp']??'') }}"></div><div class="col-md-6"><label class="form-label">العنوان</label><input class="form-control" name="address" value="{{ old('address',$content['address']??'') }}"></div></div>
    @endif

    @if($siteSection->slug==='seo')
        <div class="row g-3"><div class="col-12"><label class="form-label">عنوان الصفحة لمحركات البحث</label><input class="form-control" name="meta_title" value="{{ old('meta_title',$content['meta_title']??'') }}"></div><div class="col-12"><label class="form-label">وصف الصفحة</label><textarea class="form-control" rows="3" name="meta_description">{{ old('meta_description',$content['meta_description']??'') }}</textarea></div><div class="col-md-6"><label class="form-label">عنوان المشاركة</label><input class="form-control" name="og_title" value="{{ old('og_title',$content['og_title']??'') }}"></div><div class="col-md-6"><label class="form-label">وصف المشاركة</label><input class="form-control" name="og_description" value="{{ old('og_description',$content['og_description']??'') }}"></div></div>
    @endif

    @php
    $imageFields = match ($siteSection->slug) {
        'hero', 'showcase' => ['image_desktop'=>'صورة الكمبيوتر','image_mobile'=>'صورة الهاتف'],
        'testimonials', 'cta' => ['image'=>'صورة القسم'],
        'seo' => ['og_image'=>'صورة المشاركة'],
        default => [],
    };
@endphp
    @if($imageFields)
    <div class="cms-subsection"><h3>الصور</h3><div class="row g-3">@foreach($imageFields as $field=>$label)<div class="col-md-6"><label class="form-label">{{ $label }}</label>@if(!empty($content[$field]))<div class="cms-image-preview mb-2"><img src="{{ asset('storage/'.$content[$field]) }}" alt="{{ $label }}"></div>@endif<input class="form-control" type="file" accept="image/png,image/jpeg,image/webp" name="{{ $field }}"><small class="text-secondary">JPG / PNG / WebP — حتى 8MB</small></div>@endforeach</div></div>
    @endif
</div>
</div>
<div class="col-xl-4">
<div class="admin-panel p-3 p-md-4 sticky-xl-top" style="top:92px">
    <div class="cms-form-head"><span><x-icon name="settings"/></span><div><strong>إعدادات القسم</strong><small>الترتيب والإظهار يطبقوا وقت النشر.</small></div></div>
    <div class="mt-4"><label class="form-label">ترتيب الظهور</label><input type="number" min="0" max="9999" class="form-control" name="sort_order" value="{{ old('sort_order',$siteSection->draft_sort_order) }}"></div>
    <label class="cms-toggle mt-3"><input type="checkbox" name="is_visible" value="1" @checked(old('is_visible',$siteSection->draft_is_visible))><span><strong>إظهار هذا القسم</strong><small>لو وقفتيه ما يظهرش للزوار بعد النشر.</small></span></label>
    <div class="draft-status mt-3"><x-icon name="{{ $siteSection->hasDraftChanges() ? 'edit' : 'check' }}"/><div><strong>{{ $siteSection->hasDraftChanges() ? 'عندك مسودة غير منشورة' : 'المحتوى متزامن' }}</strong><small>آخر نشر: {{ $siteSection->published_at?->format('Y-m-d H:i') ?? 'لم ينشر' }}</small></div></div>
    <button class="btn btn-navy w-100 mt-4 icon-text-btn" type="submit"><x-icon name="save"/>حفظ كمسودة</button>
</div>
</div>
</form>
@endsection
