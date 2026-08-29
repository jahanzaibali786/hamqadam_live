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
            $this->buildConnectorPayload(
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
            $this->buildConnectorPayload(
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

    private function buildConnectorPayload(string $label, bool $enabled, array $values, array $hiddenValues): array
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
