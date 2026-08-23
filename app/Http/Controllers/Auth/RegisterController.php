<?php

namespace App\Http\Controllers\Auth;

use App\Jobs\RunAiVerification;
use App\Models\AiVerificationAttempt;
use Notification;
use App\Models\User;
use App\Models\Member;
use App\Models\Package;
use App\Rules\RecaptchaRule;
use App\Support\RegistrationOnboarding;
use App\Support\RegistrationReward;
use Illuminate\Http\Request;
use App\Models\EmailTemplate;
use App\Utility\EmailUtility;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;
use App\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use App\Notifications\DbStoreNotification;
use Kutia\Larafirebase\Facades\Larafirebase;
use App\Http\Controllers\OTPVerificationController;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */


    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */

    public function showRegistrationForm()
    {
        $on_behalves = \App\Models\OnBehalf::all();
        return view('frontend.user_registration', compact('on_behalves'));
    }

    protected function validator(array $data)
    {
        $validator = Validator::make($data, [
            'on_behalf'            => 'nullable|integer',
            'first_name'           => ['required', 'string', 'max:255'],
            'last_name'            => ['required', 'string', 'max:255'],
            'gender'               => 'required',
            'date_of_birth'        => 'required|date',
            'phone'                 => 'required_without:email|nullable|string|unique:users',
            'email'                 => 'required_without:phone|nullable|email|unique:users',
            'password'             => ['required', 'string', 'min:8', 'confirmed'],
            'g-recaptcha-response' => [
                Rule::when(get_setting('google_recaptcha_activation') == 1 && get_setting('recaptcha_user_register') == 1, ['required', new RecaptchaRule()], ['sometimes'])
            ],
            'checkbox_example_1'   => ['required', 'string'],
        ] + RegistrationOnboarding::rules(),
        [
            //'on_behalf.required' => translate('on_behalf is required'),
            'on_behalf.integer' => translate('on_behalf should be integer value'),
            'first_name.required' => translate('first_name is required'),
            'last_name.required' => translate('last_name is required'),
            'gender.required' => translate('gender is required'),
            'date_of_birth.required' => translate('date_of_birth is required'),
            'date_of_birth.date' => translate('date_of_birth should be in date format'),
            'email.required' => translate('Email is required'),
            'email.email' => translate('Email must be a valid email address'),
            'email.unique' => translate('An user exists with this email'),
            'phone.unique' => translate('An user exists with this phone'),
            'phone.required' => translate('Phone is required'),
            'password.required' => translate('Password is required'),
            'password.confirmed' => translate('Password confirmation does not match'),
            'password.min' => translate('Minimum 8 digits required for password'),
            'checkbox_example_1.required'    => translate('You must agree to our terms and conditions.'),
        ]);

        $validator->setAttributeNames([
            'mother_tongue' => translate('Mother Language'),
            'religion_id' => translate('Religion'),
            'country_id' => translate('Country'),
            'state_id' => translate('Province / State'),
            'city_id' => translate('City'),
            'education_institution' => translate('College / University'),
        ]);

        return $validator;
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\Models\User
     */
    protected function create(array $data)
    {
        $approval = 1;
        if (filter_var($data['email'] ?? null, FILTER_VALIDATE_EMAIL)) {
            $user = User::create([
                'first_name'  => $data['first_name'],
                'last_name'   => $data['last_name'],
                'membership'  => 0,
                'email'       => $data['email'],
                'phone'       => !empty($data['phone']) ? '+' . ($data['country_code'] ?? '') . preg_replace('/\\D+/', '', $data['phone']) : null,
                'password'    => Hash::make($data['password']),
                'code'        => unique_code(),
                'approved'    => $approval,
            ]);
        } else {
            if (addon_activation('otp_system')) {
                $cleanPhone = preg_replace('/\D+/', '', $data['phone']);
                $user = User::create([
                    'first_name'  => $data['first_name'],
                    'last_name'   => $data['last_name'],
                    'membership'  => 0,
                    'phone'       => '+' . $data['country_code'] . $cleanPhone,
                    'password'    => Hash::make($data['password']),
                    'code'        => unique_code(),
                    'approved'    => $approval,
                    'verification_code' => rand(100000, 999999)
                ]);
            }
        }
        if (addon_activation('referral_system') && $data['referral_code'] != null) {
            $reffered_user = User::where('code', '!=', null)->where('code', $data['referral_code'])->first();
            if ($reffered_user != null) {
                $user->referred_by = $reffered_user->id;
                $user->save();
            }
        }

        $member                             = new Member;
        $member->user_id                    = $user->id;
        $member->save();

        $member->gender                     = $data['gender'];
        $member->on_behalves_id             = $data['on_behalf'] ?? null;
        $member->birthday                   = date('Y-m-d', strtotime($data['date_of_birth']));

        $member->save();

        RegistrationOnboarding::persist($user, $data);
        RegistrationReward::applyBasicPackage($user);


        // Account opening Email to member
        if ($data['email'] != null  && env('MAIL_USERNAME') != null) {
            $account_oppening_email = EmailTemplate::where('identifier', 'account_oppening_email')->first();
            if ($account_oppening_email->status == 1) {
                EmailUtility::account_oppening_email($user->id, $data['password']);
            }
        }

        return $user;
    }

    public function register(Request $request)
    {
        // Check if this is an AJAX request (from our stepwise form)
        $isAjax = $request->ajax() || $request->wantsJson();

        if (filter_var($request->email, FILTER_VALIDATE_EMAIL)) {
            if (User::where('email', $request->email)->first() != null) {
                if ($isAjax) {
                    return response()->json([
                        'success' => false,
                        'message' => translate('Email or Phone already exists.')
                    ], 422);
                }
                flash(translate('Email or Phone already exists.'));
                return back();
            }
        } elseif (User::where('phone', '+' . $request->country_code . $request->phone)->first() != null) {
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => translate('Phone already exists.')
                ], 422);
            }
            flash(translate('Phone already exists.'));
            return back();
        }

        try {
            $this->validator($request->all())->validate();
        } catch (ValidationException $e) {
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors()
                ], 422);
            }
            throw $e;
        }

        $user = $this->create($request->all());
        auth()->login($user);

        // Apply default package registration reward
        RegistrationReward::applyBasicPackage($user);

        /*
         * AI identity verification is NOT dispatched here any more. The member
         * is redirected to the identity gate (registered() below), which runs
         * the model synchronously so it can wait for the answer and route on
         * the outcome. Dispatching here as well would call the model twice for
         * every registration.
         */

        try {
            $notify_type = 'member_registration';
            $id = unique_notify_id();
            $notify_by = $user->id;
            $info_id = $user->id;
            $message = translate('A new member has been registered to your system. Name: ') . $user->first_name . ' ' . $user->last_name;
            
            if($user->membership === 2){
                $route = route('premium.members.index');
            }elseif($user->membership === 1){
                $route = route('free.members.index');
            }else{
                $route = route('unsubscribed.members.index');
            }

            // fcm 
            if (get_setting('firebase_push_notification') == 1) {
                $fcmTokens = User::where('user_type', 'admin')->whereNotNull('fcm_token')->pluck('fcm_token')->toArray();
                Larafirebase::withTitle($notify_type)
                    ->withBody($message)
                    ->sendMessage($fcmTokens);
            }
            // end of fcm
            Notification::send(User::where('user_type', 'admin')->first(), new DbStoreNotification($notify_type, $id, $notify_by, $info_id, $message, $route));
        } catch (\Exception $e) {
            // dd($e);
        }
        if (env('MAIL_USERNAME') != null && (get_email_template('account_opening_email_to_admin', 'status') == 1)) {
            $admin = User::where('user_type', 'admin')->first();
            EmailUtility::account_opening_email_to_admin($user, $admin);
        }


        if($user->email != null){
            $user->email_verified_at = now();
            $user->approved = 1;
            $user->save();
            $successMessage = translate('Registration successful. Your Basic package reward has been activated automatically.');
        } else {
            $successMessage = translate('Registration successful.');
        }

        if($user->phone != null){
            $user->email_verified_at = now();
            $user->approved = 1;
            $user->save();
        }

        // Handle AJAX response
        if ($isAjax) {
            $redirectRoute = $this->registered($request, $user);
            return response()->json([
                'success' => true,
                'message' => $successMessage,
                'redirect' => $redirectRoute ? $redirectRoute->getTargetUrl() : route('dashboard')
            ]);
        }

        flash($successMessage)->success();

        return $this->registered($request, $user)
            ?: redirect($this->redirectPath());
    }

    protected function registered(Request $request, $user)
    {
        //?? where should redirect user after registration
        if ($user->email == null && $user->email_verified_at == null) {
            return redirect()->route('verification');
        }

        /*
         * Straight to the AI identity gate rather than the dashboard. The
         * account is already created and the member already signed in - the
         * gate only waits for the model and then routes: verified members go
         * to the dashboard, everyone else is signed out and sent to login,
         * still registered but unverified, with the dashboard's verification
         * button waiting for them.
         */
        return redirect()->route('register.ai_verification');
    }
}
