<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Upload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LoginPageSettingsController extends Controller
{
    public const TEXT_FIELDS = [
        'meta_title', 'eyebrow', 'community_text', 'helpline_caption',
        'story_kicker', 'story_context', 'story_title', 'story_quote',
        'testimonial_name', 'testimonial_detail', 'testimonial_quote',
        'stat_one_value', 'stat_one_label', 'stat_two_value', 'stat_two_label',
        'form_kicker', 'form_title', 'email_placeholder', 'password_placeholder',
        'remember_text', 'session_text', 'submit_text', 'register_title',
        'register_description', 'register_button', 'support_prompt', 'support_button',
        'trust_1_title', 'trust_1_description', 'trust_2_title', 'trust_2_description',
        'trust_3_title', 'trust_3_description', 'trust_4_title', 'trust_4_description',
        'card_1_title', 'card_1_alt', 'card_2_title', 'card_2_alt',
        'card_3_title', 'card_3_alt',
    ];

    public const MEDIA_FIELDS = [
        'testimonial_image', 'card_1_image', 'card_2_image', 'card_3_image',
    ];

    public function edit(): View
    {
        return view('admin.website_settings.login_page');
    }

    public function update(Request $request): RedirectResponse
    {
        $rules = [];
        foreach (self::TEXT_FIELDS as $field) {
            $rules[$field] = ['nullable', 'string', 'max:1200'];
        }
        foreach (self::MEDIA_FIELDS as $field) {
            $rules[$field] = ['nullable', 'integer', 'min:0'];
        }
        $validated = $request->validate($rules);
        foreach (self::MEDIA_FIELDS as $field) {
            $id = (int) ($validated[$field] ?? 0);
            if ($id > 0 && ! Upload::query()->whereKey($id)->exists()) {
                return back()->withErrors([$field => 'Please select a valid uploaded image.'])->withInput();
            }
        }

        DB::transaction(function () use ($validated): void {
            foreach (self::TEXT_FIELDS as $field) {
                Setting::updateOrCreate(['type' => 'hq_login_' . $field], ['value' => $validated[$field] ?? '']);
            }
            foreach (self::MEDIA_FIELDS as $field) {
                Setting::updateOrCreate(['type' => 'hq_login_' . $field], ['value' => (string) (int) ($validated[$field] ?? 0)]);
            }
        });
        Cache::forget('settings');
        return back()->with('success', 'Login page updated successfully.');
    }
}
