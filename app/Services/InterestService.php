<?php

namespace App\Services;

use App\Models\ChatThread;
use App\Models\ExpressInterest;
use App\Enums\ProposalStatus;
use App\Models\PackageUsage;
use App\Models\User;
use App\Notifications\DbStoreNotification;
use App\Utility\EmailUtility;
use App\Utility\SmsUtility;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Kutia\Larafirebase\Facades\Larafirebase;

class InterestService
{

      public function store($user_id)
      {
            $interested_by_user = auth()->user();
            $interested_by_member = $interested_by_user->member;

            // Nobody may express interest in themselves. The web controller
            // guarded this; this service did not, so the API allowed it and
            // charged a coin for it.
            if ((int) $user_id === (int) $interested_by_user->id) {
                  return false;
            }

            /*
             * Use the configured coin cost rather than a hardcoded 1. The web
             * flow already used feature_coin_cost(), so with an admin cost of
             * (say) 3 the website charged 3 and the API charged 1 for the same
             * action.
             */
            $coinCost = feature_coin_cost('express_interest', 1);

            if ($interested_by_member->remaining_interest >= $coinCost) {
                  /*
                   * Row insert + coin deduction + usage log must be atomic.
                   * They were not: when PackageUsage::record() failed (its table
                   * was missing on one environment) the interest row and the
                   * coin deduction had already committed, so the member was
                   * charged for an interest the caller was told had failed.
                   */
                  $express_interest = DB::transaction(function () use ($user_id, $interested_by_user, $interested_by_member, $coinCost) {
                        $express_interest                 = new ExpressInterest;
                        $express_interest->user_id        = $user_id;
                        $express_interest->interested_by  = $interested_by_user->id;
                        $express_interest->save();

                        // Deduct interested by user's remaining express interest value
                        $interested_by_member->remaining_interest -= $coinCost;
                        $interested_by_member->save();

                        // Record the spend. The web flow logged this and the API
                        // did not, so coin usage reports silently missed every
                        // interest sent from the mobile app.
                        PackageUsage::record(
                              $interested_by_user->id,
                              'interest',
                              'Express Interest',
                              $coinCost,
                              ExpressInterest::class,
                              $express_interest->id,
                              'Used ' . $coinCost . ' coin(s) to send express interest.'
                        );

                        return $express_interest;
                  });

                  $notify_user = User::where('id', $user_id)->first();

                  $notify_type = 'express_interest';
                  $notify_by = $interested_by_user->id;
                  $info_id = $express_interest->id;
                  $message = $interested_by_user->first_name . ' ' . $interested_by_user->last_name . ' ' . translate(' has Expressed Interest On You.');
                  $route = route('interest_requests');

                  $this->notifyUser($user_id, $notify_user, $notify_type, $notify_by, $info_id, $message, $route);

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
      }

      public function accept($interest_id)
      {
            $interest = ExpressInterest::find($interest_id);
            if ($interest) {
                  $interest->status = ProposalStatus::Accepted;
                  // responded_at exists on this table but was never set, so
                  // there was no record of WHEN a proposal was answered.
                  $interest->responded_at = now();
                  $interest->save();

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

                  $notify_type = 'accept_interest';
                  $notify_by = auth()->user()->id;
                  $info_id = $interest->id;
                  $message = auth()->user()->first_name . ' ' . auth()->user()->last_name . ' ' . translate(' has accepted your interest.');
                  $route = route('my_interests.index');

                  $this->notifyUser($interest->interested_by, $notify_user, $notify_type, $notify_by, $info_id, $message, $route);
                  // Express Interest email send to member
                  if ($notify_user->email != null && get_email_template('email_on_accepting_interest', 'status')) {
                        EmailUtility::email_on_accept_request($notify_user, 'email_on_accepting_interest');
                  }

                  // Express Interest Send SMS to member
                  if ($notify_user->phone != null && addon_activation('otp_system') && (get_sms_template('accept_interest', 'status') == 1)) {
                        SmsUtility::sms_on_accept_request($notify_user, 'accept_interest');
                  }

                  return true;
            }
            return false;
      }

      public function reject($interest_id)
      {
            $interest = ExpressInterest::find($interest_id);
            if ($interest) {
                  /*
                   * Mark it rejected rather than deleting the row.
                   *
                   * ExpressInterest has a Rejected status and the web
                   * interest-requests screen already offers a status filter for
                   * it, so a hard delete made that filter permanently empty,
                   * threw away the history, and let the sender express interest
                   * again - paying a second time for the same rejected request.
                   */
                  $interest->status = ProposalStatus::Rejected;
                  $interest->responded_at = now();
                  $interest->save();

                  $notify_user = User::where('id', $interest->user_id)->first();

                  $notify_type = 'reject_interest';
                  $notify_by = auth()->user()->id;
                  $info_id = $interest->id;
                  $message = auth()->user()->first_name . ' ' . auth()->user()->last_name . ' ' . translate(' has rejected your interest.');
                  $route = route('interest_requests');

                  $this->notifyUser($interest->user_id, $notify_user, $notify_type, $notify_by, $info_id, $message, $route);

                  return true;
            }
            return false;
      }

      public function notifyUser($user_id, $notify_user, $notify_type, $notify_by, $info_id, $message, $route)
      {
            try {
                  $id = unique_notify_id();

                  // FCM v1 push notification (replaces legacy Larafirebase + FirbaseNotification)
                  if ($notify_user) {
                        try {
                              // Every device, not just whichever registered
                              // last into the shared users.fcm_token column.
                              \App\Services\FcmV1Service::sendToUser(
                                    (int) $notify_user->id,
                                    ['title' => $notify_type, 'body' => $message],
                                    ['type' => $notify_type, 'notify_by' => (string) $notify_by, 'info_id' => (string) $info_id],
                              );
                        } catch (\Throwable $e) {
                              \Illuminate\Support\Facades\Log::warning('FCM v1 push failed in InterestService.', ['error' => $e->getMessage()]);
                        }
                  }

                  // Store in database (Laravel notification table)
                  Notification::send($notify_user, new DbStoreNotification($notify_type, $id, $notify_by, $info_id, $message, $route));
                  return true;
            } catch (\Exception $e) {
                  return false;
            }
      }
}
