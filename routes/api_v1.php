<?php

use App\Http\Controllers\Api\V1\Ai\AiController;
use App\Http\Controllers\Api\V1\Admin\AdminOverviewController;
use App\Http\Controllers\Api\V1\Chat\ChatController;
use App\Http\Controllers\Api\V1\Content\ContentController;
use App\Http\Controllers\Api\V1\Family\FamilyController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Auth\MobileRegistrationController;
use App\Http\Controllers\Api\V1\Verification\AiVerificationController;
use App\Http\Controllers\Api\V1\Auth\StepRegistrationController;
use App\Http\Controllers\Api\V1\Auth\StepwiseRegistrationController;
use App\Http\Controllers\Api\V1\Matching\MatchController;
use App\Http\Controllers\Api\V1\Notification\NotificationController;
use App\Http\Controllers\Api\V1\PartnerPreference\PartnerPreferenceController;
use App\Http\Controllers\Api\V1\Payment\PaymentController;
use App\Http\Controllers\Api\V1\Profile\ProfileController;
use App\Http\Controllers\Api\V1\Profile\DropdownReferenceController;
use App\Http\Controllers\Api\V1\Proposal\ProposalController;
use App\Http\Controllers\Api\V1\Proposal\ProposalMeetingController;
use App\Http\Controllers\Api\V1\Search\SearchController;
use App\Http\Controllers\Api\V1\Safety\SafetyController;
use App\Http\Controllers\Api\V1\Verification\VerificationController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class)->name('api.v1.health');

Route::middleware('auth:sanctum')->get('/admin/overview', AdminOverviewController::class)->name('api.v1.admin.overview');

Route::prefix('auth')->name('api.v1.auth.')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1')->name('register');
    Route::post('/register/complete', [MobileRegistrationController::class, 'register'])->middleware('throttle:5,1')->name('register.complete');
    Route::get('/register/steps', [StepwiseRegistrationController::class, 'definitions'])->middleware('throttle:5,1')->name('register.steps');
    Route::post('/register/step1', [StepwiseRegistrationController::class, 'step1'])->middleware('throttle:5,1')->name('register.step1');
    Route::post('/login/email', [AuthController::class, 'emailLogin'])->middleware('throttle:10,1')->name('login.email');
    Route::post('/login/google', [AuthController::class, 'googleLogin'])->middleware('throttle:10,1')->name('login.google');
    Route::post('/otp/mobile', [AuthController::class, 'requestMobileOtp'])->middleware('throttle:5,1')->name('otp.mobile');
    Route::post('/login/mobile', [AuthController::class, 'verifyMobileOtp'])->middleware('throttle:10,1')->name('login.mobile');
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:5,1')->name('forgot_password');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:10,1')->name('reset_password');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me'])->name('me');
        Route::post('/email/verification-code', [AuthController::class, 'requestEmailVerification'])->middleware('throttle:5,1')->name('email.verification_code');
        Route::post('/email/verify', [AuthController::class, 'verifyEmail'])->middleware('throttle:10,1')->name('email.verify');
        Route::post('/register/request-otp', [AuthController::class, 'requestRegistrationOtp'])->middleware('throttle:5,1')->name('register.request_otp');
        Route::post('/register/verify-otp', [AuthController::class, 'verifyRegistrationOtp'])->middleware('throttle:10,1')->name('register.verify_otp');
        Route::get('/devices', [AuthController::class, 'devices'])->name('devices');
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::post('/logout-all', [AuthController::class, 'logoutAll'])->name('logout_all');
        Route::delete('/account', [AuthController::class, 'deleteAccount'])->name('account.delete');

        // Step-wise registration
        Route::prefix('register')->name('register.')->group(function () {
            Route::post('/step/{step}', [StepwiseRegistrationController::class, 'save'])->whereNumber('step')->name('step.save');
            Route::get('/status', [StepwiseRegistrationController::class, 'status'])->name('status.v2');
            Route::post('/step2', [StepRegistrationController::class, 'step2'])->name('step2');
            Route::post('/step3', [StepRegistrationController::class, 'step3'])->name('step3');
            Route::post('/step4', [StepRegistrationController::class, 'step4'])->name('step4');
            Route::post('/step5', [StepRegistrationController::class, 'step5'])->name('step5');
            Route::post('/step6', [StepRegistrationController::class, 'step6'])->name('step6');
            Route::post('/step7', [StepRegistrationController::class, 'step7'])->name('step7');
            Route::post('/step8', [StepRegistrationController::class, 'step8'])->name('step8');
            Route::post('/step9', [StepRegistrationController::class, 'step9'])->name('step9');
            Route::post('/step10', [StepRegistrationController::class, 'step10'])->name('step10');
            Route::get('/legacy-status', [StepRegistrationController::class, 'status'])->name('status.legacy');
        });
    });
});

