<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Premium;

use App\Http\Controllers\Controller;
use App\Services\ClientScope\PremiumEntitlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PremiumEntitlementController extends Controller
{
    public function index(Request $request, PremiumEntitlementService $entitlements): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $entitlements->payload($request->user())]);
    }
}
