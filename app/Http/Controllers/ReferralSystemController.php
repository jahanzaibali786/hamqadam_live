<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Wallet;

class ReferralSystemController extends Controller
{
    public function set_referral_commission()
    {
        return view()->exists('admin.settings.general_settings')
            ? view('admin.settings.general_settings')
            : redirect()->route('admin.dashboard');
    }

    public function index()
    {
        $referredUsers = User::whereNotNull('referred_by')->latest()->paginate(20);

        return view()->exists('admin.members.index')
            ? view('admin.members.index', ['members' => $referredUsers])
            : response()->json($referredUsers);
    }

    public function referal_earnings_admin()
    {
        $earnings = Wallet::where('payment_method', 'reffered_commission')->latest()->paginate(20);

        return view()->exists('admin.wallet.transaction_history')
            ? view('admin.wallet.transaction_history', ['wallets' => $earnings])
            : response()->json($earnings);
    }

    public function my_referred_users()
    {
        $referredUsers = User::where('referred_by', auth()->id())->latest()->paginate(20);

        return view()->exists('frontend.member.my_shortlists')
            ? view('frontend.member.my_shortlists', ['shortlists' => $referredUsers])
            : response()->json($referredUsers);
    }

    public function my_referral_earnings()
    {
        $earnings = Wallet::where('payment_method', 'reffered_commission')
            ->where('user_id', auth()->id())
            ->latest()
            ->paginate(20);

        return view()->exists('frontend.member.wallet.index')
            ? view('frontend.member.wallet.index', ['wallets' => $earnings])
            : response()->json($earnings);
    }
}

