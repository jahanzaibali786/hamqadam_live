<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Ads;

use App\Http\Controllers\Api\V1\ApiController;
use App\Support\MemberAdSliders;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Home-screen ad / banner carousels, managed in Admin → Member Ad Sliders.
 * Same data the website's member home renders, so both stay in sync.
 */
class AdSliderController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $placements = $request->filled('placement')
            ? array_intersect([(string) $request->query('placement')], ['top', 'bottom'])
            : ['top', 'bottom'];

        $data = [];
        foreach ($placements as $placement) {
            $data[$placement] = array_map(fn (array $s) => [
                'eyebrow' => (string) ($s['eyebrow'] ?? ''),
                'title' => (string) ($s['title'] ?? ''),
                'highlight' => (string) ($s['highlight'] ?? ''),
                'subtitle' => (string) ($s['subtitle'] ?? ''),
                'button_label' => (string) ($s['button_label'] ?? ''),
                'button_url' => $this->absoluteUrl($s['button_url'] ?? null),
                'image_url' => $s['image_url'],
                'object_x' => $s['object_x'],
                'object_y' => $s['object_y'],
            ], MemberAdSliders::slides($placement));
        }

        return $this->success($data);
    }

    private function absoluteUrl(?string $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return null;
        }

        return preg_match('#^https?://#i', $url) ? $url : url($url);
    }
}
