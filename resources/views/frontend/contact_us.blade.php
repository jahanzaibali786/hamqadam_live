@extends('frontend.layouts.app')

@section('content')
@php
    $supportTicketsReady = \Illuminate\Support\Facades\Schema::hasTable('support_tickets');
    $latestTicket = null;
    if (auth()->check() && $supportTicketsReady) {
        $latestTicket = \App\Models\SupportTicket::where('sender_user_id', auth()->id())->latest()->first();
    }
@endphp
<section class="hq-reference-page-hero hq-ticket-reference-hero text-center">
    <div class="container">
        <div class="hq-reference-breadcrumb text-left">{{ translate('Home') }} <span>/</span> {{ translate('Help & Support') }}</div>
        <span class="hq-reference-eyebrow"><i class="las la-shield-alt"></i> {{ translate('Dedicated Family Assistance & Concierge') }}</span>
        <h1>{{ translate('How Can Our Matrimonial Counselors Assist You?') }}</h1>
        <p>{{ translate('Get confidential guidance for identity verification, packages, privacy, proposals, and respectful introductions.') }}</p>

        <div class="hq-contact-channel-grid">
            <div>
                <i class="las la-phone"></i>
                <span>{{ translate('Direct Helpline') }}</span>
                <strong>{{ get_setting('header_helpline_number') ?: '+01 112 352 566' }}</strong>
            </div>
            <div>
                <i class="las la-comments"></i>
                <span>{{ translate('Live Support') }}</span>
                <strong>{{ translate('Speak With Our Team') }}</strong>
            </div>
            <div>
                <i class="las la-envelope"></i>
                <span>{{ translate('Official Email') }}</span>
                <strong>{{ get_setting('contact_email') ?: get_setting('site_email') ?: 'support@hamqadam.com' }}</strong>
            </div>
            <div>
                <i class="las la-clock"></i>
                <span>{{ translate('Support Desk') }}</span>
                <strong>{{ translate('Round-the-clock ticket intake') }}</strong>
            </div>
        </div>
    </div>
</section>

