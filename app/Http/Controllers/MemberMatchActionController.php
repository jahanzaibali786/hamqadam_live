<?php
namespace App\Http\Controllers;
use App\Models\ProfileMatch;
use App\Models\ExpressInterest;
use Illuminate\Http\Request;
class MemberMatchActionController extends Controller {
 public function propose(Request $request,int $member){
  $viewer=$request->user();
  ProfileMatch::visibleToMember($viewer)->where('match_id',$member)->firstOrFail();
  $existing=ExpressInterest::query()->where(fn($q)=>$q->where('user_id',$member)->where('interested_by',$viewer->id))
   ->orWhere(fn($q)=>$q->where('user_id',$viewer->id)->where('interested_by',$member))->exists();
  if($existing)return back()->withErrors(['proposal'=>'A proposal already exists between these members.']);
  $request->merge(['id'=>$member]);
  $sent=app(ExpressInterestController::class)->store($request);
  return $sent?back()->with('success','Proposal sent successfully.'):back()->withErrors(['proposal'=>'Could not send proposal. Check your available coins and package.']);
 }
}
