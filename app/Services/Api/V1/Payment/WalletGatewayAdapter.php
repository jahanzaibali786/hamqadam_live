<?php

declare(strict_types=1);

namespace App\Services\Api\V1\Payment;

use App\Enums\PaymentGateway;
use App\Exceptions\ApiException;
use App\Models\PackagePayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * Gateway-specific checkout payloads and independently verified payment events.
 * Never trust a browser redirect or an arbitrary posted `status=paid`.
 */
class WalletGatewayAdapter
{
    private function option(string $key): string
    {
        $value = get_setting($key);
        return trim((string) (filled($value) ? $value : env($key, '')));
    }

    /** Hosted JazzCash Payment Portal (v1.1). The frontend POSTs fields to url. */
    public function jazzcashCheckout(PackagePayment $payment): array
    {
        $merchant = $this->option('JAZZCASH_MERCHANT_ID');
        $password = $this->option('JAZZCASH_PASSWORD');
        $salt = $this->option('JAZZCASH_INTEGRITY_SALT');
        if ($merchant === '' || $password === '' || $salt === '') {
            throw new ApiException('JazzCash merchant ID, password and integrity salt are required.', 422, 'gateway_not_configured');
        }
        if (strtoupper((string) $payment->currency) !== 'PKR') {
            throw new ApiException('JazzCash requires PKR currency.', 422, 'unsupported_currency');
        }
        $reference = 'HQ' . str_pad((string) $payment->id, 10, '0', STR_PAD_LEFT) . now()->format('His');
        $fields = [
            'pp_Version' => '1.1',
            'pp_TxnType' => 'MWALLET',
            'pp_Language' => 'EN',
            'pp_MerchantID' => $merchant,
            'pp_Password' => $password,
            'pp_TxnRefNo' => $reference,
            'pp_Amount' => (string) round((float) $payment->payable_amount * 100),
            'pp_TxnCurrency' => 'PKR',
            'pp_TxnDateTime' => now()->format('YmdHis'),
            'pp_TxnExpiryDateTime' => now()->addMinutes(30)->format('YmdHis'),
            'pp_BillReference' => (string) $payment->payment_code,
            'pp_Description' => 'Hamqadam package payment',
            'pp_ReturnURL' => url('/api/v1/payments/webhooks/jazzcash'),
        ];
        $fields['pp_SecureHash'] = $this->jazzcashHash($fields, $salt);
        $metadata = (array) ($payment->metadata ?? []);
        $metadata['jazzcash_txn_ref'] = $reference;
        $payment->forceFill(['metadata' => $metadata])->save();
        // This URL is configurable because JazzCash assigns merchant-specific portal environments.
        $url = $this->option('JAZZCASH_ENDPOINT');
        if (! filter_var($url, FILTER_VALIDATE_URL) || ! str_starts_with(strtolower($url), 'https://')) {
            throw new ApiException('Set the HTTPS JazzCash payment portal URL in admin settings.', 422, 'gateway_not_configured');
        }
        return ['mode' => 'hosted_form', 'method' => 'POST', 'url' => $url, 'fields' => $fields, 'gateway_reference' => $reference];
    }

    private function jazzcashHash(array $fields, string $salt): string
    {
        unset($fields['pp_SecureHash']);
        ksort($fields, SORT_STRING);
        $parts = array_values(array_filter($fields, static fn ($value) => $value !== null && $value !== ''));
        return strtoupper(hash_hmac('sha256', $salt . '&' . implode('&', $parts), $salt));
    }

