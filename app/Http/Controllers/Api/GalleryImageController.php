<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Controller;
use App\Http\Resources\GalleryImageRequest;
use App\Http\Resources\GalleryImageResource;
use App\Models\GalleryImage;
use App\Models\Member;
use App\Models\PackageUsage;
use App\Models\User;
use App\Models\ViewGalleryImage;
use App\Notifications\DbStoreNotification;
use App\Services\FirbaseNotification;
use App\Utility\EmailUtility;
use App\Utility\SmsUtility;
use DB;
use Illuminate\Http\Request;
use Kutia\Larafirebase\Facades\Larafirebase;
use Notification;


class GalleryImageController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function index()
    {
        $gallery_image_id = GalleryImage::where('user_id', request()->user()->id)->latest()->get();
        return GalleryImageResource::collection($gallery_image_id)->additional([
            'result' => true
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (package_validity(auth()->user()->id)) {
            if (get_remaining_package_value(auth()->user()->id, 'remaining_photo_gallery') > 0) {
                // image upload
                $photo = null;
                if ($request->hasFile('gallery_image')) {
                    $photo = upload_api_file($request->file('gallery_image'));
                }
                // $gallery_images = [];
                // if ($request->hasFile('gallery_images')) {             
                //     foreach ($request->file('gallery_images') as $key => $gallery_image) {
                //         $photo = upload_api_file($gallery_image);
                //         $gallery_images[] = $photo;
                //     }                  
                // }
                // $gallery_images = implode(',', $gallery_images);

                $galleryImage = GalleryImage::create([
                    'user_id' => auth()->user()->id,
                    'image'   => $photo
                ]);

                $member = Member::where('user_id', auth()->user()->id)->first();
                $member->remaining_photo_gallery = $member->remaining_photo_gallery - 1;
                $member->save();

                PackageUsage::record(
                    auth()->id(),
                    'gallery_photo_upload',
                    'Gallery Photo Upload',
                    1,
                    GalleryImage::class,
                    $galleryImage->id,
                    'Used 1 coin to upload a gallery photo.'
                );

                return $this->success_message('Gallery image uploaded successfully.');
            }
            return $this->failure_message('You have 0 remaining gallery photo upload. Please update your package.');
        }
        return $this->failure_message('Your package has been expired. Please update your package.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (GalleryImage::destroy($id)) {
            return $this->success_message('Image deleted successfully.');
        }
        return $this->failure_message('Sorry! Something went wrong.');
    }

    public function image_view_request()
    {
        $my_gallery_image_view_requests = DB::table('view_gallery_images')
            ->orderBy('id', 'desc')
            ->where('user_id', auth()->id())
            ->join('users', 'view_gallery_images.user_id', '=', 'users.id')
            ->select('view_gallery_images.id')
            ->distinct()
            ->paginate(10);
        return GalleryImageRequest::collection($my_gallery_image_view_requests);
        // return $this->response_data($my_gallery_image_view_requests);
    }
    public function store_image_view_request(Request $request)
    {
        $auth_user = auth()->user();
        $exist_check = ViewGalleryImage::where('user_id', $request->id)->where('requested_by', $auth_user->id)->first();
        if (!$exist_check) {
            $view_gallert_image                = new ViewGalleryImage();
            $view_gallert_image->user_id       = $request->id;
            $view_gallert_image->requested_by  = $auth_user->id;
            if ($view_gallert_image->save()) {
                $member = Member::where('user_id', $auth_user->id)->first();
                $member->remaining_gallery_image_view = $member->remaining_gallery_image_view - 1;
                $member->save();

                PackageUsage::record(
                    $auth_user->id,
                    'gallery_image_view',
                    'Gallery Image View',
                    1,
                    ViewGalleryImage::class,
                    $view_gallert_image->id,
                    'Used 1 coin to request gallery image view.'
                );

                $notify_user = User::where('id', $request->id)->first();

                // View Profile Picture Store Notification for member
                try {
                    $notify_type   = 'gallery_image_view';
                    $id            = unique_notify_id();
                    $notify_by     = $auth_user->id;
                    $info_id       = $view_gallert_image->id;
                    $message       = $auth_user->first_name . ' ' . $auth_user->last_name . ' ' . translate(' wants to see your gallery images.');
                    $route         = 'gallery-image-view-request.index';

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
                if ($notify_user->email != null && get_email_template('gallery_image_view_request_email', 'status')) {
                    EmailUtility::email_on_request($notify_user, 'gallery_image_view_request_email');
                }

                // View Profile Picture email SMS to member
                if ($notify_user->phone != null && addon_activation('otp_system') && (get_sms_template('gallery_image_view_request', 'status') == 1)) {
                    SmsUtility::sms_on_request($notify_user, 'gallery_image_view_request');
                }

                return $this->success_message('gallery image view request sent successfully');
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
        $view_gallery_image = ViewGalleryImage::findOrFail($request->gallery_image_view_request_id);
        //   dd($view_gallery_image);
        $view_gallery_image->status = 1;
        $view_gallery_image->save();
        if ($view_gallery_image) {

            $notify_user = User::where('id', $view_gallery_image->requested_by)->first();

            // Express Interest Store Notification for member
            try {
                $notify_type = 'accept_gallery_image_view_request';
                $id = unique_notify_id();
                $notify_by = $auth_user->id;
                $info_id = $view_gallery_image->id;
                $message = $auth_user->first_name . ' ' . $auth_user->last_name . ' ' . translate(' has accepted your gallery image view request.');
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
            if ($notify_user->email != null && get_email_template('gallery_image_view_request_accepted_email', 'status')) {
                EmailUtility::email_on_accept_request($notify_user, 'gallery_image_view_request_accepted_email');
            }

            // View Profile Picture email SMS to member
            if ($notify_user->phone != null && addon_activation('otp_system') && (get_sms_template('gallery_image_view_request_accepted', 'status') == 1)) {
                SmsUtility::sms_on_accept_request($notify_user, 'gallery_image_view_request_accepted');
            }

            return $this->success_message('Interest has been accepted successfully.');
        } else {
            return $this->failure_message('Sorry! Did not find any request.');
        }
    }
    public function reject_image_view_request(Request $request)
    {
        $auth_user = auth()->user();
        $gallery_view_request = ViewGalleryImage::findOrFail($request->gallery_image_view_request_id);

        if (ViewGalleryImage::destroy($request->gallery_image_view_request_id)) {

            $notify_user = User::where('id', $gallery_view_request->requested_by)->first();
            try {
                $notify_type = 'reject_gallery_image_view_request';
                $id = unique_notify_id();
                $notify_by = auth()->id();
                $info_id = $gallery_view_request->id;
                $message = $auth_user->first_name . ' ' . $auth_user->last_name . ' ' . translate(' has rejected your gallery image view request.');
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

            return $this->success_message('gallery image view request has been rejected successfully.');
        } else {
            return $this->failure_message('Sorry! Something went wrong.');
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
