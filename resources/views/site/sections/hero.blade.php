@php
$primaryUrl = $content['primary_button_url'] ?? '/my-carts/new';
$secondaryUrl = $content['secondary_button_url'] ?? '#how-it-works';
$desktopImage = $content['image_desktop'] ?? null;
$mobileImage = $content['image_mobile'] ?? null;
@endphp
<section class="marketing-hero">
    <span class="marketing-orb orb-a"></span><span class="marketing-orb orb-b"></span><span class="marketing-grid"></span>
    <div class="container position-relative">
        <div class="row align-items-center g-5">
            <div class="col-lg-6 reveal is-visible">
                <span class="marketing-kicker"><x-icon name="activity" size="17"/> {{ $content['kicker'] ?? '' }}</span>
                <h1 class="marketing-title">{{ $content['title'] ?? '' }}</h1>
                @if(!empty($content['accent']))<div class="marketing-accent">{{ $content['accent'] }}</div>@endif
                <p class="marketing-copy">{{ $content['description'] ?? '' }}</p>
                <div class="d-flex flex-column flex-sm-row gap-2 mt-4">
                    @auth
                        <a class="btn btn-primary btn-lg px-4" href="{{ in_array(auth()->user()->role,['admin','order_manager'],true) ? route('admin.dashboard') : url($primaryUrl) }}">{{ in_array(auth()->user()->role,['admin','order_manager'],true) ? 'افتح لوحة الإدارة' : ($content['primary_button_text'] ?? 'ابدئي الآن') }} <x-icon name="arrow-left" size="18"/></a>
                    @else
                        <a class="btn btn-primary btn-lg px-4" href="{{ url($primaryUrl) }}">{{ $content['primary_button_text'] ?? 'ابدئي الآن' }} <x-icon name="arrow-left" size="18"/></a>
                    @endauth
                    <a class="btn btn-marketing-outline btn-lg px-4" href="{{ $secondaryUrl }}">{{ $content['secondary_button_text'] ?? 'كيف تعمل؟' }}</a>
                </div>
                @if(!empty($content['badges']))<div class="marketing-trust mt-4">@foreach($content['badges'] as $badge)<span><i></i>{{ $badge }}</span>@endforeach</div>@endif
            </div>
            <div class="col-lg-6 reveal reveal-delay-1 is-visible">
                <div class="marketing-device-stage">
                    @if($desktopImage || $mobileImage)
                        <picture class="marketing-main-picture">@if($mobileImage)<source media="(max-width: 575px)" srcset="{{ asset('storage/'.$mobileImage) }}">@endif<img src="{{ asset('storage/'.($desktopImage ?: $mobileImage)) }}" alt="معاينة منصة سلتك"></picture>
                    @else
                        <div class="marketing-browser">
                            <div class="browser-bar"><i></i><i></i><i></i><span>سلتك — متابعة الطلب</span></div>
                            <div class="marketing-browser-body"><div class="marketing-sidebar"><span class="mini-brand">س</span>@foreach(['الرئيسية','طلباتي','سلاتي','الدفع'] as $nav)<b>{{ $nav }}</b>@endforeach</div><div class="marketing-screen"><div class="mini-stat-row"><span><small>إجمالي الطلب</small><strong>1,240 د.ل</strong></span><span><small>العربون</small><strong>400 د.ل</strong></span><span><small>الحالة</small><strong>قيد المراجعة</strong></span></div><div class="mini-order-row"><i></i><div><strong>منتج SHEIN</strong><small>أسود / XL</small></div><b>$9.85</b></div><div class="mini-order-row"><i></i><div><strong>منتج SHEIN</strong><small>وردي / L</small></div><b>$18.40</b></div><div class="mini-progress"><span style="width:74%"></span></div></div></div>
                        </div>
                        <div class="marketing-phone"><div class="phone-notch"></div><strong>سلتك</strong><div class="phone-status-card"><small>طلبك الآن</small><b>تم اعتماد العربون</b></div><div class="phone-status-card"><small>المتبقي</small><b>840 د.ل</b></div><div class="phone-timeline"><i></i><i></i><i></i></div></div>
                    @endif
                    <span class="float-label fl-one">SHEIN</span><span class="float-label fl-two">$ USD</span><span class="float-label fl-three">د.ل LYD</span>
                </div>
            </div>
        </div>
    </div>
</section>
