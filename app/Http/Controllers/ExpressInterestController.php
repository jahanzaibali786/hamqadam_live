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
    protected function getEffectiveUserId(): int
    {
        $userId = (int) Auth::id();
        $guardianLink = \App\Models\FamilyGuardianLink::where('guardian_user_id', $userId)
            ->where('status', 'approved')
            ->whereNull('revoked_at')
            ->whereNull('paused_at')
            ->first();
        if ($guardianLink && (Auth::user()->member == null || empty(Auth::user()->member->birthday))) {
            return (int) $guardianLink->profile_user_id;
        }
        return $userId;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $id=$this->getEffectiveUserId();
        $tab=request('tab','received');
        if(!in_array($tab,['received','sent','accepted','rejected'],true))$tab='received';
        $counts=[
            'received'=>ExpressInterest::where('user_id',$id)->where('status',0)->count(),
            'sent'=>ExpressInterest::where('interested_by',$id)->count(),
            'accepted'=>ExpressInterest::where(fn($q)=>$q->where('user_id',$id)->orWhere('interested_by',$id))->where('status',1)->count(),
            'rejected'=>ExpressInterest::where(fn($q)=>$q->where('user_id',$id)->orWhere('interested_by',$id))->where('status',2)->count(),
        ];
        $interests=ExpressInterest::query()->with(['sender.member','recipient.member','sender.addresses.city','recipient.addresses.city'])
          ->when($tab==='received',fn($q)=>$q->where('user_id',$id)->where('status',0))
          ->when($tab==='sent',fn($q)=>$q->where('interested_by',$id))
          ->when($tab==='accepted',fn($q)=>$q->where(fn($x)=>$x->where('user_id',$id)->orWhere('interested_by',$id))->where('status',1))
          ->when($tab==='rejected',fn($q)=>$q->where(fn($x)=>$x->where('user_id',$id)->orWhere('interested_by',$id))->where('status',2))
          ->latest()->paginate(6)->withQueryString();
        return view('frontend.member.my_interests',compact('interests','counts','tab'))->with('subjectUserId', $id);
    }

    public function interest_requests()
    {
        $id=$this->getEffectiveUserId();
        $tab=request('tab','received');
        if(!in_array($tab,['received','sent','accepted','declined','in_progress'],true))$tab='received';
        $counts=[
          'received'=>ExpressInterest::where('user_id',$id)->where('status',0)->count(),
          'sent'=>ExpressInterest::where('interested_by',$id)->count(),
          'accepted'=>ExpressInterest::where(fn($q)=>$q->where('user_id',$id)->orWhere('interested_by',$id))->where('status',1)->count(),
          'declined'=>ExpressInterest::where(fn($q)=>$q->where('user_id',$id)->orWhere('interested_by',$id))->where('status',2)->count(),
          'in_progress'=>ExpressInterest::where(fn($q)=>$q->where('user_id',$id)->orWhere('interested_by',$id))->where('status',1)->whereHas('meetings')->count(),
        ];
        $interests=ExpressInterest::query()->with(['sender.member','recipient.member','sender.addresses.city','recipient.addresses.city'])
          ->when($tab==='received',fn($q)=>$q->where('user_id',$id)->where('status',0))
          ->when($tab==='sent',fn($q)=>$q->where('interested_by',$id))
          ->when($tab==='accepted',fn($q)=>$q->where(fn($x)=>$x->where('user_id',$id)->orWhere('interested_by',$id))->where('status',1))
          ->when($tab==='declined',fn($q)=>$q->where(fn($x)=>$x->where('user_id',$id)->orWhere('interested_by',$id))->where('status',2))
          ->when($tab==='in_progress',fn($q)=>$q->where(fn($x)=>$x->where('user_id',$id)->orWhere('interested_by',$id))->where('status',1)->whereHas('meetings'))
          ->latest()->paginate(6)->withQueryString();
        return view('frontend.member.interest_requests',compact('interests','counts','tab'))->with('subjectUserId', $id);
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
        $quotaUser = $interested_by_user;
        $guardianLink = \App\Models\FamilyGuardianLink::where('guardian_user_id', $interested_by_user->id)
            ->where('status', 'approved')
            ->whereNull('revoked_at')
            ->first();
        if ($guardianLink) {
            $quotaUser = \App\Models\User::find($guardianLink->profile_user_id) ?? $interested_by_user;
        }

        $interested_by_member = $quotaUser->member;
        $coinCost = feature_coin_cost('express_interest', 1);

        if ($interested_by_member && $interested_by_member->remaining_interest >= $coinCost) {
            // Store express interest data
            $express_interest                 = new ExpressInterest;
            // Check interest does not shown on self
            if ($request->id != $interested_by_user->id && $request->id != $quotaUser->id) {
                $express_interest->user_id        = $request->id;
                $express_interest->interested_by  = $quotaUser->id;
                if ($express_interest->save()) {
                    // Deduct interested by user's remaining express interest value
                    $interested_by_member->remaining_interest -= $coinCost;
                    $interested_by_member->save();

                    PackageUsage::record(
                        $quotaUser->id,
                        'interest',
                        'Express Interest',
                        $coinCost,
                        ExpressInterest::class,
                        $express_interest->id,
                        'Used ' . $coinCost . ' coin(s) to send express interest' . ($guardianLink ? ' (via Guardian ' . $interested_by_user->first_name . ')' : '') . '.'
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
        $data=$request->validate(['interest_id'=>'required|integer']);
        $interest=ExpressInterest::where('interaction_type','interest')->findOrFail($data['interest_id']);
        app(\App\Services\MemberConnectionWorkflow::class)->respondInterest(Auth::user(),$interest,true);
        flash(translate('Interest has been accepted successfully.'))->success();
        return redirect()->route('interest_requests');
    }

    public function reject_interest(Request $request)
    {
        $data=$request->validate(['interest_id'=>'required|integer']);
        $interest=ExpressInterest::where('interaction_type','interest')->findOrFail($data['interest_id']);
        app(\App\Services\MemberConnectionWorkflow::class)->respondInterest(Auth::user(),$interest,false);
        flash(translate('Interest has been declined.'))->success();
        return redirect()->route('interest_requests');
    }

    public function remove_interest(Request $request)
    {
        $data=$request->validate(['interest_id'=>'required|integer']);
        $interest=ExpressInterest::where('interaction_type','interest')->where('user_id',Auth::id())
            ->where('status',1)->findOrFail($data['interest_id']);
        $interest->status=\App\Enums\ProposalStatus::Cancelled;
        $interest->save();
        return redirect()->route('interest_requests')->with('success','Connection revoked.');
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