    /** Verify provider signature, merchant, amount, reference, and success response. */
    public function verifyJazzcash(Request $request): array
    {
        $data = $request->all();
        $signature = (string) ($data['pp_SecureHash'] ?? '');
        $merchant = (string) ($data['pp_MerchantID'] ?? '');
        if ($signature === '' || $merchant === '' || ! hash_equals($this->option('JAZZCASH_MERCHANT_ID'), $merchant)) {
            throw new ApiException('Unverified JazzCash callback.', 403, 'invalid_gateway_signature');
        }
        $salt = $this->option('JAZZCASH_INTEGRITY_SALT');
        if ($salt === '' || ! hash_equals(strtolower($this->jazzcashHash($data, $salt)), strtolower($signature))) {
            throw new ApiException('Invalid JazzCash response signature.', 403, 'invalid_gateway_signature');
        }
        $reference = (string) ($data['pp_TxnRefNo'] ?? '');
        $payment = PackagePayment::where('payment_method', 'jazzcash')->where('metadata->jazzcash_txn_ref', $reference)->first();
        if (! $payment || (int) ($data['pp_Amount'] ?? -1) !== (int) round((float) $payment->payable_amount * 100) || strtoupper((string) ($data['pp_TxnCurrency'] ?? '')) !== 'PKR') {
            throw new ApiException('JazzCash transaction does not match the expected payment.', 422, 'gateway_transaction_mismatch');
        }
        return [
            'event_id' => 'jazzcash_' . $reference . '_' . ($data['pp_ResponseCode'] ?? 'unknown'),
            'event_type' => 'jazzcash.response',
            'payment_code' => $payment->payment_code,
            'gateway_reference' => $payment->gateway_reference,
            'status' => (string) ($data['pp_ResponseCode'] ?? '') === '000' ? 'paid' : 'failed',
            'amount' => $payment->payable_amount,
            'payload' => $data,
        ];
    }

    /** Easypaisa v4 direct API status inquiry, not browser-supplied success. */
    public function verifyEasypaisa(Request $request): array
    {
        $reference = (string) ($request->input('orderId') ?: $request->input('orderRefNumber') ?: $request->input('gateway_reference'));
        $payment = PackagePayment::where('payment_method', 'easypaisa')->where('gateway_reference', $reference)->first();
        if (! $payment) {
            throw new ApiException('Unknown Easypaisa transaction.', 404, 'payment_not_found');
        }
        $username = $this->option('EASYPAISA_USERNAME');
        $password = $this->option('EASYPAISA_PASSWORD');
        $storeId = $this->option('EASYPAISA_STORE_ID');
        $account = $this->option('EASYPAISA_ACCOUNT_MSISDN');
        if ($username === '' || $password === '' || $storeId === '' || $account === '') {
            throw new ApiException('Easypaisa status inquiry credentials are not configured.', 503, 'gateway_not_configured');
        }
        $base = $this->option('EASYPAISA_ENDPOINT');
        if (! filter_var($base, FILTER_VALIDATE_URL) || ! str_starts_with(strtolower($base), 'https://')) {
            throw new ApiException('Configure the HTTPS Easypaisa v4 inquiry endpoint.', 503, 'gateway_not_configured');
        }
        $response = Http::timeout(15)->withHeaders(['Credentials' => base64_encode($username . ':' . $password)])
            ->acceptJson()->post($base, ['orderId' => $reference, 'storeId' => $storeId, 'accountNum' => $account]);
        if (! $response->successful() || ! is_array($response->json())) {
            throw new ApiException('Easypaisa inquiry did not return a verifiable transaction.', 503, 'gateway_inquiry_failed');
        }
        $result = $response->json();
        if ((string) ($result['orderId'] ?? '') !== $reference || (string) ($result['storeId'] ?? '') !== $storeId) {
            throw new ApiException('Easypaisa inquiry response reference mismatch.', 422, 'gateway_transaction_mismatch');
        }
        // Fail closed: provider-specific '0000' success must be confirmed by transaction status.
        $paid = (string) ($result['responseCode'] ?? '') === '0000'
            && in_array(strtoupper((string) ($result['transactionStatus'] ?? $result['status'] ?? '')), ['PAID', 'SUCCESS', 'COMPLETED'], true);
        if (! $paid) {
            throw new ApiException('Easypaisa payment is not independently confirmed paid.', 409, 'payment_not_confirmed');
        }
        if (! isset($result['amount']) || abs((float) $result['amount'] - (float) $payment->payable_amount) > 0.009) {
            throw new ApiException('Easypaisa inquiry amount mismatch or missing.', 422, 'gateway_transaction_mismatch');
        }
        return [
            'event_id' => 'easypaisa_' . $reference . '_paid',
            'event_type' => 'easypaisa.inquiry.verified',
            'payment_code' => $payment->payment_code,
            'gateway_reference' => $reference,
            'status' => 'paid',
            'amount' => $payment->payable_amount,
            'payload' => $result,
        ];
    }
}
