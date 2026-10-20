@extends('frontend.layouts.app')
@section('content')
<section class="hq-reference-page-hero hq-stories-reference-hero text-center">
    <div class="container">
        <!-- <div class="hq-reference-breadcrumb text-left">{{ translate('Home') }} <span>/</span> {{ translate('Happy Stories') }}</div> -->
        <span class="hq-reference-eyebrow"><i class="las la-heart"></i> {{ translate('Sacred Unions & Auspicious Beginnings') }}</span>
        <h1>{{ translate('Real Happy Stories of Lifetime Companionship')}}</h1>
        <p>{{ translate('Read how thousands of respectful families and couples found their destined match through Hamqadam’s verified, dignified matrimonial sanctuary.') }}</p>
        <p class="mt-3 mb-0"><strong>{{ $happy_stories->total() }}</strong> {{ translate('published community stories') }} · {{ translate('Shared with permission') }}</p>
    </div>
</section>

<section class="hq-story-page hq-reference-story-page hq-design-section">
    <div class="container">
        <div class="hq-design-section-title text-left mx-0 mb-4">
            <span class="eyebrow">{{ translate('Community Journeys') }}</span>
            <h2>{{ translate('Real Stories, Real Connections') }}</h2>
            <p>{{ translate('Read stories shared and approved by our community.') }}</p>
        </div>
        <div class="hq-story-grid">
            @foreach ($happy_stories as $happy_story)
                @php($photo = array_values(array_filter(explode(',', (string)$happy_story->photos))))
                <article class="card hq-story-card shadow-none">
                    <a href="{{ route('story_details', \App\Support\StoryPublicId::encode((int)$happy_story->id)) }}" class="text-reset d-block">
                        <img src="{{ !empty($photo[0]) ? uploaded_asset($photo[0]) : static_asset('assets/img/placeholder.jpg') }}" class="hq-story-cover" loading="lazy" alt="{{ $happy_story->title }}">
                    </a>
                    <div class="hq-story-card-copy p-4">
                        <span class="hq-story-card-badge"><i class="las la-heart"></i> {{ translate('Community Story') }}</span>
                        <h2 class="h5 mt-2 mb-2"><a href="{{ route('story_details', \App\Support\StoryPublicId::encode((int)$happy_story->id)) }}" class="text-dark">{{ $happy_story->title }}</a></h2>
                        <p class="text-muted fs-10 mb-3">{{ \Illuminate\Support\Str::limit(strip_tags((string)$happy_story->details), 135) }}</p>
                        <div class="d-flex justify-content-between align-items-center fs-9 text-muted">
                            <span><i class="las la-calendar mr-1"></i>{{ $happy_story->created_at?->format('M Y') }}</span>
                            <a href="{{ route('story_details', \App\Support\StoryPublicId::encode((int)$happy_story->id)) }}" class="text-primary fw-600">{{ translate('View Story') }} <i class="las la-arrow-right"></i></a>
                        </div>
                    </div>
                </article>
            @endforeach
            @if($happy_stories->isEmpty())<div class="hq-story-empty"><i class="las la-heart"></i><h3>{{ translate('More love stories are on their way') }}</h3><p>{{ translate('Be the first to share an inspiring journey.') }}</p></div>@endif
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
