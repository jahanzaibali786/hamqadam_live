<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\ApiErrorCode;
use App\Exceptions\ApiException;
use App\Http\Controllers\Api\V1\ApiController;
use App\Models\AiFeatureRequest;
use App\Models\FamilyApprovalRequest;
use App\Models\ModerationCase;
use App\Models\PackagePayment;
use App\Models\ProfileVerificationRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminOverviewController extends ApiController
{
    public function __invoke(Request $request): JsonResponse
    {
        if ($request->user()->user_type === 'member') {
            throw new ApiException('Only admin or staff users can access the admin overview.', 403, ApiErrorCode::Forbidden->value);
        }

        return $this->success([
            'users' => [
                'total_members' => User::where('user_type', 'member')->count(),
                'blocked_members' => User::where('user_type', 'member')->where('blocked', 1)->count(),
                'pending_approval' => User::where('user_type', 'member')->where('approved', 0)->count(),
            ],
            'queues' => [
                'verification_pending' => ProfileVerificationRequest::whereIn('status', ['submitted', 'under_review'])->count(),
                'moderation_open' => ModerationCase::whereIn('status', ['open', 'under_review'])->count(),
                'family_approval_pending' => FamilyApprovalRequest::where('status', 'pending')->count(),
            ],
            'payments' => [
                'paid_count' => PackagePayment::where('payment_status', 'Paid')->count(),
                'due_count' => PackagePayment::where('payment_status', 'Due')->count(),
                'paid_amount' => (float) PackagePayment::where('payment_status', 'Paid')->sum('payable_amount'),
            ],
            'ai' => [
                'requests_today' => AiFeatureRequest::whereDate('created_at', today())->count(),
                'total_requests' => AiFeatureRequest::count(),
            ],
        ]);
    }
}
