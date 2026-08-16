@extends('frontend.layouts.app')

@section('content')
<div class="py-4 py-lg-5">
	<div class="container">
		<div class="row">
			<div class="col-xxl-6 col-xl-6 col-md-8 mx-auto">
				<div class="card">
					<div class="card-body">

						<div class="mb-5 text-center">
                            @php($registrationRewardCoins = \App\Support\RegistrationReward::rewardCoins())
							<h1 class="h3 text-primary mb-0">{{ translate('Create Your Account') }}</h1>
							<p>{{ translate('Register now and get reward of') }} {{ $registrationRewardCoins }} {{ translate('coins from the Basic Free package') }}.</p>
						</div>
						<form class="form-default" id="reg-form" role="form" action="{{ route('register') }}" method="POST" enctype="multipart/form-data">
							@csrf

								<!-- Recaptcha -->
							@if(get_setting('google_recaptcha_activation') == 1 && get_setting('recaptcha_user_register') == 1)

							@if ($errors->has('g-recaptcha-response'))
							<span class="border invalid-feedback rounded p-2 mb-3 bg-danger text-white" role="alert" style="display: block;">
								<strong>{{ $errors->first('g-recaptcha-response') }}</strong>
							</span>
							@endif
							@endif

                            @include('frontend.partials.registration_onboarding_steps')

							<div class="mb-3 d-none" id="registrationTermsBlock">
								<label class="aiz-checkbox">
								<input type="checkbox" name="checkbox_example_1" required>
									<span class=opacity-60>{{ translate('By signing up you agree to our')}}
										<a href="{{ env('APP_URL').'/terms-conditions' }}" target="_blank">{{ translate('terms and conditions')}}.</a>
									</span>
									<span class="aiz-square-check"></span>
								</label>
							</div>
							@error('checkbox_example_1')
								<span class="invalid-feedback" role="alert">{{ $message }}</span>
							@enderror

							<div class="mb-5">
								<button type="submit" id="createAccountBtn" class="btn btn-block btn-primary d-none">{{ translate('Create Account') }}</button>
							</div>
							@if(get_setting('google_login_activation') == 1 || get_setting('facebook_login_activation') == 1 || get_setting('twitter_login_activation') == 1 || get_setting('apple_login_activation') == 1)
			                <div class="mb-5">
			                    <div class="separator mb-3">
			                        <span class="bg-white px-3">{{ translate('Or Join With') }}</span>
			                    </div>
	                    		<ul class="list-inline social colored text-center">
									@if(get_setting('facebook_login_activation') == 1)
			                        <li class="list-inline-item">
			                            <a href="{{ route('social.login', ['provider' => 'facebook']) }}" class="facebook" title="{{ translate('Facebook') }}"><i class="lab la-facebook-f"></i></a>
			                        </li>
									@endif
									@if(get_setting('google_login_activation') == 1)
									<li class="list-inline-item">
										<a href="{{ route('social.login', ['provider' => 'google']) }}" class="google" title="{{ translate('Google') }}"><i class="lab la-google"></i></a>
									</li>
									@endif
									@if(get_setting('twitter_login_activation') == 1)
			                        <li class="list-inline-item">
			                            <a href="{{ route('social.login', ['provider' => 'twitter']) }}" class="twitter" title="{{ translate('Twitter') }}"><i class="lab la-twitter"></i></a>
			                        </li>
									@endif
									@if(get_setting('apple_login_activation') == 1)
			                        <li class="list-inline-item">
			                            <a href="{{ route('social.login', ['provider' => 'apple']) }}" class="apple" title="{{ translate('Apple') }}"><i class="lab la-apple"></i></a>
			                        </li>
									@endif
								</ul>
							</div>
							@endif

							<div class="text-center">
								<p class="text-muted mb-0">{{ translate("Already have an account?") }}</p>
								<a href="{{ route('login') }}">{{ translate('Login to your account') }}</a>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
@endsection


@section('script')


	@if(get_setting('google_recaptcha_activation') == 1 && get_setting('recaptcha_user_register') == 1)
		@include('partials.recaptcha', ['action' => 'recaptcha_user_register','form_id' => 'reg-form'])
	@endif
	@if(addon_activation('otp_system'))
		@include('partials.emailOrPhone')
	@endif

	 @if (get_setting('registration_verification'))
        @include('partials.verifyEmailOrPhone')
    @endif

	<script type="text/javascript">
		const regVerifyRequired = {{get_setting('registration_verification') ? 'true' : 'false' }};
		const createBtn   = $('#createAccountBtn');
		const termsCheckbox = $('input[name="checkbox_example_1"]');
		function toggleCreateBtn() {
			const termsChecked = termsCheckbox.is(':checked');
			const regVerified  = regVerifyRequired ? (verifyBtn && verifyBtn.classList.contains('disabled')) : true;
			let enableBtn = false;
			if (regVerifyRequired) {
				enableBtn = termsChecked && regVerified;
			} else {
				enableBtn = termsChecked;
			}
			createBtn.prop('disabled', !enableBtn);
		}

		document.addEventListener('DOMContentLoaded', function() {
			toggleCreateBtn();
			termsCheckbox.on('change', toggleCreateBtn);
		});
	</script>
@endsection
