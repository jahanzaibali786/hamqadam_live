<?php
namespace App\Http\Controllers;
use App\Models\Gift;
use App\Models\User;
use App\Services\Api\V1\Gift\GiftService;
use App\Exceptions\ApiException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
class MemberGiftController extends Controller {
 public function index(Request $request,GiftService $service){
  return view('frontend.member.gifts.index',['gifts'=>$service->list(),'sent'=>$service->sent($request->user(),10),'received'=>$service->received($request->user(),10),'balance'=>(int)($request->user()->member?->remaining_interest??0)]);
 }
 public function compose(Request $request,int $member,GiftService $service){
  $recipient=User::where('user_type','member')->where('approved',1)->where('blocked',0)->where('deactivated',0)->findOrFail($member);
  abort_if((int)$recipient->id===(int)$request->user()->id,404);
  return view('frontend.member.gifts.compose',['recipient'=>$recipient,'gifts'=>$service->list(),'balance'=>(int)($request->user()->member?->remaining_interest??0)]);
 }
 public function send(Request $request,GiftService $service){
  $data=$request->validate(['receiver_id'=>['required','integer','exists:users,id'],'gift_id'=>['required','integer','exists:gifts,id'],'confirmed_coins'=>['required','integer','min:1'],'message'=>['nullable','string','max:300']]);
  try {
   $result=$service->send($request->user(),(int)$data['receiver_id'],(int)$data['gift_id'],$data['message']??null,(int)$data['confirmed_coins']);
   return redirect()->route('member.gifts.index')->with('success','Gift sent successfully. Your remaining balance is '.$result['remaining_coins'].' coins.');
  }catch(ApiException $e){
   Log::info('Web gift send rejected',['sender'=>$request->user()->id,'code'=>$e->errorCode()]);
   return back()->withInput()->withErrors(['gift'=>$e->getMessage()]);
  }
 }
 public function transaction(Request $request,int $transaction,GiftService $service){
  try{$gift=$service->transactionDetail($request->user(),$transaction);}catch(ApiException $e){abort($e->statusCode());}
  return view('frontend.member.gifts.transaction',compact('gift'));
 }
}