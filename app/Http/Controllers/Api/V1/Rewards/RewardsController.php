<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Rewards;

use App\Http\Controllers\Api\V1\ApiController;
use App\Services\Api\V1\Rewards\WelcomeBonusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RewardsController extends ApiController
{
    public function __construct(private readonly WelcomeBonusService $bonus)
    {
    }

    /** GET /rewards/welcome — state for the app's Redeem section. */
    public function welcome(Request $request): JsonResponse
    {
        return $this->success($this->bonus->status($request->user()));
    }

    /** POST /rewards/welcome/claim — adds 25 coins once fully verified. */
    public function claimWelcome(Request $request): JsonResponse
    {
        return $this->success(
            $this->bonus->claim($request->user()),
            'Congratulations! Your 25 free coins have been added.'
        );
    }
}
