<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Payment;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\PackagePayment;
use App\Models\PaymentRefundRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** An audit request; this endpoint never initiates a provider refund. */
final class RefundRequestController extends ApiController
{
    public function store(Request $request, int $payment): JsonResponse
    {
        $data = $request->validate(['reason' => ['required','string','min:10','max:2000']]);
        $record = DB::transaction(function () use ($request, $payment, $data) {
            $purchase = PackagePayment::query()->where('user_id', $request->user()->id)
                ->whereKey($payment)->lockForUpdate()->firstOrFail();
            if ($purchase->payment_status !== 'Paid' || (float)$purchase->payable_amount <= 0) {
                throw ValidationException::withMessages(['payment' => 'Only paid purchases with a positive charge qualify for review.']);
            }
            $existing = PaymentRefundRequest::query()->where('package_payment_id', $purchase->id)->first();
            if ($existing) return $existing;
            return PaymentRefundRequest::query()->create([
                'package_payment_id' => $purchase->id,
                'user_id' => $request->user()->id,
                'status' => 'requested',
                'amount' => $purchase->payable_amount,
                'currency' => $purchase->currency ?: 'PKR',
                'reason' => $data['reason'],
            ]);
        });
        return $this->success([
            'id' => $record->id, 'status' => $record->status,
            'amount' => $record->amount, 'currency' => $record->currency,
        ], 'Refund review request recorded. No funds have been returned.');
    }

    public function index(Request $request): JsonResponse
    {
        return $this->success(PaymentRefundRequest::query()
            ->where('user_id', $request->user()->id)->latest()->paginate(20));
    }
}