Route::middleware('auth:sanctum')->prefix('profile')->name('api.v1.profile.')->group(function () {
    Route::get('/', [ProfileController::class, 'show'])->name('show');
    Route::put('/', [ProfileController::class, 'update'])->name('update');
    Route::patch('/privacy', [ProfileController::class, 'updatePrivacy'])->name('privacy.update');
    Route::patch('/visibility', [ProfileController::class, 'updateVisibility'])->name('visibility.update');
    Route::post('/deactivate', [ProfileController::class, 'deactivate'])->name('deactivate');
    Route::get('/dropdown-reference-data', [DropdownReferenceController::class, 'index'])->name('dropdown_reference_data');
});

Route::middleware('auth:sanctum')->prefix('profiles')->name('api.v1.profiles.')->group(function () {
    Route::get('/{profile}', [ProfileController::class, 'publicProfile'])->name('show');
    Route::get('/{profile}/compatibility', [ProfileController::class, 'compatibility'])->name('compatibility');
});

Route::middleware('auth:sanctum')->prefix('partner-preferences')->name('api.v1.partner_preferences.')->group(function () {
    Route::get('/', [PartnerPreferenceController::class, 'show'])->name('show');
    Route::put('/', [PartnerPreferenceController::class, 'update'])->name('update');
    Route::delete('/', [PartnerPreferenceController::class, 'clear'])->name('clear');
});

Route::middleware('auth:sanctum')->prefix('matches')->name('api.v1.matches.')->group(function () {
    Route::get('/', [MatchController::class, 'index'])->name('index');
    Route::get('/recommended', [MatchController::class, 'recommended'])->name('recommended');
    Route::get('/daily', [MatchController::class, 'daily'])->name('daily');
    Route::get('/{profile}', [MatchController::class, 'show'])->name('show');
    Route::post('/recalculate', [MatchController::class, 'recalculate'])->middleware('throttle:5,1')->name('recalculate');
    Route::post('/recalculate-async', [MatchController::class, 'recalculateAsync'])->middleware('throttle:10,1')->name('recalculate_async');
    Route::post('/feedback', [MatchController::class, 'feedback'])->middleware('throttle:60,1')->name('feedback');
});

Route::middleware('auth:sanctum')->prefix('search')->name('api.v1.search.')->group(function () {
    Route::get('/profiles', [SearchController::class, 'profiles'])->name('profiles');
    Route::get('/history', [SearchController::class, 'history'])->name('history');
    Route::get('/saved', [SearchController::class, 'saved'])->name('saved');
    Route::post('/saved', [SearchController::class, 'storeSaved'])->name('saved.store');
    Route::delete('/saved/{id}', [SearchController::class, 'deleteSaved'])->name('saved.delete');
    Route::post('/hidden-users', [SearchController::class, 'hideFrom'])->name('hidden_users.store');
    Route::delete('/hidden-users/{user}', [SearchController::class, 'unhideFrom'])->name('hidden_users.delete');
});

