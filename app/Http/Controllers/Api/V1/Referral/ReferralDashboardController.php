<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Referral;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\RewardTransaction;
use App\Models\User;
use App\Services\Referral\ReferralRewardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Authenticated /api/v1/bridge/referrals endpoints for mobile consumers. */
class ReferralDashboardController extends ApiController
{
    public function __construct(private readonly ReferralRewardService $rewards)
    {
    }

    public function summary(Request $request): JsonResponse
    {
        return $this->success($this->rewards->summary($request->user(), true));
    }

    public function referredUsers(Request $request): JsonResponse
    {
        $user = $request->user();
        $people = User::query()->where('referred_by', $user->id)->latest('id')
            ->paginate(max(1,min(50,(int)$request->integer('per_page',15))),
                ['id','first_name','last_name','created_at','email_verified_at','approved']);
        $rewarded = RewardTransaction::where('user_id', $user->id)
            ->where('event_key', ReferralRewardService::EVENT)->where('reference_type','referred_user')
            ->whereIn('reference_id', $people->getCollection()->pluck('id'))->pluck('reference_id')
            ->map(fn ($id) => (int) $id)->all();
        $packageRewarded=RewardTransaction::where('user_id',$user->id)->where('event_key',\App\Services\Referral\PaidPackageReferralRewardService::EVENT)->where('reference_type','referred_user')->whereIn('reference_id',$people->getCollection()->pluck('id'))->pluck('reference_id')->map(fn($id)=>(int)$id)->all();
        return $this->success([
            'referrals' => $people->getCollection()->map(static fn(User $person) => [
                'id' => $person->id,
                'name' => trim($person->first_name.' '.$person->last_name),
                'joined_at' => $person->created_at?->toISOString(),
                'email_verified' => $person->email_verified_at !== null,
                'reward_credited' => in_array((int)$person->id, $rewarded, true),
                'registration_reward_credited'=>in_array((int)$person->id,$rewarded,true),
                'package_activation_reward_credited'=>in_array((int)$person->id,$packageRewarded,true),
            ])->all(),
            'pagination' => ['page' => $people->currentPage(), 'last_page' => $people->lastPage(), 'total' => $people->total()],
            'summary' => $this->rewards->summary($user, false),
        ]);
    }

    public function rewardHistory(Request $request): JsonResponse
    {
        $records = RewardTransaction::query()->where('user_id', $request->user()->id)
            ->whereIn('event_key',[ReferralRewardService::EVENT, \App\Services\Referral\PaidPackageReferralRewardService::EVENT])->latest('id')
            ->paginate(max(1,min(50,(int)$request->integer('per_page',15))));
        return $this->success([
            'transactions' => $records->getCollection()->map(static fn ($row) => [
                'id' => $row->id, 'coins' => $row->coins,
                'referred_user_id' => $row->reference_id,'reward_type'=>$row->event_key===\App\Services\Referral\PaidPackageReferralRewardService::EVENT?'paid_package_activation':'registration',
                'created_at' => $row->created_at?->toISOString(),
            ])->all(),
            'pagination' => ['page' => $records->currentPage(), 'last_page' => $records->lastPage(), 'total' => $records->total()],
            'summary' => $this->rewards->summary($request->user(), false),
        ]);
    }
}
