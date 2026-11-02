<?php
namespace App\Services;
use App\Models\{User,Member,ProfileLike,ProfileSuperLike,PackageUsage,HiddenProfileUser,IgnoredUser};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class ProfileAppreciationService {
 public function eligible(User $actor,int $recipient): bool {
  if ($recipient<1 || $recipient===(int)$actor->id)return false;
  if (!User::whereKey($recipient)->where('user_type','member')->where('approved',1)->where('blocked',0)->where('deactivated',0)->whereHas('member',fn($q)=>$q->where(fn($s)=>$s->where('hide_profile',0)->orWhereNull('hide_profile')))->whereDoesntHave('profile_privacy_setting',fn($q)=>$q->where('invisible_mode',true))->exists())return false;
  if (IgnoredUser::where(function($query)use($actor,$recipient){$query->where(fn($q)=>$q->where('ignored_by',$actor->id)->where('user_id',$recipient))->orWhere(fn($q)=>$q->where('ignored_by',$recipient)->where('user_id',$actor->id));})->exists())return false;
  return !HiddenProfileUser::where('user_id',$recipient)->where('hidden_from_user_id',$actor->id)->exists();
 }
 public function toggleLike(User $actor,int $recipient): bool {
  if(!$this->eligible($actor,$recipient))throw ValidationException::withMessages(['member'=>'This profile is unavailable.']);
  $created=DB::transaction(function()use($actor,$recipient){
   User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
   $query=ProfileLike::where('sender_id',$actor->id)->where('recipient_id',$recipient);
   if($query->exists()){$query->delete();return false;}
   ProfileLike::create(['sender_id'=>$actor->id,'recipient_id'=>$recipient]);return true;
  },3);
  if($created)$this->notify($actor,$recipient,'profile_liked','like-'.$actor->id.'-'.$recipient);
  return $created;
 }
 public function superLike(User $actor,int $recipient,int $quotedCost): array {
  if(!$this->eligible($actor,$recipient))throw ValidationException::withMessages(['member'=>'This profile is unavailable.']);
  $result=DB::transaction(function()use($actor,$recipient){
   $member=Member::where('user_id',$actor->id)->lockForUpdate()->firstOrFail();
   $previous=ProfileSuperLike::where('sender_id',$actor->id)->where('recipient_id',$recipient)->first();
   if($previous)return ['created'=>false,'balance'=>(int)$member->remaining_interest,'cost'=>(int)$previous->coins_charged];
   $cost=max(0,(int)get_setting('feature_coin_cost_super_like',10));
   if($quotedCost!==$cost)throw ValidationException::withMessages(['coins'=>'Super Like coin price changed. Refresh and confirm the current price.']);
   if((int)$member->remaining_interest<$cost)throw ValidationException::withMessages(['coins'=>'Insufficient coins. Please add coins to continue.']);
   $record=ProfileSuperLike::create(['sender_id'=>$actor->id,'recipient_id'=>$recipient,'coins_charged'=>$cost]);
   if($cost){$member->decrement('remaining_interest',$cost);PackageUsage::record($actor->id,'super_like','Super Like',$cost,ProfileSuperLike::class,$record->id,'Super Liked member #'.$recipient);}
   return ['created'=>true,'balance'=>(int)$member->fresh()->remaining_interest,'cost'=>$cost];
  },3);
  if($result['created'])$this->notify($actor,$recipient,'profile_super_liked','super-'.$actor->id.'-'.$recipient);
  return $result;
 }
 private function notify(User $actor,int $recipient,string $key,string $event): void {
  try { $person=User::find($recipient); if($person)\App\Services\NotificationHelper::event($person,$key,['event_id'=>$event,'notify_by'=>$actor->id,'info_id'=>$actor->id,'name'=>trim($actor->first_name.' '.$actor->last_name)]); }
  catch(\Throwable $error){\Illuminate\Support\Facades\Log::warning('Profile appreciation notification failed',['recipient'=>$recipient,'error'=>$error->getMessage()]);}
 }
}
