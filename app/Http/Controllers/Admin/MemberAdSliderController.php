<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\MemberAdSliders;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class MemberAdSliderController extends Controller
{
    public function index(): View
    {
        return view('admin.website_settings.member_ad_sliders', [
            'placements' => MemberAdSliders::all(),
            'presets' => MemberAdSliders::PRESET_IMAGES,
            'maximum' => MemberAdSliders::MAX_SLIDES,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'slides' => ['required', 'array'],
            'slides.top' => ['required', 'array', 'max:6'],
            'slides.bottom' => ['required', 'array', 'max:6'],
            'slides.*.*.preset' => ['nullable', 'string', Rule::in(array_keys(MemberAdSliders::PRESET_IMAGES))],
            'slides.*.*.image_id' => ['nullable', 'integer', 'exists:uploads,id'],
            'slides.*.*.eyebrow' => ['nullable', 'string', 'max:65'],
            'slides.*.*.title' => ['required', 'string', 'max:110'],
            'slides.*.*.highlight' => ['nullable', 'string', 'max:110'],
            'slides.*.*.subtitle' => ['nullable', 'string', 'max:230'],
            'slides.*.*.button_label' => ['nullable', 'string', 'max:45'],
            'slides.*.*.button_url' => ['nullable', 'string', 'max:500'],
            'slides.*.*.object_x' => ['required', 'integer', 'between:0,100'],
            'slides.*.*.object_y' => ['required', 'integer', 'between:0,100'],
            'slides.*.*.active' => ['nullable', 'boolean'],
        ]);

        $result = [];
        foreach (['top', 'bottom'] as $placement) {
            $result[$placement] = [];
            foreach (($validated['slides'][$placement] ?? []) as $index => $slide) {
                $preset = (string) ($slide['preset'] ?? '');
                $imageId = (int) ($slide['image_id'] ?? 0);
                if (! $imageId && ! isset(MemberAdSliders::PRESET_IMAGES[$preset])) {
                    throw ValidationException::withMessages([
                        "slides.{$placement}.{$index}.image_id" => 'Choose a picture or one of the available starter images.',
                    ]);
                }

                // Never allow arbitrary protocols, javascript:, or protocol-relative URLs.
                $url = trim((string) ($slide['button_url'] ?? ''));
                if ($url !== '' && ! preg_match('#^/(?!/)#', $url)
                    && ! (filter_var($url, FILTER_VALIDATE_URL)
                        && parse_url($url, PHP_URL_SCHEME) === 'https')) {
                    throw ValidationException::withMessages([
                        "slides.{$placement}.{$index}.button_url" => 'Use a /relative path or an HTTPS address.',
                    ]);
                }

                $result[$placement][] = [
                    'preset' => $preset,
                    'image_id' => $imageId ?: null,
                    'eyebrow' => $slide['eyebrow'] ?? '',
                    'title' => $slide['title'],
                    'highlight' => $slide['highlight'] ?? '',
                    'subtitle' => $slide['subtitle'] ?? '',
                    'button_label' => $slide['button_label'] ?? '',
                    'button_url' => $url,
                    'object_x' => (int) $slide['object_x'],
                    'object_y' => (int) $slide['object_y'],
                    'active' => (bool) ($slide['active'] ?? false),
                ];
            }
        }

        Setting::updateOrCreate(
            ['type' => 'hq_member_ad_sliders'],
            ['value' => json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]
        );
        Cache::forget('settings');

        return redirect()->route('website.member_ad_sliders.index')
            ->with('success', 'Top and footer slider settings saved.');
    }
}
