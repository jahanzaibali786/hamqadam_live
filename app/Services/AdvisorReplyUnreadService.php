<?php
namespace App\Services;
use App\Models\ExpertQuestionMessage;
use Illuminate\Support\Facades\DB;
class AdvisorReplyUnreadService {
 public function unread(int $userId):int {
  return ExpertQuestionMessage::query()->join('expert_questions as q','q.id','=','expert_question_messages.expert_question_id')->where('q.user_id',$userId)->where('expert_question_messages.sender_id','!=',$userId)->whereNull('expert_question_messages.member_read_at')->count();
 }
 public function markRead(int $userId):void {
  DB::table('expert_question_messages')->whereIn('expert_question_id',DB::table('expert_questions')->select('id')->where('user_id',$userId))->where('sender_id','!=',$userId)->whereNull('member_read_at')->update(['member_read_at'=>now()]);
 }
}