Route::middleware('auth:sanctum')->prefix('proposals')->name('api.v1.proposals.')->group(function () {
    Route::get('/', [ProposalController::class, 'index'])->name('index');
    Route::post('/', [ProposalController::class, 'store'])->middleware('throttle:20,1')->name('store');
    Route::get('/favourites', [ProposalController::class, 'favourites'])->name('favourites');
    Route::post('/favourites', [ProposalController::class, 'favourite'])->name('favourites.store');
    Route::get('/favourites/{user}/check', [ProposalController::class, 'checkFavourite'])->name('favourites.check');
    Route::delete('/favourites/{user}', [ProposalController::class, 'removeFavourite'])->name('favourites.delete');
    Route::post('/ignored', [ProposalController::class, 'ignore'])->name('ignored.store');
    Route::delete('/ignored/{user}', [ProposalController::class, 'removeIgnore'])->name('ignored.delete');
    Route::post('/{proposal}/accept', [ProposalController::class, 'accept'])->name('accept');
    Route::post('/{proposal}/reject', [ProposalController::class, 'reject'])->name('reject');
    Route::post('/{proposal}/withdraw', [ProposalController::class, 'withdraw'])->name('withdraw');
    Route::post('/{proposal}/cancel', [ProposalController::class, 'cancel'])->name('cancel');
    Route::post('/{proposal}/notes', [ProposalController::class, 'addNote'])->name('notes.store');
    Route::get('/{proposal}/timeline', [ProposalController::class, 'timeline'])->name('timeline');
    Route::get('/{proposal}/meetings', [ProposalMeetingController::class, 'index'])->name('meetings');
    Route::post('/{proposal}/meetings', [ProposalMeetingController::class, 'store'])->name('meetings.store');
    Route::patch('/meetings/{meeting}', [ProposalMeetingController::class, 'update'])->name('meetings.update');
    Route::post('/meetings/{meeting}/feedback', [ProposalMeetingController::class, 'feedback'])->name('meetings.feedback');
    Route::post('/meetings/{meeting}/recording-consent', [ProposalMeetingController::class, 'recordingConsent'])->name('meetings.recording_consent');
    Route::post('/relationship-status', [ProposalMeetingController::class, 'relationshipStatus'])->name('relationship_status.store');
});

Route::middleware('auth:sanctum')->prefix('chat')->name('api.v1.chat.')->group(function () {
    Route::get('/threads', [ChatController::class, 'threads'])->name('threads');
    Route::get('/threads/{thread}/messages', [ChatController::class, 'messages'])->name('messages');
    Route::post('/threads/{thread}/messages', [ChatController::class, 'send'])->middleware('throttle:60,1')->name('messages.send');
    Route::post('/threads/{thread}/typing', [ChatController::class, 'typing'])->middleware('throttle:120,1')->name('typing');
    Route::post('/threads/{thread}/block', [ChatController::class, 'block'])->name('block');
    Route::post('/threads/{thread}/unblock', [ChatController::class, 'unblock'])->name('unblock');
    Route::post('/threads/{thread}/report', [ChatController::class, 'report'])->middleware('throttle:10,1')->name('report');
    Route::delete('/messages/{message}', [ChatController::class, 'deleteForMe'])->name('messages.delete_for_me');
});

Route::middleware('auth:sanctum')->prefix('verification')->name('api.v1.verification.')->group(function () {
    Route::get('/current', [VerificationController::class, 'current'])->name('current');
    Route::get('/history', [VerificationController::class, 'history'])->name('history');
    Route::post('/submit', [VerificationController::class, 'submit'])->middleware('throttle:5,1')->name('submit');

    /*
     * AI identity verification. Separate from /submit on purpose: these take no
     * uploads and rebuild the model payload from the database, for when
     * registration succeeded but verification did not complete.
     *
     * `run` is synchronous and calls a CPU-bound model, so it is throttled
     * harder than the read endpoints.
     */
    Route::prefix('ai')->name('ai.')->group(function () {
        Route::get('/status', [AiVerificationController::class, 'status'])->name('status');
        Route::get('/history', [AiVerificationController::class, 'history'])->name('history');
        Route::post('/run', [AiVerificationController::class, 'run'])->middleware('throttle:3,1')->name('run');
    });
});

