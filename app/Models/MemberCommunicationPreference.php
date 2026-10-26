<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MemberCommunicationPreference extends Model
{
    protected $fillable = ['user_id', 'allow_audio_calls', 'allow_video_calls'];
    protected $casts = ['allow_audio_calls' => 'boolean', 'allow_video_calls' => 'boolean'];
    public function user() { return $this->belongsTo(User::class); }
}
