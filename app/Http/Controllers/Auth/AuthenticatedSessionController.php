<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\HoroscopeProfileMatchController;
use App\Http\Controllers\ProfileMatchController;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Astrology;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

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

        if (auth()->user() != null && (auth()->user()->user_type == 'admin' || auth()->user()->user_type == 'staff')) {
            $redirect_route = 'admin.dashboard';
        } else {
            if (auth()->user() != null && (auth()->user()->member->current_package_id != null)) {
                $redirect_route = 'dashboard';
            }else{
                $redirect_route = 'packages';
            }
            
            $user  = User::where('id',Auth::user()->id)->first();
    
            if($user->member->auto_horoscope_profile_match ==  1){
              $HoroscopeProfileMatchController = new HoroscopeProfileMatchController;
              $HoroscopeProfileMatchController->match_profiles($user->id);
            }
    
            if($user->member->auto_profile_match ==  1){
              $ProfileMatchController = new ProfileMatchController;
              $ProfileMatchController->match_profiles($user->id);
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
