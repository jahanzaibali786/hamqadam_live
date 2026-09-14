<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Models\LoginAttempt;
use App\Models\SuspiciousActivityLog;
use App\Models\User;
use App\Models\UserActivityLog;
use App\Services\Admin\UserActivityTracker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Exceptions\ApiException;

class LoginSecurityService
{
    private const MAX_FAILURES = 5;
    private const WINDOW_MINUTES = 15;
    private const LOCKOUT_MINUTES = 15;

    public function assertApiAllowed(string $identifier, ?Request $request, string $channel): void
    {
        $seconds = $this->lockoutSeconds($identifier, $request, $channel);

        if ($seconds > 0) {
            throw new ApiException(
                'Too many failed login attempts. Please try again later.',
                429,
                'login_temporarily_locked',
                ['retry_after_seconds' => $seconds]
            );
        }
    }

    public function lockoutSeconds(string $identifier, ?Request $request, string $channel): int
    {
        if (! Schema::hasTable('login_attempts')) {
            return 0;
        }

        $query = LoginAttempt::query()
            ->where('identifier_hash', $this->identifierHash($identifier))
            ->where('channel', $channel)
            ->where('successful', false)
            ->where('occurred_at', '>=', now()->subMinutes(self::WINDOW_MINUTES));

        $ip = $request?->ip();
        if ($ip) {
            $query->where('ip_address', $ip);
        }

        $latestSuccess = LoginAttempt::query()
            ->where('identifier_hash', $this->identifierHash($identifier))
            ->where('channel', $channel)
            ->where('successful', true)
            ->latest('occurred_at')
            ->value('occurred_at');

        if ($latestSuccess) {
            $query->where('occurred_at', '>', $latestSuccess);
        }

        if ($query->count() < self::MAX_FAILURES) {
            return 0;
        }

        $lastFailure = $query->latest('occurred_at')->first()?->occurred_at;
        if (! $lastFailure) {
            return 0;
        }

        $unlockAt = $lastFailure->copy()->addMinutes(self::LOCKOUT_MINUTES);
        return $unlockAt->isFuture() ? max(1, now()->diffInSeconds($unlockAt)) : 0;
    }

    public function recordFailure(?User $user, string $identifier, ?Request $request, string $channel, string $reason): void
    {
        if (! Schema::hasTable('login_attempts')) {
            return;
        }

        $ip = $request?->ip();
        $attempt = LoginAttempt::create([
            'user_id' => $user?->id,
            'identifier_hash' => $this->identifierHash($identifier),
            'identifier_hint' => $this->identifierHint($identifier),
            'channel' => $channel,
            'ip_address' => $ip,
            'user_agent' => Str::limit((string) $request?->userAgent(), 500, ''),
            'successful' => false,
            'failure_reason' => $reason,
            'metadata' => ['ip_source' => $request?->ip() ? 'request_ip' : null],
            'occurred_at' => now(),
        ]);

        if ($user) {
            app(UserActivityTracker::class)->trackEvent($user, 'login_failed', $request, [
                'channel' => $channel,
                'failure_reason' => $reason,
                'login_attempt_id' => $attempt->id,
            ]);
        }

        if ($this->failureCount($identifier, $request, $channel) === self::MAX_FAILURES) {
            $this->recordSuspiciousEvent($user, $identifier, $request, $channel);
        }
    }

    public function recordSuccess(User $user, string $identifier, ?Request $request, string $channel): void
    {
        if (! Schema::hasTable('login_attempts')) {
            return;
        }

        LoginAttempt::create([
            'user_id' => $user->id,
            'identifier_hash' => $this->identifierHash($identifier),
            'identifier_hint' => $this->identifierHint($identifier),
            'channel' => $channel,
            'ip_address' => $request?->ip(),
            'user_agent' => Str::limit((string) $request?->userAgent(), 500, ''),
            'successful' => true,
            'occurred_at' => now(),
        ]);
    }

    private function failureCount(string $identifier, ?Request $request, string $channel): int
    {
        $query = LoginAttempt::query()
            ->where('identifier_hash', $this->identifierHash($identifier))
            ->where('channel', $channel)
            ->where('successful', false)
            ->where('occurred_at', '>=', now()->subMinutes(self::WINDOW_MINUTES));

        if ($request?->ip()) {
            $query->where('ip_address', $request->ip());
        }

        return $query->count();
    }

    private function recordSuspiciousEvent(?User $user, string $identifier, ?Request $request, string $channel): void
    {
        if (! Schema::hasTable('suspicious_activity_logs')) {
            return;
        }

        SuspiciousActivityLog::create([
            'user_id' => $user?->id,
            'activity_type' => 'repeated_login_failures',
            'risk_level' => 'high',
            'risk_score' => 85,
            'signals' => [
                'channel' => $channel,
                'identifier_hint' => $this->identifierHint($identifier),
                'ip_address' => $request?->ip(),
                'failure_threshold' => self::MAX_FAILURES,
                'window_minutes' => self::WINDOW_MINUTES,
            ],
        ]);
    }

    private function identifierHash(string $identifier): string
    {
        return hash('sha256', Str::lower(trim($identifier)));
    }

    private function identifierHint(string $identifier): string
    {
        $identifier = trim($identifier);
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            [$name, $domain] = explode('@', $identifier, 2);
            return substr($name, 0, 2) . str_repeat('*', max(1, strlen($name) - 2)) . '@' . $domain;
        }

        return str_repeat('*', max(0, strlen($identifier) - 4)) . substr($identifier, -4);
    }
}