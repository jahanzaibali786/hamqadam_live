<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\PublicEditorialPages;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
class PublicEditorialSettingsController extends Controller {
 public function edit(string $page) { $data=PublicEditorialPages::copy($page);return view('admin.website_settings.public_editorial',compact('page','data')); }
 public function update(Request $request,string $page) {
  $defaults=PublicEditorialPages::defaults($page);
  $rules=[];foreach($defaults as $key=>$value){$rules[$key]=is_array($value)?['required','array','max:20']:['nullable','string','max:1500'];}
  $values=$request->validate($rules);
  foreach($defaults as $key=>$value){if(is_array($value)){$rows=$values[$key]??[];foreach($rows as $row){if(!is_array($row)||array_diff(array_keys($row),array_keys($value[0]))){return back()->withErrors(['content'=>'Invalid repeatable section data.']);}foreach($row as $cell){if(!is_string($cell)||mb_strlen($cell)>1500) return back()->withErrors(['content'=>'Invalid text in repeatable section.']);}}}}
  Setting::updateOrCreate(['type'=>'hq_public_editorial_'.$page],['value'=>json_encode(array_replace($defaults,$values),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);Cache::forget('settings');
  return back()->with('success','Page content saved.');
 }
}