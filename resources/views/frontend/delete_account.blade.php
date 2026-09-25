@extends('frontend.layouts.app')

@section('meta_title')Delete Account | Hamqadam@stop

@section('meta_description')How to delete your Hamqadam account and what happens to your data — in-app deletion steps, data removal details and support contact.@stop

@section('content')
<section class="pt-4 mb-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">

                <h1 class="fw-600 h3 mb-3">Delete Your Account</h1>

                <p>You can delete your Hamqadam account at any time, directly from the mobile app. Account deletion is <strong>permanent and cannot be undone</strong> once the 30-day grace window has passed.</p>

                <h2 class="fw-600 h5 mt-4">How to delete your account (in the app)</h2>
                <ol class="pl-3">
                    <li>Open the <strong>Hamqadam app</strong> and log in.</li>
                    <li>Tap the <strong>menu (top-left)</strong> to open the side drawer.</li>
                    <li>Tap <strong>Delete Account</strong> (shown in red).</li>
                    <li>Read the warning and tap <strong>Delete</strong> to confirm.</li>
                </ol>
                <p>Your account is hidden immediately, you are signed out on every device, and your personal data is removed right away.</p>

                <h2 class="fw-600 h5 mt-4">What data is deleted</h2>
                <ul class="pl-3">
                    <li>Your name, email address, phone number and login credentials</li>
                    <li>Your entire matrimonial profile — photos, education, career, family details and partner preferences</li>
                    <li>Identity verification documents and selfies</li>
                    <li>Chats, interests/proposals, gifts and your coin wallet balance</li>
                    <li>Push notification tokens and device sessions</li>
                </ul>

                <h2 class="fw-600 h5 mt-4">What is kept (and for how long)</h2>
                <p>Only a minimal, <strong>anonymous</strong> record is retained for up to <strong>30 days</strong> for fraud prevention and legal compliance. It contains no personal data and is permanently deleted after that period.</p>

                <h2 class="fw-600 h5 mt-4">Need help?</h2>
                <p>If you cannot access the app or need assistance with deletion, contact us at <strong>support@hamqadam.com</strong> or through the in-app Help &amp; Support section. Please mention the email or phone number linked to the account.</p>

                <p class="mt-4"><a href="{{ url('/privacy-policy') }}">Read our full Privacy Policy &rarr;</a></p>

            </div>
        </div>
    </div>
</section>
@endsection
