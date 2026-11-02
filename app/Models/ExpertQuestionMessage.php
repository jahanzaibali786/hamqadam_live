<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ExpertQuestionMessage extends Model {
 protected $fillable=['expert_question_id','sender_id','body'];
 public function sender(){return $this->belongsTo(User::class,'sender_id');}
 public function question(){return $this->belongsTo(ExpertQuestion::class,'expert_question_id');}
}