<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\HomePageContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
class HomePageContentController extends Controller {
 public function edit(){return view('admin.website_settings.home_content',['defaults'=>HomePageContent::defaults(),'content'=>HomePageContent::all()]);}
 public function update(Request $request){
  $defaults=HomePageContent::defaults();$rules=[];
  foreach($defaults as $key=>$value){$rules[$key]=['present','string','max:1200'];}
  $data=$request->validate($rules);
  Setting::updateOrCreate(['type'=>'hq_home_page_content'],['value'=>json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);
  Cache::forget('settings');return back()->with('success','Homepage text saved.');
 }
}
