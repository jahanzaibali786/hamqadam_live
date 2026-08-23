<?php

declare(strict_types=1);

namespace App\Services\AiVerification;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Thin HTTP client for the Hamqadam AI Identity Verification service.
 *
 * Docs: https://ai-modals.hamqadam.com/api-docs
 *
 * POST /v1/verify is multipart/form-data. Only `verification_id` is required by
 * the schema, but the service rejects a request with no images
 * ("A verification needs at least a live selfie"), so callers must supply at
 * least live_selfie.
 *
 * Authentication is X-API-Key, NOT the gateway Bearer token. The Bearer token
 * guards /api/*, /qdrant/* and the docs pages; /v1/* is guarded by the
 * service's own key.
 *
 * This class NEVER throws for a failed verification. It returns a result array
 * so callers can record the failure without breaking the flow that called them.
 */
class AiVerificationClient
{
    /**
     * @param  array<string,string>  $images  field name => absolute file path
     * @return array{ok:bool,status:int|null,body:array,error:string|null,error_code:string|null,duration_ms:int}
     */
    public function verify(string $verificationId, array $images, array $extra = []): array
    {
        $started = microtime(true);

        if (! config('ai_verification.enabled')) {
            return $this->fail('AI verification is disabled by configuration.', 'disabled', null, $started);
        }

        $apiKey = (string) config('ai_verification.api_key');
        if ($apiKey === '') {
            return $this->fail('AI_VERIFICATION_API_KEY is not configured.', 'missing_api_key', null, $started);
        }

        if ($images === []) {
            return $this->fail('No readable images available for this user.', 'no_images', null, $started);
        }

        $url = config('ai_verification.base_url').'/v1/verify';

        try {
            $request = Http::asMultipart()
                ->withHeaders(['X-API-Key' => $apiKey])
                ->timeout((int) config('ai_verification.timeout'))
                ->connectTimeout((int) config('ai_verification.connect_timeout'))
                // Retry transport-level failures only. A 4xx is a real answer
                // about our payload, so retrying it just wastes time.
                ->retry(
                    max(1, (int) config('ai_verification.retries')),
                    (int) config('ai_verification.retry_delay_ms'),
                    fn ($exception) => $exception instanceof ConnectionException,
                    throw: false
                );

            $payload = array_merge(['verification_id' => $verificationId], $extra);
            foreach ($payload as $name => $value) {
                if ($value === null || $value === '') {
                    continue;
                }
                $request = $request->attach($name, is_bool($value) ? ($value ? 'true' : 'false') : (string) $value);
            }

            foreach ($images as $field => $path) {
                $handle = @fopen($path, 'rb');
                if ($handle === false) {
                    Log::warning('ai_verification.image_unreadable', ['field' => $field, 'path' => $path]);
                    continue;
                }
                $request = $request->attach($field, $handle, basename($path));
            }

            $response = $request->post($url);
            $body = $response->json() ?? [];

            if ($response->successful()) {
                return [
                    'ok' => true,
                    'status' => $response->status(),
                    'body' => is_array($body) ? $body : [],
                    'error' => null,
                    'error_code' => null,
                    'duration_ms' => $this->ms($started),
                ];
            }

            // The service returns {"error":{"code":..,"message":..}} on 4xx.
            $err = is_array($body) ? ($body['error'] ?? []) : [];

            return [
                'ok' => false,
                'status' => $response->status(),
                'body' => is_array($body) ? $body : [],
                'error' => (string) ($err['message'] ?? ('HTTP '.$response->status())),
                'error_code' => (string) ($err['code'] ?? 'http_'.$response->status()),
                'duration_ms' => $this->ms($started),
            ];
        } catch (Throwable $e) {
            Log::warning('ai_verification.request_failed', [
                'verification_id' => $verificationId,
                'message' => $e->getMessage(),
            ]);

            return $this->fail($e->getMessage(), 'transport_error', null, $started);
        }
    }

    /** Liveness probe. Needs no credentials. */
    public function healthy(): bool
    {
        try {
            return Http::timeout(10)
                ->get(config('ai_verification.base_url').'/health')
                ->successful();
        } catch (Throwable) {
            return false;
        }
    }

    private function fail(string $message, string $code, ?int $status, float $started): array
    {
        return [
            'ok' => false,
            'status' => $status,
            'body' => [],
            'error' => $message,
            'error_code' => $code,
            'duration_ms' => $this->ms($started),
        ];
    }

    private function ms(float $started): int
    {
        return (int) round((microtime(true) - $started) * 1000);
    }
}
