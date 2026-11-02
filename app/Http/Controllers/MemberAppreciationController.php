<?php
namespace App\Http\Controllers;
use App\Models\{ProfileLike,ProfileSuperLike,User};
use App\Services\ProfileAppreciationService;
use Illuminate\Http\Request;
class MemberAppreciationController extends Controller {
 public function like(Request $request,int $member,ProfileAppreciationService $service){
  return response()->json(['liked'=>$service->toggleLike($request->user(),$member)]);
 }
 public function superLike(Request $request,int $member,ProfileAppreciationService $service){
  $data=$request->validate(['quoted_cost'=>'required|integer|min:0']);
  $result=$service->superLike($request->user(),$member,(int)$data['quoted_cost']);
  return response()->json(['super_liked'=>true]+$result);
 }
 public function history(Request $request){
  return view('frontend.member.appreciations',[
   'outgoing'=>ProfileSuperLike::with('recipient')->where('sender_id',$request->user()->id)->latest()->paginate(15),
   'likesReceived'=>ProfileLike::with('sender')->where('recipient_id',$request->user()->id)->latest()->limit(30)->get(),
   'incoming'=>ProfileSuperLike::with('sender')->where('recipient_id',$request->user()->id)->latest()->limit(30)->get(),
  ]);
 }
}
