<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AiVerificationAttempt;
use App\Models\ProfileVerificationRequest;
use App\Services\AiVerification\AiVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AiVerificationWebController extends Controller
{
    public function __construct(private readonly AiVerificationService $verification)
    {
    }

    public function gate(Request $request): View|RedirectResponse
    {
        if (($request->user()->member?->ai_verification_status) === 'approved') {
            return redirect()->route('dashboard');
        }

        return view('frontend.verification.ai_gate', [
            'verificationStatus' => $this->verification->statusFor($request->user()->load('member')),
        ]);
    }

    public function runForRegistration(Request $request): JsonResponse
    {
        $user = $request->user()->load('member');
        $before = AiVerificationAttempt::where('user_id', $user->id)->latest('id')->first();
        $result = $this->verifyNow($user);
        $verified = $result['status'] === 'approved';
        $latestAttempt = AiVerificationAttempt::where('user_id', $user->id)->latest('id')->first();

        if ($verified) {
            return response()->json([
                'verified' => true,
                'status' => $result['status'],
                'recommendation' => $result['recommendation'],
                'title' => translate('Identity verified'),
                'message' => translate('Your identity has been verified. Taking you to your dashboard.'),
                'cta' => translate('Go to dashboard'),
                'redirect' => route('dashboard'),
                'attempt_before' => $before?->id,
                'attempt_after' => $latestAttempt?->id,
                'logs' => $this->attemptLogs($latestAttempt),
            ]);
        }

        $message = $result['status'] === 'failed'
            ? translate('We could not reach the verification service. Your account is created - please log in and finish verification from your dashboard.')
            : translate('Your account is created but not yet verified. Please log in and complete verification from your dashboard.');

        Auth::guard('web')->logout();
        session()->flash('ai_verification_notice', $message);

        return response()->json([
            'verified' => false,
            'status' => $result['status'],
            'recommendation' => $result['recommendation'],
            'title' => translate('Verification not completed'),
            'message' => $message,
            'cta' => translate('Go to login'),
            'redirect' => route('user.login'),
            'attempt_before' => $before?->id,
            'attempt_after' => $latestAttempt?->id,
            'logs' => $this->attemptLogs($latestAttempt),
        ]);
    }

    public function run(Request $request): RedirectResponse
    {
        $result = $this->verifyNow($request->user()->load('member'));

        flash(translate($result['message']))->{$this->flashLevel($result['status'])}();

        return back();
    }

    private function verifyNow($user): array
    {
        $pending = ProfileVerificationRequest::with(['documents', 'user'])
            ->where('user_id', $user->id)
            ->whereIn('status', ['draft', 'submitted', 'under_review'])
            ->latest('id')
            ->first();

        return $this->verification->verifyUser(
            $user,
            AiVerificationAttempt::SOURCE_MANUAL_RETRY,
            $pending
        );
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

    private function attemptLogs(?AiVerificationAttempt $attempt): array
    {
        if (! $attempt) {
            return [
                ['level' => 'info', 'message' => 'Verification request not yet created.'],
            ];
        }

        $logs = [
            ['level' => 'info', 'message' => 'AI verification request created.'],
            ['level' => $attempt->status === AiVerificationAttempt::STATUS_COMPLETED ? 'success' : ($attempt->status === AiVerificationAttempt::STATUS_FAILED ? 'danger' : 'warning'), 'message' => 'Current attempt status: '.$attempt->status.'.'],
        ];

        if ($attempt->http_status) {
            $logs[] = ['level' => 'info', 'message' => 'HTTP status: '.$attempt->http_status.'.'];
        }

        if ($attempt->recommendation) {
            $logs[] = ['level' => 'success', 'message' => 'Model recommendation: '.$attempt->recommendation.'.'];
        }

        if ($attempt->identity_confidence_score !== null) {
            $logs[] = ['level' => 'info', 'message' => 'Identity confidence score: '.$attempt->identity_confidence_score.'.'];
        }

        if ($attempt->fraud_risk_level) {
            $logs[] = ['level' => 'warning', 'message' => 'Fraud risk level: '.$attempt->fraud_risk_level.'.'];
        }

        if ($attempt->error_message) {
            $logs[] = ['level' => 'danger', 'message' => $attempt->error_message];
        }

        return $logs;
    }
}
