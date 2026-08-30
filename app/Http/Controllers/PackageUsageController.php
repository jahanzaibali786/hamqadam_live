<?php

namespace App\Http\Controllers;

use App\Models\PackagePayment;
use App\Models\PackageUsage;

class PackageUsageController extends Controller
{
    public function index()
    {
        $usages = PackageUsage::where('user_id', auth()->id())
            ->latest()
            ->get();

        $packagePayments = PackagePayment::where('user_id', auth()->id())
            ->with('package')
            ->get();

        $totalPurchasedCoins = (int) $packagePayments->sum(function ($payment) {
            return (int) ($payment->package?->express_interest ?? 0);
        });

        $totalUsedCoins = (int) $usages->sum('amount');
        $remainingCoins = (int) (auth()->user()->member?->remaining_interest ?? 0);
        $remainingProfileViews = (int) (auth()->user()->member?->remaining_profile_viewer_view ?? 0);
        $profileViewsUsed = (int) $usages->where('feature', 'profile_viewer_view')->sum('amount');

        return view('frontend.member.package_usage_history', compact(
            'usages',
            'totalPurchasedCoins',
            'totalUsedCoins',
            'remainingCoins',
            'remainingProfileViews',
            'profileViewsUsed'
        ));
    }
}