Route::middleware('auth:sanctum')->prefix('admin/verifications')->name('api.v1.admin.verifications.')->group(function () {
    Route::get('/', [VerificationController::class, 'queue'])->name('queue');
    Route::get('/{verification}', [VerificationController::class, 'show'])->name('show');
    Route::post('/{verification}/approve', [VerificationController::class, 'approve'])->name('approve');
    Route::post('/{verification}/reject', [VerificationController::class, 'reject'])->name('reject');
});

Route::prefix('payments')->name('api.v1.payments.')->group(function () {
    Route::post('/webhooks/stripe', [PaymentController::class, 'stripeWebhook'])->name('webhooks.stripe');
    Route::post('/webhooks/easypaisa', [PaymentController::class, 'easypaisaWebhook'])->name('webhooks.easypaisa');
    Route::post('/webhooks/jazzcash', [PaymentController::class, 'jazzcashWebhook'])->name('webhooks.jazzcash');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/plans', [PaymentController::class, 'plans'])->name('plans');
        Route::post('/checkout', [PaymentController::class, 'checkout'])->middleware('throttle:10,1')->name('checkout');
        Route::get('/history', [PaymentController::class, 'history'])->name('history');
        Route::get('/invoices/{payment}', [PaymentController::class, 'invoice'])->name('invoice');
        Route::post('/coupons/validate', [PaymentController::class, 'validateCoupon'])->middleware('throttle:20,1')->name('coupons.validate');
    });
});

Route::middleware('auth:sanctum')->prefix('notifications')->name('api.v1.notifications.')->group(function () {
    Route::get('/', [NotificationController::class, 'index'])->name('index');
    Route::get('/unread-count', [NotificationController::class, 'unreadCount'])->name('unread_count');
    Route::post('/mark-all-read', [NotificationController::class, 'markAllRead'])->name('mark_all_read');
    Route::get('/preferences', [NotificationController::class, 'preferences'])->name('preferences');
    Route::patch('/preferences', [NotificationController::class, 'updatePreferences'])->name('preferences.update');
    Route::post('/push-tokens', [NotificationController::class, 'storePushToken'])->name('push_tokens.store');
    Route::delete('/push-tokens/{token}', [NotificationController::class, 'deletePushToken'])->name('push_tokens.delete');
    Route::post('/{notification}/read', [NotificationController::class, 'markRead'])->name('read');
});

Route::middleware('auth:sanctum')->prefix('family')->name('api.v1.family.')->group(function () {
    Route::get('/dashboard', [FamilyController::class, 'dashboard'])->name('dashboard');
    Route::get('/guardians', [FamilyController::class, 'guardians'])->name('guardians');
    Route::post('/guardians', [FamilyController::class, 'storeGuardian'])->name('guardians.store');
    Route::patch('/guardians/{guardian}', [FamilyController::class, 'updateGuardian'])->name('guardians.update');
    Route::post('/guardians/{guardian}/approve', [FamilyController::class, 'approveGuardian'])->name('guardians.approve');
    Route::delete('/guardians/{guardian}', [FamilyController::class, 'revokeGuardian'])->name('guardians.delete');
    Route::post('/wali-mode', [FamilyController::class, 'waliMode'])->name('wali_mode');
    Route::get('/managed-profiles', [FamilyController::class, 'managedProfiles'])->name('managed_profiles');
    Route::get('/approval-requests', [FamilyController::class, 'approvalRequests'])->name('approval_requests');
    Route::post('/approval-requests', [FamilyController::class, 'requestApproval'])->name('approval_requests.store');
    Route::post('/approval-requests/{approval}/approve', [FamilyController::class, 'approveRequest'])->name('approval_requests.approve');
    Route::post('/approval-requests/{approval}/reject', [FamilyController::class, 'rejectRequest'])->name('approval_requests.reject');
    Route::get('/notes/{profile}', [FamilyController::class, 'notes'])->name('notes');
    Route::post('/notes', [FamilyController::class, 'storeNote'])->name('notes.store');
    Route::get('/conversations', [FamilyController::class, 'conversations'])->name('conversations');
    Route::post('/conversations', [FamilyController::class, 'startConversation'])->name('conversations.store');
    Route::get('/conversations/{conversation}/messages', [FamilyController::class, 'messages'])->name('conversations.messages');
    Route::post('/conversations/{conversation}/messages', [FamilyController::class, 'sendMessage'])->name('conversations.messages.store');
    Route::get('/digest/preview', [FamilyController::class, 'digestPreview'])->name('digest.preview');
});

