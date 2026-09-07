@extends('layouts.app')
@section('title', $seo['meta_title'] ?? 'سلتك')
@push('meta')
<meta name="description" content="{{ $seo['meta_description'] ?? '' }}">
<meta property="og:title" content="{{ $seo['og_title'] ?? ($seo['meta_title'] ?? 'سلتك') }}">
<meta property="og:description" content="{{ $seo['og_description'] ?? ($seo['meta_description'] ?? '') }}">
@if(!empty($seo['og_image']))<meta property="og:image" content="{{ asset('storage/'.$seo['og_image']) }}">@endif
@endpush
@section('body')
@if(!empty($isPreview))
<div class="preview-mode-bar"><x-icon name="eye"/> معاينة المسودة — هذه التعديلات غير ظاهرة للزوار حتى يتم النشر.</div>
@endif
@foreach($sections as $section)
    @if($section->slug !== 'seo' && $section->slug !== 'footer')
        @includeIf('site.sections.'.$section->slug, ['content'=>$section->content ?? [], 'section'=>$section])
    @endif
@endforeach
@endsection
