<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Premium;

use App\Http\Controllers\Api\V1\ApiController;
use App\Services\ClientScope\MessagingAllowanceService;
use App\Services\ClientScope\ProfileBoostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BoostController extends ApiController
{
    public function status(Request $request, ProfileBoostService $boost, MessagingAllowanceService $messages): JsonResponse
    {
        return $this->success([
            'profile_boost' => $boost->current($request->user()),
            'messages_left_today' => $messages->remaining($request->user()),
            'message_limit_note' => 'null means Unlimited Messaging is enabled for an active paid plan.',
        ]);
    }
    public function activate(Request $request, ProfileBoostService $boost): JsonResponse
    {
        return $this->success($boost->activate($request->user()), 'Profile boost activated.', 201);
    }
}
