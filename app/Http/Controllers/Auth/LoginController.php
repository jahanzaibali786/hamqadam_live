<?php

namespace App\Http\Controllers\Auth;

use Socialite;
use App\Models\User;
use App\Models\Hobby;
use App\Models\Staff;
use App\Models\Career;
use App\Models\Family;
use App\Models\Member;
use App\Models\Address;
use App\Models\Package;
use App\Models\Attitude;
use App\Models\Astrology;
use App\Models\Education;
use App\Models\Lifestyle;
use App\Models\Recidency;
use App\Models\ChatThread;
use App\Models\HappyStory;
use CoreComponentRepository;
use Illuminate\Http\Request;
use App\Models\PackagePayment;
use App\Models\PhysicalAttribute;
use App\Models\PartnerExpectation;
use App\Models\SpiritualBackground;
use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use GeneaLabs\LaravelSocialiter\Facades\Socialiter;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */


    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    //protected $redirectTo = RouteServiceProvider::HOME;

    public function redirectToProvider($provider)
    {
        if (request()->get('query') == 'mobile_app') {
            request()->session()->put('login_from', 'mobile_app');
        }
        if ($provider == 'apple') {
            return Socialite::driver("sign-in-with-apple")
                ->scopes(["name", "email"])
                ->redirect();
        }
        return Socialite::driver($provider)->redirect();
    }

    public function handleProviderCallback(Request $request, $provider)
    {
        if (session('login_from') == 'mobile_app') {
            return $this->mobileHandleProviderCallback($request, $provider);
        }
        try {
            if ($provider == 'twitter') {
                $user = Socialite::driver('twitter')->user();
            } else {
                $user = Socialite::driver($provider)->stateless()->user();
            }
        } catch (\Exception $e) {
            flash("Something Went wrong. Please try again.")->error();
            return redirect()->route('user.login');
        }
        // dd($user);
        //check if provider_id exist
        $existingUserByProviderId = User::where('provider_id', $user->id)->first();

        if ($existingUserByProviderId) {
            // $existingUserByProviderId->access_token = $user->token;
            $existingUserByProviderId->save();
            //proceed to login
            auth()->login($existingUserByProviderId, true);
        } else {
            // check if email exist
            $existingUser = User::where('email', '!=', null)->where('email', $user->email)->first();

            if ($existingUser) {
                auth()->login($existingUser, true);
            } else {
                flash(translate('Please complete the full registration form before using social login.'))->warning();
                return redirect()->route('register');
            }
        }
        
        if (session('link') != null) {
            return redirect(session('link'));
        } else {
            return redirect()->route('dashboard');
        }
    }

    public function handleAppleCallback(Request $request)
    {
        try {
            $user = Socialite::driver("sign-in-with-apple")->user();
        } catch (\Exception $e) {
            flash(translate("Something Went wrong. Please try again."))->error();
            return redirect()->route('user.login');
        }
        //check if provider_id exist
        $existingUserByProviderId = User::where('provider_id', $user->id)->first();

        if ($existingUserByProviderId) {
            $existingUserByProviderId->access_token = $user->token;
            $existingUserByProviderId->refresh_token = $user->refreshToken;
            if (!isset($user->user['is_private_email'])) {
                $existingUserByProviderId->email = $user->email;
            }
            $existingUserByProviderId->save();
            //proceed to login
            auth()->login($existingUserByProviderId, true);
        } else {
            //check if email exist
            $existing_or_new_user = User::where('email', $user->email)->first();
            if (! $existing_or_new_user) {
                flash(translate('Please complete the full registration form before using social login.'))->warning();
                return redirect()->route('register');
            }
            $existing_or_new_user->provider_id = $user->id;
            $existing_or_new_user->access_token = $user->token;
            $existing_or_new_user->refresh_token = $user->refreshToken;
            $existing_or_new_user->provider = 'apple';
            $existing_or_new_user->save();

            auth()->login($existing_or_new_user, true);
        }

        
        if (session('link') != null) {
            return redirect(session('link'));
        } else {
            return redirect()->route('dashboard');
        }
    }

    public function mobileHandleProviderCallback($request, $provider)
    {
        $return_provider = '';
        $result = false;
        if ($provider) {
            $return_provider = $provider;
            $result = true;
        }
        return response()->json([
            'result' => $result,
            'provider' => $return_provider
        ]);
    }

    /**
     * Get the needed authorization credentials from the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function credentials(Request $request)
    {
        if (filter_var($request->get('email'), FILTER_VALIDATE_EMAIL)) {
            return $request->only($this->username(), 'password');
        } else {
            return ['phone' => $request->get('email'), 'password' => $request->get('password')];
        }
    }

    public function authenticated()
    {
        if (auth()->user()->user_type == 'admin' || auth()->user()->user_type == 'staff') {
            CoreComponentRepository::instantiateShopRepository();
            return redirect()->route('admin.dashboard');
        } else {
            if (session('link') != null) {
                return redirect(session('link'));
            } else {
                return redirect()->route('dashboard');
            }
        }
    }

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except(['logout', 'account_deletion']);
    }

    public function showLoginForm()
    {
        return view('frontend.user_login');
    }

    public function logout(Request $request)
    {
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

    public function account_deletion(Request $request)
    {
        $redirect_route = 'home';

        $auth_user = auth()->user();
        if ($auth_user) {

            // user images delete from database and file storage
            $uploads = $auth_user->uploads;
            if ($uploads) {
                foreach ($uploads as $upload) {
                    if (file_exists(public_path() . '/' . $upload->file_name)) {
                        unlink(public_path() . '/' . $upload->file_name);
                        $upload->delete();
                    }
                }
            }
            
            $auth_user->member ?  $auth_user->member->delete() : '';
            Address::where('user_id', $auth_user->id)->delete();
            Education::where('user_id', $auth_user->id)->delete();
            Career::where('user_id', $auth_user->id)->delete();
            PhysicalAttribute::where('user_id', $auth_user->id)->delete();
            Hobby::where('user_id', $auth_user->id)->delete();
            Attitude::where('user_id', $auth_user->id)->delete();
            Recidency::where('user_id', $auth_user->id)->delete();
            Lifestyle::where('user_id', $auth_user->id)->delete();
            Astrology::where('user_id', $auth_user->id)->delete();
            Family::where('user_id', $auth_user->id)->delete();
            PartnerExpectation::where('user_id', $auth_user->id)->delete();
            SpiritualBackground::where('user_id', $auth_user->id)->delete();
            PackagePayment::where('user_id', $auth_user->id)->delete();
            HappyStory::where('user_id', $auth_user->id)->delete();
            Staff::where('user_id', $auth_user->id)->delete();
            ChatThread::where('sender_user_id', auth()->user()->id)->orWhere('receiver_user_id', auth()->user()->id)->delete();
            User::destroy(auth()->user()->id);

            auth()->guard()->logout();
            $request->session()->invalidate();

            flash(translate("Your account deletion successfully done."))->success();
            return redirect()->route($redirect_route);
        }
        flash(translate("Something Went Wrong"))->error();
        return redirect()->back();
    }
}