Route::prefix('content')->name('api.v1.content.')->group(function () {
    Route::get('/articles', [ContentController::class, 'articles'])->name('articles');
    Route::get('/articles/{slug}', [ContentController::class, 'article'])->name('articles.show');
    Route::get('/success-stories', [ContentController::class, 'successStories'])->name('success_stories');
    Route::get('/advice', [ContentController::class, 'advice'])->name('advice');
    Route::get('/expert/questions', [ContentController::class, 'expertQuestions'])->name('expert.questions');
    Route::get('/forums', [ContentController::class, 'forums'])->name('forums');
    Route::get('/forums/{forum}/threads', [ContentController::class, 'threads'])->name('threads');
    Route::get('/threads/{thread}/posts', [ContentController::class, 'posts'])->name('posts');
    Route::get('/webinars', [ContentController::class, 'webinars'])->name('webinars');
    Route::get('/marriage-tips', [ContentController::class, 'marriageTips'])->name('marriage_tips');
    Route::get('/regional-updates', [ContentController::class, 'regionalUpdates'])->name('regional_updates');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/success-stories', [ContentController::class, 'storeSuccessStory'])->name('success_stories.store');
        Route::post('/expert/questions', [ContentController::class, 'storeExpertQuestion'])->name('expert.questions.store');
        Route::post('/forums/{forum}/threads', [ContentController::class, 'storeThread'])->name('threads.store');
        Route::post('/threads/{thread}/posts', [ContentController::class, 'storePost'])->name('posts.store');
        Route::post('/webinars/{webinar}/register', [ContentController::class, 'registerWebinar'])->name('webinars.register');
    });
});

Route::middleware('auth:sanctum')->prefix('safety')->name('api.v1.safety.')->group(function () {
    Route::post('/report', [SafetyController::class, 'report'])->middleware('throttle:10,1')->name('report');
    Route::post('/block', [SafetyController::class, 'block'])->name('block');
    Route::post('/mute', [SafetyController::class, 'mute'])->name('mute');
    Route::post('/restrict', [SafetyController::class, 'restrict'])->name('restrict');
    Route::get('/moderation-cases', [SafetyController::class, 'queue'])->name('moderation_cases');
    Route::post('/moderation-cases/{case}/resolve', [SafetyController::class, 'resolve'])->name('moderation_cases.resolve');
});

Route::middleware('auth:sanctum')->prefix('ai')->name('api.v1.ai.')->group(function () {
    Route::post('/bio', [AiController::class, 'bio'])->middleware('throttle:20,1')->name('bio');
    Route::post('/conversation-starters', [AiController::class, 'conversationStarters'])->middleware('throttle:20,1')->name('conversation_starters');
    Route::post('/profile-quality', [AiController::class, 'profileQuality'])->middleware('throttle:20,1')->name('profile_quality');
    Route::post('/scam-check', [AiController::class, 'scamCheck'])->middleware('throttle:30,1')->name('scam_check');
    Route::post('/red-flag-check', [AiController::class, 'redFlagCheck'])->middleware('throttle:30,1')->name('red_flag_check');
});
