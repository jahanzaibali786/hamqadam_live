<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarriageProposal extends Model
{
    protected $fillable = ['interest_id', 'sender_id', 'recipient_id', 'template_id', 'language', 'source', 'message', 'status', 'approved_at', 'responded_at', 'reminded_at', 'family_stage'];
    protected $casts = ['approved_at' => 'datetime', 'responded_at' => 'datetime', 'reminded_at' => 'datetime'];

    public function interest() { return $this->belongsTo(ExpressInterest::class, 'interest_id'); }
    public function sender() { return $this->belongsTo(User::class, 'sender_id'); }
    public function recipient() { return $this->belongsTo(User::class, 'recipient_id'); }
    public function template() { return $this->belongsTo(WorkflowMessageTemplate::class, 'template_id'); }
}
