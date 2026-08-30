<?php

declare(strict_types=1);

namespace App\Support\Compatibility;

use App\Services\FirbaseNotification;
use Illuminate\Support\Facades\Log;

class Larafirebase
{
    protected string $title = '';
    protected string $body = '';

    public static function withTitle(string $title): self
    {
        $instance = new self();
        $instance->title = $title;

        return $instance;
    }

    public function withBody(string $body): self
    {
        $this->body = $body;

        return $this;
    }

    public function sendMessage($tokens): bool
    {
        $tokens = is_array($tokens) ? $tokens : [$tokens];
        $tokens = array_values(array_filter($tokens));

        if (empty($tokens)) {
            return false;
        }

        foreach ($tokens as $token) {
            try {
                FirbaseNotification::send((object) [
                    'fcm_token' => $token,
                    'title' => $this->title,
                    'text' => $this->body,
                    'notify_by' => null,
                ]);
            } catch (\Throwable $e) {
                Log::warning('FCM compatibility send failed.', [
                    'title' => $this->title,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return true;
    }
}