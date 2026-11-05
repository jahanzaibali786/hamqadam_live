<?php

namespace App\Support;

use Illuminate\Support\Arr;

final class MemberAdSliders
{
    public const MAX_SLIDES = 6;

    // Only these pre-installed files can be selected without an uploaded image ID.
    public const PRESET_IMAGES = [
        'top_couple' => 'assets/img/member-reference-couple.svg',
        'top_hearts' => 'assets/img/hamqadam-luxury/hero-hearts.png',
        'top_trust' => 'assets/img/hamqadam-luxury/silk-trust.png',
        'bottom_rings' => 'assets/img/hamqadam-luxury/hero-jewelry.jpg',
        'bottom_jewelry' => 'assets/img/hamqadam-luxury/hero-jewelry.png',
        'bottom_floral' => 'assets/img/hamqadam-luxury/watercolor-frame.png',
    ];

    public static function defaults(): array
    {
        return [
            'top' => [
                [
                    'preset' => 'top_couple', 'image_id' => null,
                    'eyebrow' => 'AI Matches', 'title' => 'Thoughtful Proposals',
                    'highlight' => 'for Your Journey',
                    'subtitle' => 'Our AI finds compatible, verified profiles based on your preferences.',
                    'button_label' => 'Explore AI Matches', 'button_url' => route('my_matched_profiles'),
                    'object_x' => 50, 'object_y' => 50, 'active' => true,
                ],
                [
                    'preset' => 'top_hearts', 'image_id' => null,
                    'eyebrow' => 'Verified Community', 'title' => 'Discover Genuine',
                    'highlight' => 'Connections',
                    'subtitle' => 'Explore meaningful matches in a respectful community.',
                    'button_label' => 'View Members', 'button_url' => route('member.listing'),
                    'object_x' => 50, 'object_y' => 50, 'active' => true,
                ],
                [
                    'preset' => 'top_trust', 'image_id' => null,
                    'eyebrow' => 'Your Next Chapter', 'title' => 'A Beautiful Beginning',
                    'highlight' => 'Starts Here',
                    'subtitle' => 'Take the next step toward a compatible lifelong partner.',
                    'button_label' => 'Find Matches', 'button_url' => route('my_matched_profiles'),
                    'object_x' => 50, 'object_y' => 50, 'active' => true,
                ],
            ],
            'bottom' => [
                [
                    'preset' => 'bottom_rings', 'image_id' => null,
                    'eyebrow' => 'Sponsored', 'title' => 'Exclusive Wedding Packages',
                    'highlight' => '', 'subtitle' => 'Venues | Photography | Decor | Catering',
                    'button_label' => 'Get Quote', 'button_url' => route('member.reference.packages'),
                    'object_x' => 50, 'object_y' => 50, 'active' => true,
                ],
                [
                    'preset' => 'bottom_jewelry', 'image_id' => null,
                    'eyebrow' => 'Partner', 'title' => 'Find Premium Wedding Venues',
                    'highlight' => '', 'subtitle' => 'Explore elegant celebrations and meaningful moments.',
                    'button_label' => 'View Offers', 'button_url' => route('member.reference.packages'),
                    'object_x' => 50, 'object_y' => 50, 'active' => true,
                ],
                [
                    'preset' => 'bottom_floral', 'image_id' => null,
                    'eyebrow' => 'Featured', 'title' => 'Plan Your Special Day',
                    'highlight' => '', 'subtitle' => 'Explore services for your new beginning.',
                    'button_label' => 'Learn More', 'button_url' => route('member.reference.packages'),
                    'object_x' => 50, 'object_y' => 50, 'active' => true,
                ],
            ],
        ];
    }

    public static function all(): array
    {
        $saved = json_decode((string) get_setting('hq_member_ad_sliders', ''), true);
        $defaults = self::defaults();

        // Once the editor has saved an empty slot, keep it empty, not re-seeded.
        if (! is_array($saved)) {
            return $defaults;
        }

        foreach (['top', 'bottom'] as $placement) {
            if (array_key_exists($placement, $saved) && is_array($saved[$placement])) {
                $defaults[$placement] = array_values(array_slice(
                    array_filter($saved[$placement], 'is_array'), 0, self::MAX_SLIDES
                ));
            }
        }

        return $defaults;
    }

    public static function slides(string $placement, bool $includeHidden = false): array
    {
        if (! in_array($placement, ['top', 'bottom'], true)) {
            return [];
        }

        return array_values(array_filter(array_map(function (array $slide) {
            $imageId = filter_var($slide['image_id'] ?? null, FILTER_VALIDATE_INT);
            $preset = (string) ($slide['preset'] ?? '');
            $image = $imageId ? uploaded_asset($imageId) : null;

            if (! $image && isset(self::PRESET_IMAGES[$preset])) {
                $image = static_asset(self::PRESET_IMAGES[$preset]);
            }

            if (! $image) {
                return null;
            }

            $slide['image_url'] = $image;
            $slide['object_x'] = max(0, min(100, (int) ($slide['object_x'] ?? 50)));
            $slide['object_y'] = max(0, min(100, (int) ($slide['object_y'] ?? 50)));
            $slide['active'] = (bool) ($slide['active'] ?? false);

            return $slide;
        }, self::all()[$placement]), fn ($slide) => $slide !== null &&
            ($includeHidden || $slide['active'])));
    }
}
