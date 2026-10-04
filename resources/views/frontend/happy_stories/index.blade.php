@extends('frontend.layouts.app')
@section('content')
<section class="hq-reference-page-hero hq-stories-reference-hero text-center">
    <div class="container">
        <!-- <div class="hq-reference-breadcrumb text-left">{{ translate('Home') }} <span>/</span> {{ translate('Happy Stories') }}</div> -->
        <span class="hq-reference-eyebrow"><i class="las la-heart"></i> {{ translate('Sacred Unions & Auspicious Beginnings') }}</span>
        <h1>{{ translate('Real Happy Stories of Lifetime Companionship')}}</h1>
        <p>{{ translate('Read how thousands of respectful families and couples found their destined match through Hamqadam’s verified, dignified matrimonial sanctuary.') }}</p>
        <div class="hq-story-stat-row">
            <span><i class="las la-heart"></i><strong>12,000+</strong> {{ translate('Happy Sacred Marriages') }}</span>
            <span><i class="las la-certificate"></i><strong>100%</strong> {{ translate('CNIC Verified Profiles') }}</span>
            <span><i class="las la-shield-alt"></i><strong>99.2%</strong> {{ translate('Family Trust & Respect') }}</span>
        </div>
        <div class="hq-story-filter-row">
            <span class="active">{{ translate('All Stories') }}</span><span>{{ translate('Recent Nikah') }}</span><span>{{ translate('Overseas & Intercity') }}</span><span>{{ translate('Doctors & Engineers') }}</span><span>{{ translate('Organized by Families') }}</span>
        </div>
    </div>
</section>

<section class="hq-story-page hq-reference-story-page hq-design-section">
    <div class="container">
        @php($featuredStory = $happy_stories->first())
        @if($featuredStory)
            @php($featuredPhotos = array_values(array_filter(explode(',', (string)$featuredStory->photos))))
            <article class="hq-feature-story">
                <a class="hq-feature-story-media" href="{{ route('story_details', $featuredStory->id) }}">
                    <img src="{{ !empty($featuredPhotos[0]) ? uploaded_asset($featuredPhotos[0]) : static_asset('assets/img/placeholder.jpg') }}" alt="{{ $featuredStory->title }}">
                </a>
                <div class="hq-feature-story-copy">
                    <span class="badge badge-soft-success"><i class="las la-certificate mr-1"></i>{{ translate('Both Profiles CNIC Verified') }}</span>
                    <div class="text-warning mb-2">★★★★★</div>
                    <blockquote>“{{ \Illuminate\Support\Str::limit(strip_tags((string)$featuredStory->details), 260) }}”</blockquote>
                    <h3 class="h5 mb-1 text-primary">{{ $featuredStory->title }}</h3>
                    <p class="text-muted fs-11 mb-3">{{ translate('A verified Hamqadam success story shared with the community.') }}</p>
                    <a href="{{ route('story_details', $featuredStory->id) }}" class="btn btn-primary btn-sm">{{ translate('Read Full Journey') }} <i class="las la-arrow-right ml-1"></i></a>
                </div>
            </article>
        @endif

        <div class="hq-design-section-title text-left mx-0 mb-4">
            <span class="eyebrow">{{ translate('Destined Companions') }}</span>
            <h2>{{ translate('Verified Journey Chronicles') }}</h2>
            <p>{{ translate('Every match is anchored in mutual respect, familial honor, and verified backgrounds across Pakistan and overseas diaspora.') }}</p>
        </div>

        <div class="hq-story-grid">
            @foreach ($happy_stories as $happy_story)
                @if($featuredStory && $happy_story->id === $featuredStory->id) @continue @endif
                @php($photo = array_values(array_filter(explode(',', (string)$happy_story->photos))))
                <article class="card hq-story-card shadow-none">
                    <a href="{{ route('story_details', $happy_story->id) }}" class="text-reset d-block">
                        <img src="{{ !empty($photo[0]) ? uploaded_asset($photo[0]) : static_asset('assets/img/placeholder.jpg') }}" class="img-fluid" alt="{{ $happy_story->title }}">
                    </a>
                    <div class="p-3">
                        <span class="hq-story-card-badge"><i class="las la-shield-alt"></i> {{ translate('Verified Journey') }}</span>
                        <h2 class="h5 mt-2 mb-2"><a href="{{ route('story_details', $happy_story->id) }}" class="text-dark">{{ $happy_story->title }}</a></h2>
                        <p class="text-muted fs-10 mb-3">{{ \Illuminate\Support\Str::limit(strip_tags((string)$happy_story->details), 135) }}</p>
                        <div class="d-flex justify-content-between align-items-center fs-9 text-muted">
                            <span><i class="las la-calendar mr-1"></i>{{ $happy_story->created_at->format('M Y') }}</span>
                            <a href="{{ route('story_details', $happy_story->id) }}" class="text-primary fw-600">{{ translate('View Story') }} <i class="las la-arrow-right"></i></a>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="aiz-pagination aiz-pagination-center mt-4">{{ $happy_stories->appends(request()->input())->links() }}</div>

        <div class="hq-story-success-cta">
            <div>
                <span class="hq-reference-eyebrow"><i class="las la-gift"></i> {{ translate('Auspicious Gift for Newlyweds') }}</span>
                <h2>{{ translate('Found Your Soulmate on Hamqadam?') }}</h2>
                <p>{{ translate('Share your wedding journey and inspire millions of hopeful families looking for sincere, vetted alliances.') }}</p>
                <a href="{{ auth()->check() ? route('happy_story.member') : route('login') }}" class="btn btn-light btn-sm mr-2"><i class="las la-heart mr-1"></i>{{ translate('Submit Your Success Story') }}</a>
                <a href="{{ route('contact_us') }}" class="btn btn-outline-light btn-sm"><i class="las la-phone mr-1"></i>{{ translate('Dedicated Helpline') }}</a>
            </div>
            <div class="hq-story-hamper"><i class="las la-gift fs-30 mb-2"></i><strong class="d-block">{{ translate('Hamqadam Hamper') }}</strong><small>{{ translate('A keepsake for selected verified success stories.') }}</small></div>
        </div>
    </div>
