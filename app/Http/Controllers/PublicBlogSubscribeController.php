<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
class PublicBlogSubscribeController extends Controller {
 public function subscribe(Request $request) {
  $d=$request->validate(['email'=>['required','email','max:254'],'consent'=>['accepted']]);
  $email=mb_strtolower(trim($d['email']));$now=now();
  $exists=DB::table('hamqadam_blog_subscribers')->where('email',$email)->first();
  if($exists){DB::table('hamqadam_blog_subscribers')->where('id',$exists->id)->update(['consented_at'=>$now,'unsubscribed_at'=>null,'updated_at'=>$now]);}
  else{DB::table('hamqadam_blog_subscribers')->insert(['email'=>$email,'unsubscribe_token'=>Str::random(64),'consented_at'=>$now,'created_at'=>$now,'updated_at'=>$now]);}
  return back()->with('subscribe_status','Thank you for subscribing. You can unsubscribe from any future digest.');
 }
 public function unsubscribe(string $token){
  abort_unless(preg_match('/^[a-zA-Z0-9]{64}$/',$token),404);
  $changed=DB::table('hamqadam_blog_subscribers')->where('unsubscribe_token',$token)->update(['unsubscribed_at'=>now(),'updated_at'=>now()]);
  abort_unless($changed,404);
  return response('Your email has been unsubscribed from the Hamqadam editorial digest.',200)->header('Content-Type','text/plain; charset=UTF-8');
 }
}