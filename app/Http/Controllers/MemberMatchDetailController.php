<?php
namespace App\Http\Controllers;
use App\Models\ProfileMatch;
use Illuminate\Http\Request;
class MemberMatchDetailController extends Controller {
 public function show(Request $request,int $member){
  $match=ProfileMatch::query()->visibleToMember($request->user())->with('matchedUser.member')->where('match_id',$member)->firstOrFail();
  return view('frontend.member.match_explanation',compact('match'));
 }
}
