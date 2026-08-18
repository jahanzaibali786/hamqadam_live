<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AiVerificationAttempt;
use App\Models\ProfileVerificationRequest;
use App\Services\AiVerification\AiVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Member-facing AI verification trigger for the web dashboard.
 *
 * Mirrors Api\V1\Verification\AiVerificationController but returns a redirect
 * with a flash message instead of JSON. Runs synchronously - the member clicked
 * a button and is waiting for an answer.
 */
class AiVerificationWebController extends Controller
{
    public function __construct(private readonly AiVerificationService $verification)
    {
    }

    public function run(Request $request): RedirectResponse
    {
        $user = $request->user()->load('member');

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

        flash(translate($result['message']))->{$this->flashLevel($result['status'])}();

        return back();
    }

    private function flashLevel(string $status): string
    {
        return match ($status) {
            'approved' => 'success',
            'rejected' => 'error',
            'failed' => 'error',
            default => 'warning',
        };
    }
}
