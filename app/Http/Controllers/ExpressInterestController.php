<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ChatThread;
use App\Models\ExpressInterest;
use App\Models\PackageUsage;
use App\Models\User;
use App\Notifications\DbStoreNotification;
use App\Services\FirbaseNotification;
use App\Utility\EmailUtility;
use App\Utility\SmsUtility;
use Auth;
use DB;
use Illuminate\Http\Request;
use Kutia\Larafirebase\Facades\Larafirebase;
use Notification;

class ExpressInterestController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $interests = DB::table('express_interests')
            ->orderBy('id', 'desc')
            ->where('interested_by', Auth::user()->id)
            ->join('users', 'express_interests.user_id', '=', 'users.id')
            ->select('express_interests.id')
            ->distinct()
            ->paginate(10);

        return view('frontend.member.my_interests', compact('interests'));
    }

    public function interest_requests()
    {
        $status = request('status');
        $statusMap = [
            'pending' => 0,
            'accepted' => 1,
            'rejected' => 2,
            'withdrawn' => 3,
            'cancelled' => 4,
            'expired' => 5,
        ];

        $interests = ExpressInterest::with('sender.member')
            ->where('user_id', Auth::user()->id)
            ->when(isset($statusMap[$status]), fn ($query) => $query->where('status', $statusMap[$status]))
            ->latest()
            ->paginate(10)
            ->appends(['status' => $status]);

        if (request()->ajax() || request()->boolean('partial')) {
            return response()->json([
                'html' => view('frontend.member.partials.interest_requests_table', compact('interests'))->render(),
            ]);
        }

        return view('frontend.member.interest_requests', compact('interests'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $interested_by_user = Auth::user();
        $interested_by_member = $interested_by_user->member;
        $coinCost = feature_coin_cost('express_interest', 1);

        if ($interested_by_member->remaining_interest >= $coinCost) {
            // Store express interest data
            $express_interest                 = new ExpressInterest;
            // Check interest does not shown on self
            if ($request->id != $interested_by_user->id) {
                $express_interest->user_id        = $request->id;
                $express_interest->interested_by  = $interested_by_user->id;
                if ($express_interest->save()) {
                    // Deduct interested by user's remaining express interest value
                    $interested_by_member->remaining_interest -= $coinCost;
                    $interested_by_member->save();

                    PackageUsage::record(
                        $interested_by_user->id,
                        'interest',
                        'Express Interest',
                        $coinCost,
                        ExpressInterest::class,
                        $express_interest->id,
                        'Used ' . $coinCost . ' coin(s) to send express interest.'
                    );

                    $notify_user = User::where('id', $request->id)->first();

                    // Express Interest Store Notification for member
                    try {
                        $notify_type = 'express_interest';
                        $id = unique_notify_id();
                        $notify_by = $interested_by_user->id;
                        $info_id = $express_interest->id;
                        $message = $interested_by_user->first_name . ' ' . $interested_by_user->last_name . ' ' . translate(' has Expressed Interest On You.');
                        $route = route('interest_requests');

                        // fcm 
                        if (get_setting('firebase_push_notification') == 1) {
                            $fcmTokens = User::where('id', $request->id)->whereNotNull('fcm_token')->pluck('fcm_token')->toArray();
                            self::sendFirebaseNotification($fcmTokens, $notify_user, $notify_type, $message, $notify_by, $info_id);
                        }
                        // end of fcm

                        Notification::send($notify_user, new DbStoreNotification($notify_type, $id, $notify_by, $info_id, $message, $route));
                    } catch (\Exception $e) {
                        // dd($e);
                    }

                    // Express Interest email send to member
                    if ($notify_user->email != null && get_email_template('email_on_express_interest', 'status')) {
                        EmailUtility::email_on_request($notify_user, 'email_on_express_interest');
                    }

                    // Express Interest Send SMS to member
                    if ($notify_user->phone != null && addon_activation('otp_system') && (get_sms_template('express_interest', 'status') == 1)) {
                        SmsUtility::sms_on_request($notify_user, 'express_interest');
                    }

                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function accept_interest(Request $request)
    {
        $interest = ExpressInterest::findOrFail($request->interest_id);
        $interest->status = 1;
        if ($interest->save()) {
            // $existing_chat_thread = ChatThread::where('sender_user_id', $interest->interested_by)->where('receiver_user_id', $interest->user_id)->first();

            $existing_chat_thread = ChatThread::where(function ($query) use ($interest) {
                $query->where('sender_user_id', $interest->interested_by)->where('receiver_user_id', $interest->user_id);
            })->orWhere(function ($query) use ($interest) {
                $query->where('receiver_user_id', $interest->interested_by)->where('sender_user_id', $interest->user_id);
            })->first();

            if ($existing_chat_thread == null) {
                $chat_thread                    = new ChatThread;
                $chat_thread->thread_code       = $interest->interested_by . date('Ymd') . $interest->user_id;
                $chat_thread->sender_user_id    = $interest->interested_by;
                $chat_thread->receiver_user_id  = $interest->user_id;
                $chat_thread->save();
            }

            $notify_user = User::where('id', $interest->interested_by)->first();

            // Express Interest Store Notification for member
            try {
                $notify_type = 'accept_interest';
                $id = unique_notify_id();
                $notify_by = Auth::user()->id;
                $info_id = $interest->id;
                $message = Auth::user()->first_name . ' ' . Auth::user()->last_name . ' ' . translate(' has accepted your interest.');
                $route = route('my_interests.index');

                // fcm 
                if (get_setting('firebase_push_notification') == 1) {
                    $fcmTokens = User::where('id', $interest->interested_by)->whereNotNull('fcm_token')->pluck('fcm_token')->toArray();
                    self::sendFirebaseNotification($fcmTokens, $notify_user, $notify_type, $message, $notify_by, $info_id);
                }
                // end of fcm

                Notification::send($notify_user, new DbStoreNotification($notify_type, $id, $notify_by, $info_id, $message, $route));
            } catch (\Exception $e) {
                // dd($e);
            }

            // Express Interest email send to member
            if ($notify_user->email != null && get_email_template('email_on_accepting_interest', 'status')) {
                EmailUtility::email_on_accept_request($notify_user, 'email_on_accepting_interest');
            }

            // Express Interest Send SMS to member
            if ($notify_user->phone != null && addon_activation('otp_system') && (get_sms_template('accept_interest', 'status') == 1)) {
                SmsUtility::sms_on_accept_request($notify_user, 'accept_interest');
            }
            flash(translate('Interest has been accepted successfully.'))->success();
            return redirect()->route('interest_requests');
        } else {
            flash(translate('Sorry! Something went wrong.'))->error();
            return back();
        }
    }

    public function reject_interest(Request $request)
    {
        $interest = ExpressInterest::findOrFail($request->interest_id);

        if (ExpressInterest::destroy($request->interest_id)) {

            $notify_user = User::where('id', $interest->user_id)->first();
            try {
                $notify_type = 'reject_interest';
                $id = unique_notify_id();
                $notify_by = Auth::user()->id;
                $info_id = $interest->id;
                $message = Auth::user()->first_name . ' ' . Auth::user()->last_name . ' ' . translate(' has rejected your interest.');
                $route = route('interest_requests');

                // fcm 
                if (get_setting('firebase_push_notification') == 1) {
                    $fcmTokens = User::where('id', $interest->user_id)->whereNotNull('fcm_token')->pluck('fcm_token')->toArray();
                    self::sendFirebaseNotification($fcmTokens, $notify_user, $notify_type, $message, $notify_by, $info_id);
                }
                // end of fcm

                Notification::send($notify_user, new DbStoreNotification($notify_type, $id, $notify_by, $info_id, $message, $route));
            } catch (\Exception $e) {
                // dd($e);
            }

            flash(translate('Interest has been rejected successfully.'))->success();
            return redirect()->back();
        } else {
            flash(translate('Sorry! Something went wrong.'))->error();
            return back();
        }
    }

    public function remove_interest(Request $request)
    {
        $interest = ExpressInterest::where('user_id', Auth::id())
            ->where('status', 1)
            ->findOrFail($request->interest_id);

        if (ExpressInterest::destroy($interest->id)) {
            flash(translate('Accepted interest has been removed successfully.'))->success();
            return redirect()->back();
        }

        flash(translate('Sorry! Something went wrong.'))->error();
        return back();
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
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
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
        //
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
