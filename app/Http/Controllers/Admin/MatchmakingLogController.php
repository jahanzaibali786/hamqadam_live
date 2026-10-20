<?php
declare(strict_types=1);
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
class MatchmakingLogController extends Controller {
 public function index(Request $request) {
  $filter=$request->validate(['context'=>['nullable','in:matches,preview'],'status'=>['nullable','integer','between:100,599'],'user_id'=>['nullable','integer','min:1']]);
  $logs=Schema::hasTable('compatibility_model_response_logs')?DB::table('compatibility_model_response_logs')->when($filter['context']??null,fn($q,$v)=>$q->where('context',$v))->when($filter['status']??null,fn($q,$v)=>$q->where('http_status',$v))->when($filter['user_id']??null,fn($q,$v)=>$q->where('user_id',$v))->orderByDesc('id')->paginate(25)->withQueryString():new \Illuminate\Pagination\LengthAwarePaginator([],0,25);
  return view('admin.matchmaking_logs.index',compact('logs'));
 }
 public function show(int $id) {
  abort_unless(Schema::hasTable('compatibility_model_response_logs'),404);
  $log=DB::table('compatibility_model_response_logs')->where('id',$id)->first();
  abort_unless($log,404);
  $json=json_decode((string)$log->response_body,true);
  $body=json_last_error()===JSON_ERROR_NONE?json_encode($json,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE):(string)$log->response_body;
  return view('admin.matchmaking_logs.show',compact('log','body'));
 }
}
