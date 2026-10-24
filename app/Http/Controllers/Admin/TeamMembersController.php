<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\HamqadamTeamMember;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
class TeamMembersController extends Controller {
 public function index(){return view('admin.website_settings.team_members', ['members'=>HamqadamTeamMember::orderBy('sort_order')->orderBy('id')->get()]);}
 public function store(Request $r){HamqadamTeamMember::create($this->validated($r));return back()->with('success','Team member added.');}
 public function update(Request $r,HamqadamTeamMember $member){$member->update($this->validated($r));return back()->with('success','Team member updated.');}
 public function destroy(HamqadamTeamMember $member){$member->delete();return back()->with('success','Team member removed.');}
 private function validated(Request $r):array{
  $v=$r->validate(['name'=>'required|string|max:150','initials'=>'nullable|string|max:8','role'=>'required|string|max:180','badge'=>'nullable|string|max:100','group'=>['required',Rule::in(['All','Family','Technology','Trust'])],
 'bio'=>'nullable|string|max:1500','focus'=>'nullable|string|max:1200','location'=>'nullable|string|max:120',
 'action_text'=>'nullable|string|max:80','action_link'=>'nullable|string|max:512','image_id'=>'nullable|integer|exists:uploads,id','sort_order'=>'required|integer|min:0|max:99999','is_active'=>'nullable|boolean']);
  $v['is_active']=$r->boolean('is_active');
  if(!empty($v['action_link']) && !str_starts_with($v['action_link'],'/') && !preg_match('/^https:\/\//i',$v['action_link'])){throw \Illuminate\Validation\ValidationException::withMessages(['action_link'=>'Use an internal path or HTTPS link.']);}
  return $v;
 }
}
