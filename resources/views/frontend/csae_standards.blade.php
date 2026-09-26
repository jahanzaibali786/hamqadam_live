@extends('frontend.layouts.app')

@section('meta_title')Child Safety (CSAE) Standards | Hamqadam@stop

@section('meta_description')Hamqadam's published standards against child sexual abuse and exploitation (CSAE) — our zero-tolerance policy, prevention measures, reporting and enforcement.@stop

@section('content')
<section class="pt-4 mb-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">

                <h1 class="fw-600 h3 mb-3">Child Safety — Standards Against Child Sexual Abuse and Exploitation (CSAE)</h1>

                <p><strong>Effective date:</strong> {{ now()->format('F j, Y') }} &nbsp;·&nbsp; <strong>Applies to:</strong> the Hamqadam mobile app, website (hamqadam.com) and all related services.</p>

                <h2 class="fw-600 h5 mt-4">1. Our commitment</h2>
                <p>Hamqadam is a matrimonial service for adults only. We have a <strong>zero-tolerance policy</strong> towards child sexual abuse and exploitation (CSAE) in any form. We are committed to keeping our platform safe and to complying with all applicable child-safety laws, including Google Play's Child Safety Standards.</p>

                <h2 class="fw-600 h5 mt-4">2. Age requirement</h2>
                <ul class="pl-3">
                    <li>The service is strictly limited to users aged <strong>18 years or older</strong>.</li>
                    <li>Date of birth is collected at registration and enforced by the backend; accounts under 18 are rejected.</li>
                    <li>Suspected minors are removed and their accounts are permanently banned.</li>
                </ul>

                <h2 class="fw-600 h5 mt-4">3. Prohibited content and behaviour</h2>
                <p>The following are strictly prohibited on Hamqadam and result in immediate removal and a permanent ban:</p>
                <ul class="pl-3">
                    <li>Any sexual content involving minors, real or depicted</li>
                    <li>Grooming, sexualised conversation with, or targeting of minors</li>
                    <li>Sharing, requesting or linking to child sexual abuse material (CSAM)</li>
                    <li>Any attempt to contact or arrange meetings with minors through the platform</li>
                </ul>

                <h2 class="fw-600 h5 mt-4">4. Prevention measures</h2>
                <ul class="pl-3">
                    <li><strong>Identity verification:</strong> government-ID based verification with selfie matching for every member.</li>
                    <li><strong>Manual review:</strong> suspicious profiles are held for human review before activation.</li>
                    <li><strong>Reporting tools:</strong> every profile and chat has a one-tap Report/Block option, monitored by our moderation team.</li>
                    <li><strong>Human moderation:</strong> reported content is reviewed promptly by trained staff.</li>
                    <li><strong>No minor-facing design:</strong> the app is not directed at children and is not marketed to them.</li>
                </ul>

                <h2 class="fw-600 h5 mt-4">5. Reporting suspected CSAE</h2>
                <p>If you see anything on Hamqadam that may involve the abuse or exploitation of a minor, report it immediately:</p>
                <ul class="pl-3">
                    <li>In the app: open the profile or chat → tap the <strong>flag / Report</strong> icon, or use <strong>Help &amp; Support</strong>.</li>
                    <li>Email: <strong>support@hamqadam.com</strong> with the words "Child Safety" in the subject line — these reports are prioritised.</li>
                </ul>
                <p>We respond to child-safety reports as a priority and take immediate action: suspending the account, preserving evidence and, where required, reporting to law enforcement and relevant child-protection authorities such as NCMEC (National Center for Missing &amp; Exploited Children) or the user's local authority.</p>

                <h2 class="fw-600 h5 mt-4">6. Enforcement</h2>
                <ul class="pl-3">
                    <li>Accounts violating these standards are <strong>permanently banned</strong> with no right of appeal where CSAE is suspected.</li>
                    <li>We preserve and, where legally required, disclose evidence to law enforcement.</li>
                    <li>We co-operate fully with legal processes and authorities investigating child exploitation.</li>
                </ul>

                <h2 class="fw-600 h5 mt-4">7. Contact</h2>
                <p>Child-safety questions or reports: <strong>support@hamqadam.com</strong> (subject: "Child Safety").</p>

            </div>
        </div>
    </div>
</section>
@endsection
