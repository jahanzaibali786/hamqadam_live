<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\System;

use App\Http\Controllers\Api\V1\ApiController;
use Illuminate\Http\JsonResponse;

final class BridgeController extends ApiController
{
    public function channelA(): JsonResponse
    {
        return $this->success(
            $this->connectorPayload(
                label: 'connector_a',
                enabled: (int) get_setting('chat_realtime_enabled', 0) === 1,
                values: [
                    'app_id' => (string) get_setting('pusher_app_id', ''),
                    'app_key' => (string) get_setting('pusher_app_key', ''),
                    'cluster' => (string) get_setting('pusher_app_cluster', ''),
                    'host' => (string) get_setting('pusher_host', ''),
                    'port' => (string) get_setting('pusher_port', ''),
                    'scheme' => (string) get_setting('pusher_scheme', ''),
                ],
                hiddenValues: [
                    'app_secret' => (string) get_setting('pusher_app_secret', ''),
                ],
            ),
            'Configuration loaded successfully.'
        );
    }

    public function channelB(): JsonResponse
    {
        return $this->success(
            $this->connectorPayload(
                label: 'connector_b',
                enabled: (int) get_setting('agora_calling_enabled', 0) === 1,
                values: [
                    'app_id' => (string) get_setting('agora_app_id', ''),
                    'token_expiry' => (string) get_setting('agora_token_expiry', '3600'),
                ],
                hiddenValues: [
                    'app_certificate' => (string) get_setting('agora_app_certificate', ''),
                ],
            ),
            'Configuration loaded successfully.'
        );
    }

    public function channelC(): JsonResponse
    {
        $stripe = $this->connectorPayload(
            label: 'stripe_gateway',
            enabled: (int) get_setting('stripe_payment_activation', 0) === 1,
            values: [
                'payment_activation_key' => 'stripe_payment_activation',
                'sandbox' => false,
                'checkout_mode' => 'stripe_checkout',
                'instruction' => (string) (get_setting('STRIPE_PAYMENT_INSTRUCTIONS') ?? ''),
            ],
            hiddenValues: [
                'public_key' => (string) get_setting('STRIPE_KEY', ''),
                'secret_key' => (string) get_setting('STRIPE_SECRET', ''),
            ],
        );

        $easypaisa = $this->connectorPayload(
            label: 'easypaisa_gateway',
            enabled: (int) get_setting('easypaisa_payment_activation', 0) === 1,
            values: [
                'payment_activation_key' => 'easypaisa_payment_activation',
                'sandbox' => (int) get_setting('easypaisa_sandbox', 0) === 1,
                'checkout_mode' => 'manual_gateway_confirmation',
                'instruction' => (string) (get_setting('EASYPAISA_PAYMENT_INSTRUCTIONS') ?? ''),
            ],
            hiddenValues: [
                'store_id' => (string) get_setting('EASYPAISA_STORE_ID', ''),
                'account_msisdn' => (string) get_setting('EASYPAISA_ACCOUNT_MSISDN', ''),
                'username' => (string) get_setting('EASYPAISA_USERNAME', ''),
                'password' => (string) get_setting('EASYPAISA_PASSWORD', ''),
                'hash_key' => (string) get_setting('EASYPAISA_HASH_KEY', ''),
                'endpoint' => (string) get_setting('EASYPAISA_ENDPOINT', ''),
            ],
        );

        $jazzcash = $this->connectorPayload(
            label: 'jazzcash_gateway',
            enabled: (int) get_setting('jazzcash_payment_activation', 0) === 1,
            values: [
                'payment_activation_key' => 'jazzcash_payment_activation',
                'sandbox' => (int) get_setting('jazzcash_sandbox', 0) === 1,
                'checkout_mode' => 'manual_gateway_confirmation',
                'instruction' => (string) (get_setting('JAZZCASH_PAYMENT_INSTRUCTIONS') ?? ''),
            ],
            hiddenValues: [
                'merchant_id' => (string) get_setting('JAZZCASH_MERCHANT_ID', ''),
                'account_msisdn' => (string) get_setting('JAZZCASH_ACCOUNT_MSISDN', ''),
                'password' => (string) get_setting('JAZZCASH_PASSWORD', ''),
                'integrity_salt' => (string) get_setting('JAZZCASH_INTEGRITY_SALT', ''),
                'endpoint' => (string) get_setting('JAZZCASH_ENDPOINT', ''),
            ],
        );

        $public = [
            'stripe' => $stripe['public'],
            'easypaisa' => $easypaisa['public'],
            'jazzcash' => $jazzcash['public'],
        ];

        $secured = [
            'stripe' => $stripe['secured'],
            'easypaisa' => $easypaisa['secured'],
            'jazzcash' => $jazzcash['secured'],
        ];

        return $this->success(
            [
                'connector' => 'connector_c',
                'enabled' => $stripe['enabled'] || $easypaisa['enabled'] || $jazzcash['enabled'],
                'public' => $public,
                'fingerprints' => [
                    'stripe' => $stripe['fingerprints'],
                    'easypaisa' => $easypaisa['fingerprints'],
                    'jazzcash' => $jazzcash['fingerprints'],
                ],
                'secured' => $secured,
                'payload_hash' => $this->fingerprint([
                    'connector' => 'connector_c',
                    'public' => $public,
                    'secured' => $secured,
                ]),
                'meta' => [
                    'bridged' => true,
                    'purpose' => 'payment_integrations',
                    'supported_gateways' => ['stripe', 'easypaisa', 'jazzcash'],
                ],
            ],
            'Configuration loaded successfully.'
        );
    }
    private function connectorPayload(string $label, bool $enabled, array $values, array $hiddenValues): array
    {
        $publicValues = [];
        $fingerprints = [];

        foreach ($values as $key => $value) {
            $publicValues[$key] = $value;
            $fingerprints[$key] = $this->fingerprint($value);
        }

        $hiddenPayload = [];
        foreach ($hiddenValues as $key => $value) {
            $hiddenPayload[$key] = [
                'present' => $value !== '',
                'masked' => $this->mask($value),
                'fingerprint' => $this->fingerprint($value),
            ];
        }

        return [
            'connector' => $label,
            'enabled' => $enabled,
            'public' => $publicValues,
            'fingerprints' => $fingerprints,
            'secured' => $hiddenPayload,
            'payload_hash' => $this->fingerprint([
                'connector' => $label,
                'enabled' => $enabled,
                'public' => $publicValues,
                'secured' => $hiddenPayload,
            ]),
        ];
    }

    private function fingerprint(mixed $value): string
    {
        $serialized = is_scalar($value) || $value === null
            ? (string) $value
            : (json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '');

        return hash('sha256', $serialized);
    }

    private function mask(string $value): string
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            return '';
        }

        $visible = max(2, min(4, strlen($trimmed)));

        return str_repeat('*', max(0, strlen($trimmed) - $visible)) . substr($trimmed, -$visible);
    }
}

