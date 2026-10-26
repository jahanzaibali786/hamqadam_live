<?php
namespace App\Support;
use App\Models\HamqadamTeamMember;
use Illuminate\Support\Facades\Schema;
class HamqadamTeamContent {
 public static function members(): array {
  if(!Schema::hasTable('hamqadam_team_members')) return \App\Http\Controllers\HamqadamEditorialController::content('team')['practice']['members'] ?? [];
  return HamqadamTeamMember::where('is_active',true)->orderBy('sort_order')->orderBy('id')->get()->map(fn($m)=>$m->only(['name','initials','role','badge','group','bio','focus','location','action_text','action_link','image_id']))->all();
 }
}
