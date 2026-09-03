<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\ProfileImageRequest;
use App\Models\Member;
use App\Models\PackageUsage;
use App\Models\User;
use App\Models\ViewProfilePicture;
use App\Notifications\DbStoreNotification;
use App\Services\FirbaseNotification;
use App\Utility\EmailUtility;
use App\Utility\SmsUtility;
use DB;
use Illuminate\Http\Request;
use Kutia\Larafirebase\Facades\Larafirebase;
use Notification;

class ProfileImageController extends Controller
{
    public function image_view_request()
    {
        $my_profile_pic_view_requests = DB::table('view_profile_pictures')
            ->orderBy('id', 'desc')
            ->where('user_id', auth()->id())
            ->join('users', 'view_profile_pictures.user_id', '=', 'users.id')
            ->select('view_profile_pictures.id')
            ->distinct()
            ->paginate(10);
        return ProfileImageRequest::collection($my_profile_pic_view_requests);
        // return $this->response_data($my_profile_pic_view_requests);
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store_image_view_request(Request $request)
    {
        $auth_user = auth()->user();
        $exist_check = ViewProfilePicture::where('user_id', $request->id)->where('requested_by', $auth_user->id)->first();
        if (!$exist_check) {
            $view_profile_picture                 = new ViewProfilePicture;
            $view_profile_picture->user_id        = $request->id;
            $view_profile_picture->requested_by   = $auth_user->id;
            if ($view_profile_picture->save()) {
                $member = Member::where('user_id', $auth_user->id)->first();
                $member->remaining_profile_image_view = $member->remaining_profile_image_view - 1;
                $member->save();

                PackageUsage::record(
                    $auth_user->id,
                    'profile_image_view',
                    'Profile Image View',
                    1,
                    ViewProfilePicture::class,
                    $view_profile_picture->id,
                    'Used 1 coin to request profile image view.'
                );

                $notify_user = User::where('id', $request->id)->first();

                // View Profile Picture Store Notification for member
                try {
                    $notify_type   = 'profile_picture_view';
                    $id            = unique_notify_id();
                    $notify_by     = $auth_user->id;
                    $info_id       = $view_profile_picture->id;
                    $message       = $auth_user->first_name . ' ' . $auth_user->last_name . ' ' . translate(' wants to see your profile picture.');
                    $route         = 'profile-picture-view-request.index';

                    // fcm 
                    if (get_setting('firebase_push_notification') == 1) {
                        self::sendFirebaseNotification($notify_user->fcm_token, $notify_user, $notify_type, $message, $notify_by, $info_id);
                    }
                    // end of fcm

                    Notification::send($notify_user, new DbStoreNotification($notify_type, $id, $notify_by, $info_id, $message, $route));
                } catch (\Exception $e) {
                    // dd($e);
                }

                // View Profile Picture email send to member
                if ($notify_user->email != null && get_email_template('profile_picture_view_request_email', 'status')) {
                    EmailUtility::email_on_request($notify_user, 'profile_picture_view_request_email');
                }

                // View Profile Picture email SMS to member
                if ($notify_user->phone != null && addon_activation('otp_system') && (get_sms_template('profile_picture_view_request', 'status') == 1)) {
                    SmsUtility::sms_on_request($notify_user, 'profile_picture_view_request');
                }

                return $this->success_message('profile image view request sent successfully');
            } else {
                return $this->failure_message('Something went wrong');
            }
        } else {
            return $this->failure_message('Already requested');
        }
    }

    public function accept_image_view_request(Request $request)
    {
        $auth_user = auth()->user();
        $view_profile_picture = ViewProfilePicture::findOrFail($request->profile_pic_view_request_id);

        if ($view_profile_picture) {
            $view_profile_picture->status = 1;
            $view_profile_picture->save();
            $notify_user = User::where('id', $view_profile_picture->requested_by)->first();

            // Express Interest Store Notification for member
            try {
                $notify_type = 'accept_profile_picture_view_request';
                $id = unique_notify_id();
                $notify_by = $auth_user->id;
                $info_id = $view_profile_picture->id;
                $message = $auth_user->first_name . ' ' . $auth_user->last_name . ' ' . translate(' has accepted your profile picture view request.');
                $route = route("member_profile", $auth_user->id);

                // fcm 
                if (get_setting('firebase_push_notification') == 1) {
                    self::sendFirebaseNotification($notify_user->fcm_token, $notify_user, $notify_type, $message, $notify_by, $info_id);
                }
                // end of fcm

                Notification::send($notify_user, new DbStoreNotification($notify_type, $id, $notify_by, $info_id, $message, $route));
            } catch (\Exception $e) {
                // dd($e);
            }

            // View Profile Picture email send to member
            if ($notify_user->email != null && get_email_template('profile_picture_view_request_accepted_email', 'status')) {
                EmailUtility::email_on_accept_request($notify_user, 'profile_picture_view_request_accepted_email');
            }

            // View Profile Picture email SMS to member
            if ($notify_user->phone != null && addon_activation('otp_system') && (get_sms_template('profile_picture_view_request_accepted', 'status') == 1)) {
                SmsUtility::sms_on_accept_request($notify_user, 'profile_picture_view_request_accepted');
            }
            return $this->success_message('Interest has been accepted successfully.');
        } else {
            return $this->failure_message('Sorry! Did not find any request');
        }
    }

    public function reject_image_view_request(Request $request)
    {
        $auth_user = auth()->user();
        $profile_pic_view_request = ViewProfilePicture::findOrFail($request->profile_pic_view_request_id);

        if (ViewProfilePicture::destroy($request->profile_pic_view_request_id)) {

            $notify_user = User::where('id', $profile_pic_view_request->requested_by)->first();
            try {
                $notify_type = 'reject_profile_image_view_request';
                $id = unique_notify_id();
                $notify_by = auth()->id();
                $info_id = $profile_pic_view_request->id;
                $message = $auth_user->first_name . ' ' . $auth_user->last_name . ' ' . translate(' has rejected your profile picture view request.');
                $route = route('member.listing');

                // fcm 
                if (get_setting('firebase_push_notification') == 1) {
                    self::sendFirebaseNotification($notify_user->fcm_token, $notify_user, $notify_type, $message, $notify_by, $info_id);
                }
                // end of fcm

                Notification::send($notify_user, new DbStoreNotification($notify_type, $id, $notify_by, $info_id, $message, $route));
            } catch (\Exception $e) {
                // dd($e);
            }

            return $this->success_message('profile image view request has been rejected successfully.');
        } else {
            return $this->failure_message('Sorry! Did not find any request');
        }
    }

    public static function sendFirebaseNotification($fcmTokens = null, $notify_user = null, $notify_type = null, $message = null, $notify_by = null, $info_id = null)
    {
        // FCM v1, addressed by member rather than by token.
        //
        // This used to post to `fcm.googleapis.com/fcm/send`, the endpoint
        // Google retired in June 2024, and it discarded the result - so these
        // notifications reached nobody and nothing was logged. It was also
        // gated on `users.fcm_token`, a column the mobile app never writes:
        // the app registers per-device rows in `user_push_tokens`. Both of
        // those had to go for a member to be reachable on their phone.
        if (!$notify_user) {
            return;
        }

        try {
            \App\Services\FcmV1Service::sendToUser(
                (int) $notify_user->id,
                [
                    'title' => str_replace('_', ' ', (string) $notify_type),
                    'body'  => (string) $message,
                ],
                [
                    // The keys the app routes and de-duplicates on.
                    'type'      => (string) $notify_type,
                    'route'     => (string) $notify_type,
                    'notify_by' => (string) $notify_by,
                    'info_id'   => (string) $info_id,
                ],
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('FCM v1 push failed.', [
                'user_id' => $notify_user->id ?? null,
                'type'    => $notify_type,
                'error'   => $e->getMessage(),
            ]);
        }
    }
}
