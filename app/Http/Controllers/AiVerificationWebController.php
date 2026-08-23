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

    /**
     * The screen shown immediately after web registration.
     *
     * The account already exists and the member is already signed in - nothing
     * here can undo that. This only decides where they go next.
     */
    public function gate(Request $request): View|RedirectResponse
    {
        // Already verified? Nothing to wait for.
        if (($request->user()->member?->ai_verification_status) === 'approved') {
            return redirect()->route('dashboard');
        }

        return view('frontend.verification.ai_gate');
    }

    /**
     * Called by the gate screen over AJAX. Runs the model and tells the page
     * where to send the member.
     *
     * APPROVE -> dashboard. Anything else -> signed out and sent to login: the
     * account stays registered but unverified, and the dashboard's verification
     * button is there once they log back in.
     */
    public function runForRegistration(Request $request): JsonResponse
    {
        $user = $request->user()->load('member');
        $result = $this->verifyNow($user);
        $verified = $result['status'] === 'approved';

        if ($verified) {
            return response()->json([
                'verified' => true,
                'status' => $result['status'],
                'recommendation' => $result['recommendation'],
                'title' => translate('Identity verified'),
                'message' => translate('Your identity has been verified. Taking you to your dashboard.'),
                'cta' => translate('Go to dashboard'),
                'redirect' => route('dashboard'),
            ]);
        }

        /*
         * Not verified. Sign them out and send them to login, as required.
         * The flash survives the logout because it is queued on the session
         * before invalidation is skipped - we deliberately do NOT invalidate
         * the session id here, only the auth state, so the message shows.
         */
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
            // The frontend login lives at /users/login (route name `user.login`);
            // `login` is the framework default and not what this app serves.
            'redirect' => route('user.login'),
        ]);
    }

    public function run(Request $request): RedirectResponse
    {
        $result = $this->verifyNow($request->user()->load('member'));

        flash(translate($result['message']))->{$this->flashLevel($result['status'])}();

        return back();
    }

    /**
     * Run the model against whatever the database already holds. Prefers the
     * newest open verification request, which carries the CNIC and selfie from
     * registration step 13, so the model can do a real identity comparison
     * rather than just looking at the profile photo.
     */
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
}
