<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
class PublicNavigationController extends Controller {
 public function update(Request $request,string $area) {
  abort_unless(in_array($area,['header','footer'],true),404);
  $data=$request->validate(['labels'=>'required|array|max:15','labels.*'=>'nullable|string|max:60','links'=>'required|array|max:15','links.*'=>'nullable|string|max:512']);
  $items=[];
  foreach($data['labels'] as $i=>$label) {
   $label=trim((string)$label);$link=trim((string)($data['links'][$i]??''));
   if($label===''&&$link==='')continue;
   if($label===''||$link===''||(!preg_match('#^/(?!/)#',$link)&&!preg_match('#^https://#i',$link)))throw ValidationException::withMessages(['links'=>'Each item requires a label and an internal /path or HTTPS URL.']);
   $items[]=['label'=>$label,'url'=>$link];
  }
  Setting::updateOrCreate(['type'=>'hq_public_nav_'.$area],['value'=>json_encode($items)]);
  Cache::forget('settings');
  return back()->with('success','Public website '.ucfirst($area).' links updated.');
 }
}
