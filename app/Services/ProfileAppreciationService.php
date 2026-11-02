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
  return DB::transaction(function()use($actor,$recipient){
   User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
   $query=ProfileLike::where('sender_id',$actor->id)->where('recipient_id',$recipient);
   if($query->exists()){$query->delete();return false;}
   ProfileLike::create(['sender_id'=>$actor->id,'recipient_id'=>$recipient]);return true;
  },3);
 }
 public function superLike(User $actor,int $recipient): array {
  if(!$this->eligible($actor,$recipient))throw ValidationException::withMessages(['member'=>'This profile is unavailable.']);
  return DB::transaction(function()use($actor,$recipient){
   $member=Member::where('user_id',$actor->id)->lockForUpdate()->firstOrFail();
   $previous=ProfileSuperLike::where('sender_id',$actor->id)->where('recipient_id',$recipient)->first();
   if($previous)return ['created'=>false,'balance'=>(int)$member->remaining_interest,'cost'=>(int)$previous->coins_charged];
   $cost=max(0,(int)get_setting('feature_coin_cost_super_like',10));
   if((int)$member->remaining_interest<$cost)throw ValidationException::withMessages(['coins'=>'Insufficient coins. Please add coins to continue.']);
   $record=ProfileSuperLike::create(['sender_id'=>$actor->id,'recipient_id'=>$recipient,'coins_charged'=>$cost]);
   if($cost){$member->decrement('remaining_interest',$cost);PackageUsage::record($actor->id,'super_like','Super Like',$cost,ProfileSuperLike::class,$record->id,'Super Liked member #'.$recipient);}
   return ['created'=>true,'balance'=>(int)$member->fresh()->remaining_interest,'cost'=>$cost];
  },3);
 }
}
