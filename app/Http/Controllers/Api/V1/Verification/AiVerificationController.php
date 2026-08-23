<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Verification;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\AiVerificationAttempt;
use App\Models\ProfileVerificationRequest;
use App\Services\AiVerification\AiVerificationClient;
use App\Services\AiVerification\AiVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Standalone AI verification endpoints.
 *
 * These exist for the case the brief called out: registration succeeded but
 * verification did not complete - the model was down, the account had no
 * usable photo, or the result came back MANUAL_REVIEW. Nothing here takes an
 * image upload; the payload for the model is rebuilt from what is already in
 * the database.
 */
class AiVerificationController extends ApiController
{
    public function __construct(
        private readonly AiVerificationService $verification,
        private readonly AiVerificationClient $client,
    ) {
    }

    /** GET /api/v1/verification/ai/status */
    public function status(Request $request): JsonResponse
    {
        return $this->success(
            $this->verification->statusFor($request->user()->load('member'))
        );
    }

    /**
     * POST /api/v1/verification/ai/run
     *
     * Runs synchronously: the caller explicitly asked for a verification and
     * wants the outcome in the response, unlike the registration hook which
     * must not make anybody wait. Throttled at the route.
     */
    public function run(Request $request): JsonResponse
    {
        $user = $request->user()->load('member');

        // Prefer the newest non-final document request: it carries the CNIC and
        // selfie, which lets the model do a real identity comparison instead of
        // just looking at the profile photo.
        $pending = ProfileVerificationRequest::with(['documents', 'user'])
            ->where('user_id', $user->id)
            ->whereIn('status', ['draft', 'submitted', 'under_review'])
            ->latest('id')
            ->first();

        $result = $this->verification->verifyUser(
            $user,
            AiVerificationAttempt::SOURCE_MANUAL_RETRY,
            $pending
        );

        $payload = array_merge($result, [
            'used_documents' => $pending !== null,
            'service_reachable' => $this->client->healthy(),
        ]);

        // A skipped or failed attempt is not a client error - the account
        // simply is not verifiable yet - so keep it 200 with a clear status
        // rather than making the app treat it as a broken request.
        return $this->success($payload, $result['message']);
    }

    /** GET /api/v1/verification/ai/history */
    public function history(Request $request): JsonResponse
    {
        $attempts = AiVerificationAttempt::where('user_id', $request->user()->id)
            ->latest('id')
            ->limit(20)
            ->get()
            ->map(fn (AiVerificationAttempt $a) => [
                'id' => $a->id,
                'source' => $a->source,
                'status' => $a->status,
                'recommendation' => $a->recommendation,
                'identity_confidence_score' => $a->identity_confidence_score,
                'fraud_risk_score' => $a->fraud_risk_score,
                'fraud_risk_level' => $a->fraud_risk_level,
                'face_detected' => $a->face_detected,
                'images_sent' => $a->images_sent,
                'error_message' => $a->error_message,
                'created_at' => $a->created_at?->toISOString(),
            ]);

        return $this->success(['attempts' => $attempts]);
    }
}
