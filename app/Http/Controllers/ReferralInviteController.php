<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Smart referral link: `/invite/{code}`.
 *
 * Opened on a phone it sends the person to the right store (Google Play for
 * Android, App Store for iPhone) so they install the app; on a desktop it falls
 * back to the web registration page with the code prefilled. The code travels
 * with the install where the platform allows it (Play's `referrer` parameter).
 */
class ReferralInviteController extends Controller
{
    public function __invoke(Request $request, string $code): RedirectResponse
    {
        $code = trim($code);
        $agent = strtolower((string) $request->userAgent());
        $web = route('register', ['referral_code' => $code]);

        $isAndroid = str_contains($agent, 'android');
        $isIos = str_contains($agent, 'iphone') || str_contains($agent, 'ipad') || str_contains($agent, 'ipod');

        if ($isAndroid) {
            $play = (string) config('services.mobile_app.play_store');
            if ($play !== '') {
                $glue = str_contains($play, '?') ? '&' : '?';

                return redirect()->away($play . $glue . 'referrer=' . rawurlencode('referral_code=' . $code));
            }
        }

        if ($isIos) {
            $store = (string) config('services.mobile_app.app_store');
            if ($store !== '') {
                return redirect()->away($store);
            }
        }

        return redirect()->to($web);
    }
}
