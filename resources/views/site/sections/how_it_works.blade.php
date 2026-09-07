<section id="how-it-works" class="marketing-section">
<div class="container"><div class="section-heading text-center reveal"><span class="page-kicker">{{ $content['kicker'] ?? '' }}</span><h2>{{ $content['title'] ?? '' }}</h2><p>{{ $content['subtitle'] ?? '' }}</p></div>
<div class="row g-3 g-lg-4 mt-1">@foreach($content['items'] ?? [] as $index=>$item)<div class="col-sm-6 col-xl-3 reveal"><article class="journey-card"><span class="journey-number">{{ str_pad($index+1,2,'0',STR_PAD_LEFT) }}</span><div class="journey-icon"><x-icon name="{{ $item['icon'] ?? 'activity' }}" size="27"/></div><h3>{{ $item['title'] ?? '' }}</h3><p>{{ $item['description'] ?? '' }}</p></article></div>@endforeach</div></div>
</section>
