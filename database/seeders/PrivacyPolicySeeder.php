<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Replaces the lorem-ipsum placeholder on the `privacy-policy` custom page
 * with a real, Google Play–compliant policy. Idempotent: safe to re-run, and
 * it creates the page if a fresh install is missing it.
 */
class PrivacyPolicySeeder extends Seeder
{
    public function run(): void
    {
        $content = <<<'HTML'
<p><em>Last updated: September 25, 2026</em></p>

<p>Hamqadam ("we", "our", "us") operates the Hamqadam mobile application and the website <strong>https://hamqadam.com</strong> (together, the "Service"). This Privacy Policy explains what information we collect, how we use it, and the choices you have. By using Hamqadam you agree to this policy.</p>

<h3>1. Information We Collect</h3>
<p><strong>Information you provide:</strong></p>
<ul>
    <li>Account details: name, email address, phone number, gender, and date of birth.</li>
    <li>Profile details: photos, education, occupation, income, family information, partner preferences, and other details you choose to add to your matrimonial profile.</li>
    <li>Identity verification documents: government-issued ID (CNIC/passport) and a live selfie, submitted for profile verification. These are used only for verification.</li>
    <li>Content you create: proposals/interests, messages, chat text and voice notes, gifts, notifications, and reviews.</li>
</ul>
<p><strong>Information collected automatically:</strong></p>
<ul>
    <li>Device information: device model, operating system, unique device identifiers, and app version.</li>
    <li>Log and usage data: pages viewed, features used, and timestamps.</li>
    <li>Approximate location: only when you explicitly add your city to your profile. The app does not track your location in the background.</li>
</ul>
<p><strong>Permissions:</strong> The app may request access to your camera (profile photos and video calls), microphone (voice notes and calls), photo library (uploads), and notifications. You can revoke any permission from your device settings at any time.</p>

<h3>2. How We Use Your Information</h3>
<ul>
    <li>To create and maintain your matrimonial profile and show it to other members according to your privacy settings.</li>
    <li>To provide core features: search and discovery, interest/expression of interest, chat, audio &amp; video calls, gift sending, and notifications.</li>
    <li>To verify profiles and prevent fake accounts, fraud, harassment, and misuse.</li>
    <li>To process coin/package purchases and maintain your coin wallet balance and transaction history.</li>
    <li>To send you service notifications (matches, interests, messages, calls) and, with your consent, promotional communications.</li>
    <li>To improve, troubleshoot, and secure the Service.</li>
</ul>

<h3>3. Payments and Wallet</h3>
<p>Hamqadam uses an internal coin wallet (coins) for features such as sending interests and gifts. Purchases of coins/packages are processed by our payment gateway partners. We do not store your full card numbers, CVV, or banking credentials on our servers.</p>

<h3>4. How We Share Information</h3>
<p>We do not sell your personal data. We share information only in these cases:</p>
<ul>
    <li><strong>Other members:</strong> Your profile information is visible to other members according to the privacy settings you choose (public, contacts-only, or private). You control what is visible.</li>
    <li><strong>Guardian mode:</strong> If you invite a family guardian, the guardian can see only the parts of your activity you explicitly allow.</li>
    <li><strong>Service providers:</strong> Hosting, messaging/push notifications (e.g., Firebase), payment processing, and call infrastructure (e.g., Agora) providers who process data on our behalf.</li>
    <li><strong>Legal requirements:</strong> Where required by law, court order, or to protect the rights, property, or safety of our users.</li>
</ul>

<h3>5. Data Retention</h3>
<p>We keep your information for as long as your account is active. When you deactivate or delete your account, we remove or anonymize your profile and personal data within a reasonable period, except where we must retain certain records for legal, fraud-prevention, or dispute-resolution purposes.</p>

<h3>6. Data Security</h3>
<p>We use industry-standard measures such as encrypted connections (HTTPS/TLS), hashed passwords, and access controls to protect your data. However, no method of transmission or storage is 100% secure, and we cannot guarantee absolute security.</p>

<h3>7. Children's Privacy</h3>
<p>Hamqadam is a matrimonial service intended for adults aged 18 and over. We do not knowingly collect information from anyone under 18. If you believe a minor has created an account, please contact us and we will remove it.</p>

<h3>8. Your Rights and Choices</h3>
<ul>
    <li>Update or correct your profile information from the app at any time.</li>
    <li>Change your privacy visibility settings (who can see your photos and details).</li>
    <li><strong>Delete your account</strong> directly from the app (Settings &rarr; Delete Account) or by contacting us. When you request deletion, your account is hidden immediately, all your sessions are signed out, and your personal data — including your name, contact details, photos, profile details, verification documents, chat content and wallet — is destroyed. Only a minimal, anonymous record is kept for up to 30 days for fraud prevention and legal compliance, after which it is permanently removed.</li>
    <li>Request a copy of your data or ask us to delete it, subject to legal retention requirements.</li>
    <li>Opt out of promotional notifications and communications.</li>
</ul>

<h3>9. Third-Party Services</h3>
<p>The app integrates third-party services including Firebase (push notifications), Agora (audio/video calls), and payment gateways. Their handling of data is governed by their own privacy policies.</p>

<h3>10. Changes to This Policy</h3>
<p>We may update this Privacy Policy from time to time. Material changes will be communicated through the app or the website, and the "Last updated" date above will be revised.</p>

<h3>11. Contact Us</h3>
<p>For any questions, data requests, or complaints about this Privacy Policy, contact us at <strong>support@hamqadam.com</strong> or through the in-app help &amp; support section.</p>
HTML;

        $values = [
            'type' => 'privacy_policy_page',
            'title' => 'Privacy Policy',
            'content' => $content,
            'meta_title' => 'Privacy Policy | Hamqadam',
            'meta_description' => 'How Hamqadam collects, uses, protects and shares your information — Google Play privacy policy for the Hamqadam matrimonial app.',
            'keywords' => 'privacy policy, hamqadam, matrimonial, data protection',
        ];

        $page = Page::where('slug', 'privacy-policy')->first();

        // The Page model has no $fillable, so avoid mass assignment and set
        // each column explicitly.
        $page = $page ?? new Page();
        $page->type = $values['type'];
        $page->title = $values['title'];
        $page->slug = 'privacy-policy';
        $page->content = $values['content'];
        $page->meta_title = $values['meta_title'];
        $page->meta_description = $values['meta_description'];
        $page->keywords = $values['keywords'];
        $page->save();

        $this->command?->info($page->wasRecentlyCreated ? 'privacy-policy page created.' : 'privacy-policy page updated (id ' . $page->id . ').');
    }
}
