<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ModerationCaseStatus;
use App\Enums\ProposalStatus;
use App\Enums\VerificationRequestStatus;
use App\Models\AiFeatureRequest;
use App\Models\ExpressInterest;
use App\Models\FamilyApprovalRequest;
use App\Models\FamilyGuardianLink;
use App\Models\ModerationCase;
use App\Models\PackagePayment;
use App\Models\ProfileMatch;
use App\Models\ProfileVerificationRequest;
use App\Models\SafetyAction;
use App\Models\SavedSearch;
use App\Models\Setting;
use App\Models\User;
use App\Services\Api\V1\Profile\ProfileCompletionService;
use App\Support\RegistrationReward;
use Artisan;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class V1PlatformConsoleController extends Controller
{
    private const MATCHMAKING_SETTINGS = [
        'ai_matchmaking_enabled' => 1,
        'ai_match_minimum_score' => 50,
        'ai_match_daily_recommendation_limit' => 20,
        'ai_match_trait_overlap_minimum' => 1,
        'ai_match_cold_start_minimum_signals' => 4,
        'search_online_window_minutes' => 15,
        'proposal_expiry_days' => 14,
        'ai_match_personality_enabled' => 1,
        'ai_match_emotional_enabled' => 1,
        'ai_match_religious_enabled' => 1,
        'ai_match_lifestyle_enabled' => 1,
        'ai_match_communication_enabled' => 1,
        'ai_match_long_term_enabled' => 1,
        'ai_match_mutual_interest_enabled' => 1,
        'ai_match_cold_start_enabled' => 1,
        'ai_match_collaborative_filtering_enabled' => 1,
        'ai_match_recency_boost_points' => 3,
        'ai_match_photo_attractiveness_enabled' => 0,
        'ai_match_voice_tone_enabled' => 0,
        'ai_match_context_aware_enabled' => 0,
        'ai_match_dormant_reengagement_enabled' => 0,
        'ai_match_weight_religion' => 20,
        'ai_match_weight_lifestyle' => 15,
        'ai_match_weight_education' => 15,
        'ai_match_weight_profession' => 10,
        'ai_match_weight_income' => 10,
        'ai_match_weight_age' => 10,
        'ai_match_weight_prayer' => 10,
        'ai_match_weight_language' => 5,
        'ai_match_weight_location' => 5,
        'ai_match_weight_behavior' => 10,
        'ai_match_weight_personality' => 10,
        'ai_match_weight_emotional' => 8,
        'ai_match_weight_communication' => 7,
        'ai_match_weight_long_term' => 10,
        'ai_match_weight_mutual_interest' => 5,
        'ai_match_weight_cold_start' => 5,
    ];

    public function admin(): View
    {
        return view('admin.v1_platform.index', [
            'members' => [
                'total' => User::where('user_type', 'member')->count(),
                'pending' => User::where('user_type', 'member')->where('approved', 0)->count(),
                'blocked' => User::where('user_type', 'member')->where('blocked', 1)->count(),
                'hidden_profiles' => User::where('user_type', 'member')
                    ->whereHas('member', fn ($query) => $query->where('hide_profile', true))
                    ->count(),
            ],
            'queues' => [
                'verification' => ProfileVerificationRequest::whereIn('status', [
                    VerificationRequestStatus::Submitted->value,
                    VerificationRequestStatus::UnderReview->value,
                ])->count(),
                'moderation' => ModerationCase::whereIn('status', [
                    ModerationCaseStatus::Open->value,
                    ModerationCaseStatus::UnderReview->value,
                ])->count(),
                'family' => FamilyApprovalRequest::where('status', 'pending')->count(),
            ],
            'payments' => [
                'paid' => PackagePayment::where('payment_status', 'Paid')->count(),
                'due' => PackagePayment::where('payment_status', 'Due')->count(),
                'revenue' => (float) PackagePayment::where('payment_status', 'Paid')->sum('payable_amount'),
            ],
            'ai' => [
                'today' => AiFeatureRequest::whereDate('created_at', today())->count(),
                'pending' => AiFeatureRequest::whereIn('status', ['pending', 'processing'])->count(),
                'total' => AiFeatureRequest::count(),
            ],
            'recentModerationCases' => ModerationCase::with(['reportedUser', 'reporter'])->latest()->limit(8)->get(),
            'recentVerifications' => ProfileVerificationRequest::with('user')->latest()->limit(8)->get(),
            'recentAiRequests' => AiFeatureRequest::latest()->limit(8)->get(),
        ]);
    }

    public function matchmakingSettings(): View
    {
        return view('admin.v1_platform.matchmaking_settings', [
            'defaults' => self::MATCHMAKING_SETTINGS,
            'weights' => [
                'religion' => 'Religious Compatibility System',
                'lifestyle' => 'Lifestyle Compatibility Detection',
                'education' => 'Education Compatibility',
                'profession' => 'Profession Compatibility',
                'income' => 'Income Compatibility',
                'age' => 'Age Preference',
                'prayer' => 'Prayer / Religious Practice',
                'language' => 'Language Compatibility',
                'location' => 'Location Compatibility',
                'behavior' => 'Behavioral Analysis',
                'personality' => 'Personality Matching Engine',
                'emotional' => 'Emotional Compatibility Analysis',
                'communication' => 'Communication Style Analysis',
                'long_term' => 'Long-Term Match Prediction',
                'mutual_interest' => 'Mutual Interest Predictions',
                'cold_start' => 'Cold Start Handling',
            ],
            'features' => [
                'ai_match_personality_enabled' => 'Personality Matching Engine',
                'ai_match_emotional_enabled' => 'Emotional Compatibility Analysis',
                'ai_match_religious_enabled' => 'Religious Compatibility System',
                'ai_match_lifestyle_enabled' => 'Lifestyle Compatibility Detection',
                'ai_match_communication_enabled' => 'Communication Style Analysis',
                'ai_match_long_term_enabled' => 'Long-Term Match Prediction',
                'ai_match_mutual_interest_enabled' => 'Mutual Interest Predictions',
                'ai_match_cold_start_enabled' => 'Cold Start Handling',
                'ai_match_collaborative_filtering_enabled' => 'Collaborative Filtering',
                'ai_match_photo_attractiveness_enabled' => 'Photo Attractiveness Score (Opt-in only)',
                'ai_match_voice_tone_enabled' => 'Voice Tone Analysis',
                'ai_match_context_aware_enabled' => 'Context-Aware Matching',
                'ai_match_dormant_reengagement_enabled' => 'Dormant User Re-engagement Prediction',
            ],
        ]);
    }

    public function updateMatchmakingSettings(Request $request)
    {
        $validated = $request->validate([
            'ai_matchmaking_enabled' => ['sometimes', 'boolean'],
            'ai_match_minimum_score' => ['required', 'integer', 'min:0', 'max:100'],
            'ai_match_daily_recommendation_limit' => ['required', 'integer', 'min:1', 'max:100'],
            'ai_match_trait_overlap_minimum' => ['required', 'integer', 'min:1', 'max:10'],
            'ai_match_cold_start_minimum_signals' => ['required', 'integer', 'min:1', 'max:20'],
            'search_online_window_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'proposal_expiry_days' => ['required', 'integer', 'min:1', 'max:365'],
            'ai_match_recency_boost_points' => ['required', 'integer', 'min:0', 'max:20'],
        ]);

        foreach (self::MATCHMAKING_SETTINGS as $key => $default) {
            if (str_starts_with($key, 'ai_match_weight_')) {
                $validated[$key] = $request->validate([
                    $key => ['required', 'integer', 'min:0', 'max:100'],
                ])[$key];
                continue;
            }

            if (str_ends_with($key, '_enabled') || $key === 'ai_matchmaking_enabled') {
                $validated[$key] = $request->has($key) ? 1 : 0;
            }
        }

        foreach ($validated as $key => $value) {
            Setting::updateOrCreate(['type' => $key], ['value' => $value]);
        }

        Artisan::call('cache:clear');

        flash(translate('AI matchmaking settings updated successfully'))->success();

        return back();
    }

    public function member(Request $request): View
    {
        $user = $request->user()->loadMissing('member.package');
        $profileCompletion = app(ProfileCompletionService::class)->calculate($user);
        $latestVerification = ProfileVerificationRequest::where('user_id', $user->id)->latest()->first();
        $verificationStatus = $this->resolveMemberVerificationStatus($user, $latestVerification);

        if ($user->member && (int) $user->member->profile_completion_percentage !== $profileCompletion) {
            $user->member->forceFill([
                'profile_completion_percentage' => $profileCompletion,
            ])->save();
        }

        return view('frontend.member.v1_dashboard', [
            'profileCompletion' => $profileCompletion,
            'verification' => $latestVerification,
            'verificationStatus' => $verificationStatus,
            'topMatches' => ProfileMatch::with('matchedUser.member')
                ->where('user_id', $user->id)
                ->orderByDesc('match_percentage')
                ->limit(5)
                ->get(),
            'proposalStats' => [
                'sent_pending' => ExpressInterest::where('interested_by', $user->id)
                    ->where('status', ProposalStatus::Pending->value)
                    ->count(),
                'received_pending' => ExpressInterest::where('user_id', $user->id)
                    ->where('status', ProposalStatus::Pending->value)
                    ->count(),
                'accepted' => ExpressInterest::where(function ($query) use ($user): void {
                    $query->where('user_id', $user->id)->orWhere('interested_by', $user->id);
                })->where('status', ProposalStatus::Accepted->value)->count(),
            ],
            'currentPackage' => $user->member?->package,
            'recommendedPackage' => RegistrationReward::nextRecommendedPackage($user->member?->package),
            'latestPayment' => PackagePayment::where('user_id', $user->id)->latest()->first(),
            'savedSearches' => SavedSearch::where('user_id', $user->id)->latest()->limit(5)->get(),
            'familyLinks' => FamilyGuardianLink::where('profile_user_id', $user->id)
                ->orWhere('guardian_user_id', $user->id)
                ->latest()
                ->limit(5)
                ->get(),
            'safetyActions' => SafetyAction::where('actor_user_id', $user->id)->latest()->limit(5)->get(),
            'recentAiRequests' => AiFeatureRequest::where('user_id', $user->id)->latest()->limit(5)->get(),
        ]);
    }

    private function resolveMemberVerificationStatus(User $user, ?ProfileVerificationRequest $latestVerification): string
    {
        if ((int) $user->approved === 1 || $user->member?->verification_status === 'verified') {
            return 'verified';
        }

        if ($latestVerification) {
            $status = $latestVerification->status instanceof \BackedEnum
                ? $latestVerification->status->value
                : (string) $latestVerification->status;

            return $status;
        }

        if (! empty($user->verification_info) || $user->member?->verification_status === 'submitted') {
            return 'submitted';
        }

        if ($user->email_verified_at) {
            return get_setting('member_verification') ? 'email_verified' : 'verified';
        }

        return 'not_submitted';
    }
}
