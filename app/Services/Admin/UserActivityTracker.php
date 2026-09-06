<?php

namespace App\Services\Admin;

use App\Models\User;
use App\Models\UserActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class UserActivityTracker
{
    public function trackLogin(User $user, ?Request $request = null, string $guard = 'web', ?array $metadata = null): void
    {
        $request ??= request();

        if (! Schema::hasTable('user_activity_logs')) {
            return;
        }

        $ipAddress = $this->clientIp($request);

        UserActivityLog::create([
            'user_id' => $user->id,
            'event_type' => 'login',
            'guard' => $guard,
            'session_id' => $request->hasSession() ? $request->session()->getId() : null,
            'device_type' => $this->deviceType($request->userAgent()),
            'ip_address' => $ipAddress,
            'location' => $this->locationFromRequest($request, $ipAddress),
            'user_agent' => $request->userAgent(),
            'metadata' => array_merge($metadata ?: [], [
                'ip_source' => $this->ipSource($request),
                'proxy_ip' => $request->ip(),
            ]),
            'occurred_at' => now(),
        ]);

        if ($user->getAttribute('last_activity') !== null) {
            $user->forceFill([
                'ip_address' => $ipAddress,
                'last_activity' => now()->timestamp,
            ])->save();
        }
    }

    public function trackEvent(User $user, string $eventType, ?Request $request = null, ?array $metadata = null): void
    {
        $request ??= request();

        if (! Schema::hasTable('user_activity_logs')) {
            return;
        }

        $ipAddress = $this->clientIp($request);

        UserActivityLog::create([
            'user_id' => $user->id,
            'event_type' => $eventType,
            'guard' => auth()->getDefaultDriver(),
            'session_id' => $request->hasSession() ? $request->session()->getId() : null,
            'device_type' => $this->deviceType($request->userAgent()),
            'ip_address' => $ipAddress,
            'location' => $this->locationFromRequest($request, $ipAddress),
            'user_agent' => $request->userAgent(),
            'metadata' => array_merge($metadata ?: [], [
                'ip_source' => $this->ipSource($request),
                'proxy_ip' => $request->ip(),
            ]),
            'occurred_at' => now(),
        ]);
    }

    private function deviceType(?string $userAgent): string
    {
        $ua = strtolower((string) $userAgent);

        if (str_contains($ua, 'android') || str_contains($ua, 'iphone') || str_contains($ua, 'mobile')) {
            return 'mobile';
        }

        if (str_contains($ua, 'ipad') || str_contains($ua, 'tablet')) {
            return 'tablet';
        }

        return 'web';
    }

    private function clientIp(Request $request): ?string
    {
        foreach (['CF-Connecting-IP', 'True-Client-IP', 'X-Real-IP'] as $header) {
            $ip = trim((string) $request->header($header));
            if ($this->isValidIp($ip)) {
                return $ip;
            }
        }

        foreach (explode(',', (string) $request->header('X-Forwarded-For')) as $candidate) {
            $ip = trim($candidate);
            if ($this->isValidIp($ip)) {
                return $ip;
            }
        }

        $forwarded = (string) $request->header('Forwarded');
        if (preg_match('/for="?\[?([^;,\]"]+)/i', $forwarded, $match) && $this->isValidIp($match[1])) {
            return $match[1];
        }

        $ip = $request->ip();
        return $this->isValidIp($ip) ? $ip : null;
    }

    private function ipSource(Request $request): string
    {
        foreach (['CF-Connecting-IP', 'True-Client-IP', 'X-Real-IP', 'X-Forwarded-For', 'Forwarded'] as $header) {
            if ($request->headers->has($header)) {
                return $header;
            }
        }

        return 'request_ip';
    }

    private function locationFromRequest(Request $request, ?string $ipAddress = null): ?string
    {
        $country = $request->header('CF-IPCountry') ?: $request->header('X-App-Country');
        $city = $request->header('X-App-City');
        $region = $request->header('X-App-Region');

        $location = trim(implode(', ', array_filter([$city, $region, $country])));

        if ($location !== '') {
            return $location;
        }

        if ($ipAddress && $this->isPrivateIp($ipAddress)) {
            return in_array($ipAddress, ['::1', '127.0.0.1'], true) ? 'Localhost' : 'Private network';
        }

        return null;
    }

    private function isValidIp(?string $ip): bool
    {
        return is_string($ip) && filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }
    private function isPrivateIp(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }
}



