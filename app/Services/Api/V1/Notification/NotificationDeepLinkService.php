<?php

declare(strict_types=1);

namespace App\Services\Api\V1\Notification;

use App\Models\User;

/**
 * Client-facing destinations must be relative first-party paths, never arbitrary
 * external URLs. All target pages continue to enforce their own authorization.
 */
final class NotificationDeepLinkService
{
    public function resolve(User $user, array $data): string
    {
        $path = trim((string) ($data['route'] ?? ''));
        if ($path === '' || ! str_starts_with($path, '/') || str_starts_with($path, '//')
            || str_contains($path, '\\') || preg_match('/[\x00-\x1F\x7F]/', $path)) {
            return '/dashboard';
        }
        $safePrefixes = ['/dashboard', '/member/', '/support-ticket/', '/packages', '/my-', '/notifications'];
        foreach ($safePrefixes as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix)) return $path;
        }
        return '/dashboard';
    }
}
