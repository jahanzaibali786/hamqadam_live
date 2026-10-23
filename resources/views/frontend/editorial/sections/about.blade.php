@php($hero=$data['hero'])
<section class="hqed-about-hero"><div class="hqed-wrap hqed-about-grid"><div><span class="hqed-eyebrow">{{ $hero['eyebrow'] }}</span><h1>{{ $hero['title'] }}</h1><p class="hqed-lede">{{ $hero['subtitle'] }}</p><p>{{ $hero['description'] }}</p><div class="hqed-actions"><a class="hqed-btn" href="{{ url($hero['primary_link']) }}">{{ $hero['primary_text'] }} <i class="las la-arrow-right"></i></a><a class="hqed-btn hqed-btn-light" href="{{ $hero['secondary_link'] }}">{{ $hero['secondary_text'] }} <i class="las la-arrow-right"></i></a></div><div class="hqed-about-stats">
@foreach($hero['stats'] as $stat)<div><strong>{{ $stat['value'] }}</strong><small>{{ $stat['label'] }}</small></div>@endforeach
</div></div><div class="hqed-about-photo"><img src="{{ !empty($hero['image']) && uploaded_asset($hero['image'])?uploaded_asset($hero['image']):static_asset($hero['image_fallback']) }}" alt="Hamqadam matrimonial celebration"><div class="hqed-floating"><strong>{{ $hero['image_badge_title'] }}</strong><small>{{ $hero['image_badge_subtitle'] }}</small></div><div class="hqed-photo-caption"><strong>{{ $hero['image_caption_title'] }}</strong><p>{{ $hero['image_caption_subtitle'] }}</p><small>{{ $hero['image_caption_tag'] }}</small></div></div></div></section>
<section class="hqed-section" id="purpose"><div class="hqed-wrap"><header class="hqed-centered"><span class="hqed-eyebrow">{{ $data['purpose']['eyebrow'] }}</span><h2>{{ $data['purpose']['title'] }}</h2><p>{{ $data['purpose']['description'] }}</p></header><div class="hqed-grid2 hqed-comparison">
@foreach($data['purpose']['compare'] as $i=>$block)<article class="hqed-card {{ $i ? 'hqed-pink':'' }}"><span class="hqed-eyebrow">{{ $block['eyebrow'] }}</span><h3>{{ $block['title'] }}</h3><ul>
@foreach($block['points'] as $point)<li><i class="las {{ $i?'la-check-circle':'la-times-circle' }}"></i>{{ $point }}</li>@endforeach
</ul></article>@endforeach
</div><div class="hqed-grid4 hqed-feature-grid">
@foreach($data['purpose']['features'] as $feature)<article class="hqed-card"><span class="hqed-icon"><i class="las la-{{ $feature['icon'] }}"></i></span><h3>{{ $feature['title'] }}</h3><p>{{ $feature['description'] }}</p></article>@endforeach
</div></div></section>
<section class="hqed-origin" id="our-origin"><div class="hqed-wrap hqed-origin-grid"><img src="{{ !empty($data['origin']['image']) && uploaded_asset($data['origin']['image'])?uploaded_asset($data['origin']['image']):static_asset($data['origin']['image_fallback']) }}" alt="Hamqadam founding council" loading="lazy"><div><span class="hqed-eyebrow">{{ $data['origin']['eyebrow'] }}</span><h2>{{ $data['origin']['title'] }}</h2><p>{{ $data['origin']['description'] }}</p><strong>{{ $data['origin']['attribution'] }}</strong><small>{{ $data['origin']['attribution_detail'] }}</small></div></div></section>
<section class="hqed-section"><div class="hqed-wrap"><header class="hqed-centered"><span class="hqed-eyebrow">{{ $data['philosophy']['eyebrow'] }}</span><h2>{{ $data['philosophy']['title'] }}</h2><p>{{ $data['philosophy']['description'] }}</p></header><div class="hqed-grid2">
@foreach($data['philosophy']['cards'] as $card)<article class="hqed-card hqed-tinted"><div class="hqed-card-top"><b>{{ $card['number'] }}</b><small>{{ $card['tag'] }}</small></div><h3>{{ $card['title'] }}</h3><p>{{ $card['description'] }}</p><div class="hqed-footnote">{{ $card['footnote'] }}</div></article>@endforeach
</div></div></section>
<section class="hqed-verify" id="verification"><div class="hqed-wrap"><div class="hqed-section-row"><div><span class="hqed-eyebrow">{{ $data['verification']['eyebrow'] }}</span><h2>{{ $data['verification']['title'] }}</h2></div><p>{{ $data['verification']['description'] }}</p></div><div class="hqed-grid4">
@foreach($data['verification']['steps'] as $step)<article class="hqed-card"><small class="hqed-eyebrow">{{ $step['number'] }}</small><h3>{{ $step['title'] }}</h3><p>{{ $step['description'] }}</p><div class="hqed-footnote">{{ $step['tag'] }}</div></article>@endforeach
</div></div></section>
<section class="hqed-section"><div class="hqed-wrap"><header class="hqed-centered"><span class="hqed-eyebrow">{{ $data['beliefs']['eyebrow'] }}</span><h2>{{ $data['beliefs']['title'] }}</h2><p>{{ $data['beliefs']['description'] }}</p></header><div class="hqed-grid3">
@foreach($data['beliefs']['items'] as $belief)<article class="hqed-card hqed-tinted"><span class="hqed-icon"><i class="las la-{{ $belief['icon'] }}"></i></span><h3>{{ $belief['title'] }}</h3><p>{{ $belief['description'] }}</p>
@if(!empty($belief['tag']))<small class="hqed-footnote">{{ $belief['tag'] }}</small>@endif
</article>@endforeach
</div></div></section>
<div class="hqed-trust-strip"><div class="hqed-wrap hqed-grid3">
@foreach($data['trust_strip'] as $trust)<div class="hqed-trust-item"><span class="hqed-icon"><i class="las la-{{ $trust['icon'] }}"></i></span><div><strong>{{ $trust['value'] }}</strong><small>{{ $trust['detail'] }}</small></div></div>@endforeach
</div></div>
@include('frontend.editorial.sections.cta',['cta'=>$data['cta'],'variant'=>'wide'])