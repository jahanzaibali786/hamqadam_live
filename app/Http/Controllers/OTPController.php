<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class OTPController extends Controller
{
    public function credentials_index()
    {
        return view()->exists('admin.settings.third_party_settings')
            ? view('admin.settings.third_party_settings')
            : redirect()->route('admin.dashboard');
    }

    public function update_credentials(Request $request)
    {
        flash(translate('OTP credentials can be managed from settings.'))->success();

        return back();
    }

    public function bulk_sms()
    {
        return view()->exists('admin.marketing.newsletters')
            ? view('admin.marketing.newsletters')
            : redirect()->route('admin.dashboard');
    }

    public function bulk_sms_send(Request $request)
    {
        $request->validate([
            'message' => ['nullable', 'string'],
        ]);

        flash(translate('Bulk SMS dispatch is not configured yet.'))->warning();

        return back();
    }
}