<section class="hq-design-section hq-reference-ticket-page">
    <div class="container">
        <div class="hq-ticket-layout">
            <main class="hq-ticket-main">
                <div class="hq-ticket-heading mb-4">
                    <span><i class="las la-ticket-alt"></i></span>
                    <div>
                        <h2>{{ auth()->check() && $supportTicketsReady ? translate('Open a Support Ticket') : translate('Send a Confidential Enquiry') }}</h2>
                        <p>{{ auth()->check() && $supportTicketsReady ? translate('Your request will be linked to your Hamqadam account so you can receive support and follow its status.') : translate('Tell our matrimonial support team how we can help. Registered members can sign in to create a trackable support ticket.') }}</p>
                    </div>
                </div>

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0 pl-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if(session('support_ticket_id'))
                    <div class="alert alert-success">
                        <i class="las la-check-circle mr-1"></i>
                        {{ translate('Your support ticket was submitted successfully.') }}
                    </div>
                @endif

                @if(auth()->check() && $supportTicketsReady)
                    <form action="{{ route('support-ticket.store') }}" method="post" id="support-ticket-form">
                        @csrf
                        <div class="row gutters-10">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>{{ translate('Member Name') }}</label>
                                    <input type="text" class="form-control" value="{{ trim(auth()->user()->first_name . ' ' . auth()->user()->last_name) }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>{{ translate('Account Email') }}</label>
                                    <input type="email" class="form-control" value="{{ auth()->user()->email }}" readonly>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Subject') }} <span class="text-danger">*</span></label>
                            <input type="text" name="subject" value="{{ old('subject', request('subject')) }}" class="form-control" maxlength="255" placeholder="{{ translate('Briefly describe what you need help with') }}" required>
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Description') }} <span class="text-danger">*</span></label>
                            <textarea name="description" class="form-control" rows="8" placeholder="{{ translate('Share the relevant details so our support team can assist you accurately') }}" required>{{ old('description', request('description')) }}</textarea>
                        </div>
                        <div class="hq-ticket-meta">
                            <span><i class="las la-lock mr-1"></i>{{ translate('Private account-linked support request') }}</span>
                            <span><i class="las la-history mr-1"></i>{{ translate('Expected first response is shown after submission') }}</span>
                        </div>
                        <button type="submit" class="btn btn-primary mt-4 px-5">
                            <i class="las la-paper-plane mr-1"></i>{{ translate('Submit Support Ticket') }}
                        </button>
                    </form>
                @else
                    <form action="{{ route('contact-us.store') }}" method="post" id="contact-form">
                        @csrf
                        <div class="row gutters-10">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>{{ translate('Name') }} <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="name" value="{{ old('name', auth()->check() ? trim(auth()->user()->first_name . ' ' . auth()->user()->last_name) : '') }}" placeholder="{{ translate('Enter your full name') }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>{{ translate('Email') }} <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" name="email" value="{{ old('email', auth()->check() ? auth()->user()->email : '') }}" placeholder="{{ translate('Enter your email address') }}" required>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Enquiry Type') }} <span class="text-danger">*</span></label>
                            <select class="form-control" name="category" required>
                                <option value="">{{ translate('Select enquiry type') }}</option>
                                <option value="issue" @selected(old('category', request('category')) === 'issue')>{{ translate('Issue') }}</option>
                                <option value="suggestion" @selected(old('category', request('category')) === 'suggestion')>{{ translate('Suggestion') }}</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Subject') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="subject" value="{{ old('subject', request('subject')) }}" placeholder="{{ translate('Write the subject here') }}" required>
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Description') }} <span class="text-danger">*</span></label>
                            <textarea class="form-control" rows="8" name="description" placeholder="{{ translate('Write your description here') }}" required>{{ old('description', request('description')) }}</textarea>
                        </div>
                        @if(get_setting('google_recaptcha_activation') == 1 && get_setting('recaptcha_contact_form') == 1 && $errors->has('g-recaptcha-response'))
                            <span class="border invalid-feedback rounded p-2 mb-3 bg-danger text-white" role="alert" style="display:block;">
                                <strong>{{ $errors->first('g-recaptcha-response') }}</strong>
                            </span>
                        @endif
                        <button type="submit" class="btn btn-primary px-5">
                            <i class="las la-paper-plane mr-1"></i>{{ translate('Send Enquiry') }}
                        </button>
                        <p class="small text-muted mt-3 mb-0">
                            {{ translate('Already a member?') }} <a href="{{ route('login') }}" class="text-primary fw-600">{{ translate('Log in to create a trackable support ticket.') }}</a>
                        </p>
                    </form>
                @endif
            </main>

            <aside class="hq-ticket-side">
                @if(auth()->check() && $supportTicketsReady)
                    <div class="hq-ticket-side-card">
                        <h4><i class="las la-search mr-1"></i>{{ translate('Latest Ticket') }}</h4>
                        @if($latestTicket)
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <strong class="text-dark">#{{ $latestTicket->ticket_id ?: $latestTicket->id }}</strong>
                                <span class="badge {{ (string)$latestTicket->status === '1' ? 'badge-success' : 'badge-soft-primary' }}">{{ translate($latestTicket->status_label) }}</span>
                            </div>
                            <p class="mb-2">{{ \Illuminate\Support\Str::limit($latestTicket->subject, 90) }}</p>
                            @if($latestTicket->expected_response_at && (string)$latestTicket->status !== '1')
                                <p class="mb-0"><i class="las la-clock mr-1"></i>{{ translate('Expected response by') }} <strong>{{ $latestTicket->expected_response_at->format('d M Y, h:i A') }}</strong></p>
                            @elseif($latestTicket->resolved_at)
                                <p class="mb-0"><i class="las la-check-circle mr-1"></i>{{ translate('Resolved') }} {{ $latestTicket->resolved_at->diffForHumans() }}</p>
                            @endif
                        @else
                            <p class="mb-0">{{ translate('You have no support tickets yet. Your newest request will appear here after submission.') }}</p>
                        @endif
                    </div>
                @endif

                <div class="hq-ticket-side-card">
                    <h4><i class="las la-user-shield mr-1"></i>{{ translate('Privacy First') }}</h4>
                    <p>{{ translate('Support requests are handled within your account context. Sensitive matrimonial information should only be shared when necessary to resolve the issue.') }}</p>
                    <ul>
                        <li>{{ translate('Account-aware support for registered members') }}</li>
                        <li>{{ translate('Ticket status and expected response tracking') }}</li>
                        <li>{{ translate('Resolved-ticket support rating') }}</li>
                    </ul>
                </div>

                <div class="hq-ticket-side-card hq-quick-help">
                    <h4><i class="las la-question-circle mr-1"></i>{{ translate('Quick Help') }}</h4>
                    <details>
                        <summary>{{ translate('How do I report a profile?') }}</summary>
                        <p>{{ translate('Open the member profile and use the report option. Include only accurate information so the moderation team can review it properly.') }}</p>
                    </details>
                    <details>
                        <summary>{{ translate('Where do I manage my package?') }}</summary>
                        <p>{{ translate('Open Premium Plans to review available packages, entitlements and renewal options.') }}</p>
                    </details>
                    <details>
                        <summary>{{ translate('How are support ratings collected?') }}</summary>
                        <p>{{ translate('When an account-linked ticket is resolved, the member can rate that support interaction from 1 to 5 stars.') }}</p>
                    </details>
                </div>
            </aside>
        </div>

        <div class="hq-support-cta mt-5">
            <div>
                <h3>{{ translate('Need Guidance Before Sending Your Request?') }}</h3>
                <p>{{ translate('Our support team can help with verification, privacy, subscriptions, proposals, and account access.') }}</p>
            </div>
            <div class="hq-support-cta-actions">
                <a href="tel:{{ preg_replace('/[^0-9+]/', '', (string)(get_setting('header_helpline_number') ?: '+01112352566')) }}" class="btn btn-outline-primary"><i class="las la-phone mr-1"></i>{{ translate('Call Helpline') }}</a>
                @guest
                    <a href="{{ route('login') }}" class="btn btn-primary"><i class="las la-sign-in-alt mr-1"></i>{{ translate('Member Login') }}</a>
                @endguest
            </div>
        </div>
    </div>
</section>
@endsection

@section('script')
    @guest
        @if(get_setting('google_recaptcha_activation') == 1 && get_setting('recaptcha_contact_form') == 1)
            @include('partials.recaptcha', ['action' => 'recaptcha_contact_form','form_id' => 'contact-form'])
        @endif
    @endguest
@endsection
