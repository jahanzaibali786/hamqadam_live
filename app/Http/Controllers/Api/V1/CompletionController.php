<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Models\AnalyticsEvent;
use App\Models\RewardTransaction;
use App\Models\MatchSuccess;
use App\Models\SponsoredListing;
use App\Models\SatisfactionSurvey;
use App\Services\ClientScope\AdVisibilityService;
use App\Services\ClientScope\MatchConfirmationService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class CompletionController extends ApiController
{
    public function track(Request $request): JsonResponse
    {
        $data = $request->validate([
            'event_name' => ['required','string','max:100'],
            'feature_key' => ['nullable','string','max:100'],
            'session_key' => ['nullable','string','max:120'],
            'country' => ['nullable','string','max:100'],
            'region' => ['nullable','string','max:100'],
            'city' => ['nullable','string','max:100'],
            'platform' => ['nullable','in:android,ios,web'],
            'source' => ['nullable','string','max:80'],
            'metadata' => ['nullable','array'],
        ]);
        // Client analytics do not count as independently verified business
        // outcomes. Sensitive reward/match events are generated server-side.
        abort_if(in_array($data['event_name'], ['got_match','got_match_confirmed','referral_reward_issued'], true),
            422, 'This event must be recorded by the backend.');
        AnalyticsEvent::create($data + ['user_id' => $request->user()->id,'occurred_at' => now()]);
        return $this->success(null, 'Event recorded.', 201);
    }

    public function nps(Request $request): JsonResponse
    {
        $data = $request->validate([
            'score' => ['required','integer','between:0,10'],
            'comment' => ['nullable','string','max:1500'],
            'context' => ['nullable','in:nps'],
            'platform' => ['nullable','in:android,ios,web'],
        ]);
        $survey = DB::transaction(function () use ($request, $data) {
            \App\Models\User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            if (SatisfactionSurvey::where('user_id', $request->user()->id)->where('context', 'nps')
                ->where('created_at', '>=', now()->subDays(30))->exists()) {
                throw ValidationException::withMessages(['score' => 'An NPS survey was already submitted in the last 30 days.']);
            }
            return SatisfactionSurvey::create([
                'user_id' => $request->user()->id,
                'score' => $data['score'], 'comment' => $data['comment'] ?? null,
                'context' => 'nps', 'platform' => $data['platform'] ?? 'android',
            ]);
        });
        return $this->success($survey, 'Thank you for your feedback.', 201);
    }

    public function gotMatch(Request $request, MatchConfirmationService $confirmations): JsonResponse
    {
        $data = $request->validate([
            'matched_user_id' => ['required','integer','exists:users,id'],
            'note' => ['nullable','string','max:2000'],
        ]);
        // Same authorization, mutually confirmed status and reward logic as web.
        $result = $confirmations->confirm($request->user(), (int) $data['matched_user_id'], $data['note'] ?? null);
        if ($result['created']) {
            AnalyticsEvent::create([
                'user_id' => $request->user()->id,
                'event_name' => 'got_match_confirmed',
                'feature_key' => 'match_tracking',
                'metadata' => ['matched_user_id' => $data['matched_user_id'], 'mutual' => $result['mutual']],
                'platform' => 'android', 'occurred_at' => now(),
            ]);
        }
        return $this->success([
            'confirmation_id' => $result['confirmation']->id,
            'matched_user_id' => (int) $data['matched_user_id'],
            'mutual_confirmation' => $result['mutual'],
            'already_confirmed' => ! $result['created'],
            'reward_issued_now' => $result['rewards_issued'],
            'marriage_verified' => false,
        ], $result['mutual'] ? 'Both members confirmed the match.' : 'Waiting for the other member to confirm.');
    }

    public function sponsored(Request $request, AdVisibilityService $ads): JsonResponse
    {
        $data = $request->validate(['placement' => ['nullable','in:home,discovery,match']]);
        return $this->success($ads->visibleFor($request->user(), $data['placement'] ?? null, 12));
    }

    public function matchConfirmations(Request $request): JsonResponse
    {
        $userId = (int) $request->user()->id;
        $rows = MatchSuccess::query()->where('user_id', $userId)
            ->where('status', 'got_match')->latest()->paginate(20);
        $reciprocalIds = MatchSuccess::query()->where('matched_user_id', $userId)
            ->where('status', 'got_match')
            ->whereIn('user_id', $rows->getCollection()->pluck('matched_user_id'))
            ->pluck('user_id')->map(fn ($id) => (int)$id)->all();
        return $this->success([
            'confirmations' => $rows->getCollection()->map(static fn ($row) => [
                'id' => $row->id,
                'matched_user_id' => (int)$row->matched_user_id,
                'mutual_confirmation' => in_array((int)$row->matched_user_id, $reciprocalIds, true),
                'marriage_verified' => false,
                'confirmed_at' => $row->confirmed_at?->toISOString(),
            ])->all(),
            'pagination' => ['page'=>$rows->currentPage(),'last_page'=>$rows->lastPage(),'total'=>$rows->total()],
        ]);
    }

    public function sponsoredImpression(Request $request, SponsoredListing $sponsor, AdVisibilityService $ads): JsonResponse
    {
        abort_unless($ads->visibleFor($request->user(), $sponsor->placement, 30)
            ->contains('id', $sponsor->id), 404);
        $key = 'sponsor-impression:' . hash('sha256', $request->user()->id.':'.$sponsor->id);
        $counted = Cache::add($key, 1, 3600);
        if ($counted) {
            SponsoredListing::whereKey($sponsor->id)->increment('impressions');
            AnalyticsEvent::create([
                'user_id' => $request->user()->id,
                'event_name' => 'sponsored_impression',
                'feature_key' => 'sponsored_listing',
                'platform' => 'android',
                'metadata' => ['listing_id' => $sponsor->id],
                'occurred_at' => now(),
            ]);
        }
        return $this->success(['counted' => $counted]);
    }

    public function rewards(Request $request): JsonResponse
    {
        $items = RewardTransaction::with('rule')->where('user_id', $request->user()->id)->latest()->paginate(20);
        return ApiResponse::paginated($items);
    }

    public function profileLink(Request $request): JsonResponse
    {
        return $this->success(['url' => route('profile.share', ['user' => $request->user()->id])]);
    }
}