</section>

<section class="hq-design-section">
    <div class="container">
        <div class="hq-design-section-title">
            <span class="eyebrow">{{ translate('Parent & Guardian Guidance') }}</span>
            <h2>{{ translate("Safeguarding Your Family’s Sacred Journey") }}</h2>
            <p>{{ translate('Practical advice and sanctuary protocols designed to keep proposals serene, secure, and aligned with cultural traditions.') }}</p>
        </div>
        <div class="hq-safety-grid">
            <div class="hq-safety-card"><i class="las la-shield-alt"></i><h4>{{ translate('Guardian Privacy Shield') }}</h4><p>{{ translate('Control numbers and high-resolution portraits remain shielded until both families explicitly grant mutual consent.') }}</p></div>
            <div class="hq-safety-card"><i class="las la-door-open"></i><h4>{{ translate('First Meeting Protocols') }}</h4><p>{{ translate('Our elders advise conducting the first discussion through a scheduled family conference call or dignified home visit with clear permissions.') }}</p></div>
            <div class="hq-safety-card"><i class="las la-id-card"></i><h4>{{ translate('Vetted Identity Verification') }}</h4><p>{{ translate('Hamqadam reviews official verification details before granting the verified member status shown throughout the platform.') }}</p></div>
        </div>
        <div class="hq-support-cta mt-4"><div><h3>{{ translate('Have Questions About Family Safety?') }}</h3><p>{{ translate('Our Matrimonial Advisory Board conducts confidential parent consultations.') }}</p></div><div class="hq-support-cta-actions"><a href="{{ route('contact_us') }}" class="btn btn-light">{{ translate('Book Guardian Communication') }}</a></div></div>
    </div>
</section>
@endsection

@section('modal')
    @include('modals.login_modal')
    @include('modals.package_update_alert_modal')
@endsection

@section('script')
<script type="text/javascript">

	// Login alert
    function loginModal(){
        $('#LoginModal').modal();
    }

    // Package update alert
    function package_update_alert(){
      $('.package_update_alert_modal').modal('show');
    }

</script>
@endsection
