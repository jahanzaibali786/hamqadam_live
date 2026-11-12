<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\HoroscopeProfileMatchController;
use App\Http\Controllers\ProfileMatchController;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Astrology;
use App\Support\RegistrationReward;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\FamilyGuardianLink;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use App\Services\Admin\UserActivityTracker;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('frontend.user_login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        app(UserActivityTracker::class)->trackLogin(auth()->user(), $request, 'web');

        $user = $request->user();

        if (in_array($user->user_type, ['admin', 'staff'], true)) {
            $redirect_route = 'admin.dashboard';
        } elseif (FamilyGuardianLink::where('guardian_user_id', $user->id)
            ->where('status', 'approved')
            ->whereNull('revoked_at')
            ->exists()) {
            $redirect_route = 'guardian_panel.index';
        } else {
            // A member is linked through members.user_id = users.id, NOT by
            // members.id = users.id. The relation excludes soft-deleted rows.
            $members = $user->member()->limit(2)->get();
            if ($members->count() !== 1) {
                Log::warning('Login: no unique active member record for user', [
                    'user_id' => $user->id,
                    'active_member_count' => $members->count(),
                    'trashed_member_count' => $user->member()->onlyTrashed()->count(),
                ]);

                // Do not create a second member, restore a deleted account, or
                // connect a member with a coincidentally matching primary key.
                return redirect()->route('contact_us')
                    ->with('warning', 'Your login succeeded, but the member profile needs review. Please contact support.');
            }

            $member = $members->first();
            if ($member->current_package_id === null) {
                // Set an existing active default package ONLY for the member
                // tied to this authenticated user; never invent a package ID.
                $defaultPackage = RegistrationReward::registrationPackage();
                if ($defaultPackage) {
                    $member->current_package_id = $defaultPackage->id;
                    $member->save();
                }
            }

            $redirect_route = $member->current_package_id !== null ? 'dashboard' : 'packages';

            if ((int) $member->auto_horoscope_profile_match === 1) {
                (new HoroscopeProfileMatchController)->match_profiles($user->id);
            }

            if ((int) $member->auto_profile_match === 1) {
                (new ProfileMatchController)->match_profiles($user->id);
            }
        }

        return redirect()->route($redirect_route);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        // Auth::guard('web')->logout();

        // $request->session()->invalidate();

        // $request->session()->regenerateToken();

        // return redirect('/');

        if (auth()->user() != null && (auth()->user()->user_type == 'admin' || auth()->user()->user_type == 'staff')) {
            $redirect_route = 'admin';
        } else {
            $redirect_route = 'home';
        }

        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        $request->session()->invalidate();

        return redirect()->route($redirect_route);
    }
}



