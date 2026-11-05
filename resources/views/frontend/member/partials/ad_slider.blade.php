@php
    $placement = $placement ?? 'top';
    $slides = \App\Support\MemberAdSliders::slides($placement);
    $isTop = $placement === 'top';
@endphp
@if(count($slides))
<section class="hm-ad-slider hm-ad-slider--{{ $placement }}" data-hm-ad-slider data-interval="6500"
    aria-label="{{ $isTop?'Matchmaking promotions':'Sponsored promotions' }}">
    <div class="hm-ad-slider-track">
        @foreach($slides as $index=>$ad)
        <article class="hm-ad-slide {{ $loop->first?'is-active':'' }}" data-hm-slide
            aria-hidden="{{ $loop->first?'false':'true' }}">
            <img class="hm-ad-image" src="{{ $ad['image_url'] }}" alt=""
                 loading="{{ $loop->first?'eager':'lazy' }}"
                 style="object-position:{{ $ad['object_x'] }}% {{ $ad['object_y'] }}%">
            <div class="hm-ad-overlay"></div>
            <div class="hm-ad-content">
                @if(filled($ad['eyebrow']??''))<span class="hm-ad-eyebrow">{{ $ad['eyebrow'] }}</span>@endif
                <h2>{{ $ad['title'] }}@if(filled($ad['highlight']??'')) <em>{{ $ad['highlight'] }}</em>@endif</h2>
                @if(filled($ad['subtitle']??''))<p>{{ $ad['subtitle'] }}</p>@endif
                @if(filled($ad['button_label']??'') && filled($ad['button_url']??''))
                    <a class="hm-ad-cta" href="{{ $ad['button_url'] }}">{{ $ad['button_label'] }} <i class="las la-arrow-right" aria-hidden="true"></i></a>
                @endif
            </div>
            @if($isTop)
            <div class="hm-ad-benefits" aria-label="Member matchmaking features">
                <span><i class="las la-magic" aria-hidden="true"></i> AI Matchmaking</span>
                <span><i class="las la-shield-alt" aria-hidden="true"></i> Verified Profiles</span>
                <span><i class="las la-heart" aria-hidden="true"></i> Smart Suggestions</span>
                <span><i class="las la-certificate" aria-hidden="true"></i> Higher Compatibility</span>
            </div>
            @else
                <span class="hm-ad-sponsored"><i class="las la-bullhorn" aria-hidden="true"></i> Sponsored</span>
            @endif
        </article>
        @endforeach
    </div>
    @if(count($slides)>1)
      <button class="hm-ad-nav hm-ad-prev" type="button" data-hm-ad-prev aria-label="Previous promotion"><i class="las la-angle-left"></i></button>
      <button class="hm-ad-nav hm-ad-next" type="button" data-hm-ad-next aria-label="Next promotion"><i class="las la-angle-right"></i></button>
      <div class="hm-ad-dots" role="group" aria-label="Select promotion">
      @foreach($slides as $index=>$ad)
        <button type="button" class="{{ $loop->first?'is-active':'' }}" data-hm-ad-dot="{{ $index }}"
                aria-label="Show promotion {{ $loop->iteration }}" aria-current="{{ $loop->first?'true':'false' }}"></button>
      @endforeach
      </div>
    @endif
</section>
@endif
