<?php

namespace App\Support;

final class PublicNavigation
{
    public static function defaults(string $area): array
    {
        return match ($area) {
            'header' => [
                ['label' => 'Home', 'url' => '/'],
                ['label' => 'About Us', 'url' => '/about-us'],
                ['label' => 'Our Team', 'url' => '/our-team'],
                ['label' => 'Success Stories', 'url' => '/happy-stories'],
                ['label' => 'Blogs', 'url' => '/blog'],
                ['label' => 'Help & Support', 'url' => '/contact-us/page'],
            ],
            'footer' => [
                ['label' => 'Home', 'url' => '/'],
                ['label' => 'Active Members', 'url' => '/active-members'],
                ['label' => 'Success Stories', 'url' => '/happy-stories'],
                ['label' => 'Premium Plans', 'url' => '/packages'],
            ],
            'footer_trust' => [
                ['label' => 'Privacy Policy', 'url' => '/privacy-policy'],
                ['label' => 'Terms & Conditions', 'url' => '/terms-conditions'],
                ['label' => 'Identity Verification', 'url' => '/contact-us/page'],
                ['label' => 'Safe Matrimony Guide', 'url' => '/contact-us/page'],
            ],
            'footer_bottom' => [
                ['label' => 'Terms', 'url' => '/terms-conditions'],
                ['label' => 'Privacy', 'url' => '/privacy-policy'],
                ['label' => 'Help Center', 'url' => '/contact-us/page'],
            ],
            default => [],
        };
    }

    public static function items(string $area): array
    {
        $defaults = self::defaults($area);
        $raw = get_setting('hq_public_nav_' . $area, null);
        if ($raw === null || $raw === '') {
            return $defaults;
        }

        $decoded = json_decode((string) $raw, true);
        if (!is_array($decoded)) {
            return $defaults;
        }

        return array_values(array_filter($decoded, static function ($item) {
            return is_array($item) && is_string($item['label'] ?? null)
                && trim($item['label']) !== ''
                && is_string($item['url'] ?? null)
                && (preg_match('#^/(?!/)#', $item['url']) === 1
                    || preg_match('#^https://#i', $item['url']) === 1);
        }));
    }

    public static function link(string $url): string
    {
        if (str_starts_with($url, '/') && !str_starts_with($url, '//')) {
            return url($url);
        }
        if (filter_var($url, FILTER_VALIDATE_URL)
            && parse_url($url, PHP_URL_SCHEME) === 'https') {
            return $url;
        }
        return url('/');
    }
}
