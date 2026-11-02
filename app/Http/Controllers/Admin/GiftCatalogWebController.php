<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Gift;
use App\Models\GiftTransaction;
use Illuminate\Http\Request;
class GiftCatalogWebController extends Controller {
 private function authorizeAdmin(Request $request):void{abort_unless($request->user()?->user_type==='admin'||($request->user()?->user_type==='staff'&&$request->user()?->admin_identifier==='subadmin'),403);}
 public function index(Request $request){$this->authorizeAdmin($request);return view('admin.gifts.index',['gifts'=>Gift::orderBy('sort_order')->get(),'transactions'=>GiftTransaction::with(['gift','sender','receiver'])->latest()->paginate(15)]);}
 public function save(Request $request){$this->authorizeAdmin($request);$data=$request->validate(['gift_id'=>['nullable','integer','exists:gifts,id'],'name'=>['required','string','max:60'],'slug'=>['required','string','max:60'],'thumbnail'=>['required','string','max:255'],'animated_asset'=>['required','string','max:255'],'coins'=>['required','integer','min:1'],'category'=>['nullable','string','max:40'],'sort_order'=>['required','integer','min:0'],'is_active'=>['nullable','boolean']]);$id=$data['gift_id']??null;unset($data['gift_id']);$data['is_active']=$request->boolean('is_active');$rule=Gift::where('slug',$data['slug'])->when($id,fn($q)=>$q->where('id','!=',$id))->exists();if($rule)return back()->withInput()->withErrors(['slug'=>'Slug already in use.']);if($id){Gift::findOrFail($id)->update($data);}else{Gift::create($data);}return back()->with('success','Gift catalog saved.');}
 public function deactivate(Request $request,Gift $gift){$this->authorizeAdmin($request);$gift->update(['is_active'=>false]);return back()->with('success','Gift deactivated without deleting history.');}
}