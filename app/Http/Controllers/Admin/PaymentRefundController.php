<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentRefundRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Internal review tracker. A gateway refund must be performed/verified separately. */
final class PaymentRefundController extends Controller
{
    public function index(Request $request): View
    {
        $data = $request->validate(['status' => ['nullable','in:requested,reviewing,rejected,provider_refund_confirmed']]);
        $refundId = $request->integer('refund_id');
        $items = PaymentRefundRequest::query()->with(['user','payment'])
            ->when($refundId > 0, fn($q) => $q->whereKey($refundId))
            ->when(! empty($data['status']), fn($q) => $q->where('status',$data['status']))
            ->latest()->paginate(20)->withQueryString();
        return view('admin.payment_refunds.index', compact('items'));
    }

    /**
     * Records a review decision only. Provider-confirmed Stripe refunds must
     * actually exist at Stripe and belong to the purchased payment intent.
     */
    public function execute(Request $request, PaymentRefundRequest $refund): RedirectResponse
    {
        $request->validate(['confirm' => ['required', 'accepted']]);
        app(\App\Services\Api\V1\Payment\StripeRefundExecutionService::class)->execute($refund, (int) $request->user()->id);
        return back()->with('success', 'Stripe refund attempt submitted. Check provider status before closing the request.');
    }

    public function review(Request $request, PaymentRefundRequest $refund): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required','in:reviewing,rejected,provider_refund_confirmed'],
            'admin_note' => ['nullable','string','max:2000'],
            'provider_refund_reference' => ['required_if:status,provider_refund_confirmed','nullable','string','max:255'],
            'provider_verified' => ['accepted_if:status,provider_refund_confirmed'],
        ]);
        if ($data['status'] === 'provider_refund_confirmed') {
            $payment = $refund->payment;
            if (! $payment || $payment->payment_method !== 'stripe') {
                throw ValidationException::withMessages(['status' => 'Automatic provider verification is available for Stripe only. Leave wallet refunds in review pending supported gateway verification.']);
            }
            $secret = trim((string) (get_setting('STRIPE_SECRET') ?: config('services.stripe.secret', env('STRIPE_SECRET'))));
            if ($secret === '') {
                throw ValidationException::withMessages(['status' => 'Stripe secret key has not been configured.']);
            }
            $refundId = trim((string) $data['provider_refund_reference']);
            if (! str_starts_with($refundId, 're_')) {
                throw ValidationException::withMessages(['provider_refund_reference' => 'Provide the Stripe refund ID starting with re_.']);
            }
            try {
                \Stripe\Stripe::setApiKey($secret);
                $providerRefund = \Stripe\Refund::retrieve($refundId);
                $sessionId = (array) ($payment->metadata ?? []);
                $sessionId = $sessionId['stripe_session_id'] ?? null;
                if (! $sessionId) throw new \RuntimeException('Original Stripe checkout session unavailable.');
                $session = \Stripe\Checkout\Session::retrieve($sessionId);
                $valid = $providerRefund->status === 'succeeded'
                    && (string) $providerRefund->payment_intent === (string) $session->payment_intent
                    && (int) $providerRefund->amount >= (int) round((float) $refund->amount * 100)
                    && strtolower((string) $providerRefund->currency) === strtolower((string) $refund->currency);
                if (! $valid) throw new \RuntimeException('Stripe refund does not match this payment, amount, currency, or a successful refund.');
            } catch (\Throwable $exception) {
                report($exception);
                throw ValidationException::withMessages(['provider_refund_reference' => 'Stripe did not verify this refund against the original charge.']);
            }
        }

        DB::transaction(function () use ($request, $refund, $data): void {
            $locked = PaymentRefundRequest::query()->whereKey($refund->id)->lockForUpdate()->firstOrFail();
            if (in_array($locked->status, ['provider_refund_confirmed', 'rejected'], true)) {
                throw ValidationException::withMessages(['status' => 'This request is already closed and cannot be changed.']);
            }
            $locked->update([
                'status' => $data['status'],
                'admin_note' => $data['admin_note'] ?? null,
                'provider_refund_reference' => $data['provider_refund_reference'] ?? null,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ]);
        }, 3);
        return back()->with('success', 'Refund review saved. No refund was initiated by this page; active subscriptions are unchanged.');
    }

}
