<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Resources\ReferralEarningsResource;
use App\Http\Resources\ReferralResource;
use App\Models\RewardTransaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Referral\ReferralRewardService;
use Illuminate\Http\Request;

/** Backward-compatible mobile referral endpoints: GET /api/referral-*. */
class ReferralController extends Controller
{
    public function __construct(private readonly ReferralRewardService $rewards)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $items = User::where('referred_by', $user->id)->latest()->paginate(15);
        return ReferralResource::collection($items)->additional([
            'result' => true,
            'summary' => $this->rewards->summary($user, true),
        ]);
    }

    public function referral_code(Request $request)
    {
        // Preserve referral_code for old mobile versions; add the shareable link
        // and explicit eligibility/limits for newer builds.
        return $this->response_data($this->rewards->summary($request->user(), true));
    }

    public function referral_earnings(Request $request)
    {
        $user = $request->user();
        $commissions = Wallet::where('payment_method', 'reffered_commission')
            ->where('user_id', $user->id)->latest()->paginate(15);
        $rewardHistory = RewardTransaction::where('user_id', $user->id)
            ->where('event_key', ReferralRewardService::EVENT)
            ->latest()->limit(50)->get(['id','reference_id','coins','created_at']);
        return ReferralEarningsResource::collection($commissions)->additional([
            'result' => true,
            'summary' => $this->rewards->summary($user, true),
            'coin_reward_history' => $rewardHistory,
        ]);
    }

    public function referral_check(Request $request)
    {
        $user = $request->user();
        $data = $this->rewards->summary($user, true);
        if (addon_activation('referral_system') && $user->referred_by !== null
            && (int)($user->referral_comission ?? 0) === 0) {
            $data['referral_discount_type'] = get_setting('referral_user_package_purchase_discount_type');
            $data['referral_discount_amount'] = get_setting('referral_user_package_purchase_discount');
        }
        return $this->response_data($data);
    }
}
