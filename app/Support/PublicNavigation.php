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
                ['label' => 'Refund Policy', 'url' => '/refund-policy'],
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
                ['label' => 'Refund Policy', 'url' => '/refund-policy'],
                ['label' => 'Identity Verification', 'url' => '/contact-us/page'],
                ['label' => 'Safe Matrimony Guide', 'url' => '/contact-us/page'],
            ],
            'footer_bottom' => [
                ['label' => 'Terms', 'url' => '/terms-conditions'],
                ['label' => 'Privacy', 'url' => '/privacy-policy'],
                ['label' => 'Help Center', 'url' => '/help-center'],
            ],
            default => [],
        };
    }

    public static function items(string $area): array
    {
        $defaults = self::defaults($area);
        $raw = get_setting('hq_public_nav_' . $area, null);
        $items = $defaults;

        if ($raw !== null && $raw !== '') {
            $decoded = json_decode((string) $raw, true);
            if (is_array($decoded)) {
                $items = array_values(array_filter($decoded, static function ($item) {
                    return is_array($item) && is_string($item['label'] ?? null)
                        && trim($item['label']) !== ''
                        && is_string($item['url'] ?? null)
                        && (preg_match('#^/(?!/)#', $item['url']) === 1
                            || preg_match('#^https://#i', $item['url']) === 1);
                }));
            }
        }

        // Compliance navigation must remain visible even if the administrator
        // previously saved a custom menu before the policy page existed.
        if (in_array($area, ['header','footer_trust'], true) &&
            ! collect($items)->contains(fn ($item) =>
                parse_url($item['url'], PHP_URL_PATH) === '/refund-policy')) {
            $items[] = ['label' => 'Refund Policy', 'url' => '/refund-policy'];
        }

        if ($area === 'footer_bottom') {
            $found = false;
            foreach ($items as &$item) {
                if (strcasecmp(trim($item['label']), 'Help Center') === 0) {
                    // The older help link was a placeholder to the contact form.
                    if ($item['url'] === '/contact-us/page') {
                        $item['url'] = '/help-center';
                    }
                    $found = true;
                }
            }
            unset($item);
            if (! $found && ! collect($items)->contains(fn ($item) =>
                parse_url($item['url'], PHP_URL_PATH) === '/help-center')) {
                $items[] = ['label' => 'Help Center', 'url' => '/help-center'];
            }
        }

        return $items;
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
