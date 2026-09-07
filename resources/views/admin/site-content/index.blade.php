@extends('layouts.admin')
@section('title','إدارة الموقع الخارجي')
@section('admin-content')
<div class="content-studio-head d-flex flex-column flex-xl-row align-items-xl-end justify-content-between gap-3 mb-4">
    <div><div class="page-kicker">استوديو المحتوى</div><h1 class="admin-page-title mb-2">إدارة الموقع الخارجي</h1><p class="text-secondary mb-0">عدّلي النصوص والصور وترتيب الأقسام واحفظيها كمسودة، وبعد المراجعة انشريها للزوار.</p></div>
    <div class="d-flex flex-wrap gap-2"><a class="btn btn-ghost icon-text-btn" target="_blank" href="{{ route('admin.site-content.preview') }}"><x-icon name="eye"/>معاينة المسودة</a><form method="POST" action="{{ route('admin.site-content.publish-all') }}" class="m-0 js-confirm-form" data-confirm="تأكيد النشر" data-confirm-title="نشر كل التعديلات؟" data-confirm-text="سيتم استبدال المحتوى المنشور بجميع المسودات الحالية.">@csrf<button class="btn btn-primary icon-text-btn"><x-icon name="publish"/>نشر كل التعديلات</button></form></div>
</div>
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3"><div class="cms-stat"><span class="cms-stat-icon"><x-icon name="content"/></span><div><small>الأقسام</small><strong>{{ $stats['total'] }}</strong></div></div></div>
    <div class="col-6 col-xl-3"><div class="cms-stat"><span class="cms-stat-icon"><x-icon name="eye"/></span><div><small>ظاهرة بالمسودة</small><strong>{{ $stats['visible'] }}</strong></div></div></div>
    <div class="col-6 col-xl-3"><div class="cms-stat"><span class="cms-stat-icon warning"><x-icon name="edit"/></span><div><small>تحتاج نشر</small><strong>{{ $stats['drafts'] }}</strong></div></div></div>
    <div class="col-6 col-xl-3"><div class="cms-stat"><span class="cms-stat-icon success"><x-icon name="publish"/></span><div><small>سبق نشرها</small><strong>{{ $stats['published'] }}</strong></div></div></div>
</div>
<div class="cms-grid">
@foreach($sections as $section)
    <article class="cms-section-card {{ $section->hasDraftChanges() ? 'has-draft' : '' }}">
        <div class="d-flex align-items-start justify-content-between gap-3"><div class="d-flex align-items-center gap-3"><span class="cms-section-number">{{ str_pad((string)$section->draft_sort_order,2,'0',STR_PAD_LEFT) }}</span><div><h2 class="h6 fw-bold mb-1">{{ $section->label }}</h2><div class="d-flex flex-wrap gap-2"><span class="status-chip {{ $section->draft_is_visible ? 'success' : 'muted' }}">{{ $section->draft_is_visible ? 'ظاهر' : 'مخفي' }}</span>@if($section->hasDraftChanges())<span class="status-chip warning">مسودة غير منشورة</span>@else<span class="status-chip">متزامن</span>@endif</div></div></div><x-icon name="content" size="24" class="text-secondary"/></div>
        <p class="small text-secondary mt-3 mb-3">آخر نشر: {{ $section->published_at?->format('Y-m-d H:i') ?? 'لم يُنشر بعد' }}</p>
        <div class="d-flex gap-2"><a class="btn btn-sm btn-navy flex-grow-1 icon-text-btn" href="{{ route('admin.site-content.edit',$section) }}"><x-icon name="edit"/>تعديل المحتوى</a>@if($section->hasDraftChanges())<form method="POST" action="{{ route('admin.site-content.publish',$section) }}" class="m-0 js-confirm-form" data-confirm="تأكيد النشر" data-confirm-title="نشر قسم {{ $section->label }}؟">@csrf<button class="btn btn-sm btn-primary icon-btn" title="نشر"><x-icon name="publish"/></button></form>@endif</div>
    </article>
@endforeach
</div>
@endsection
