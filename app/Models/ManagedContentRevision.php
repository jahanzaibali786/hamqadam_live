<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManagedContentRevision extends Model
{
    protected $fillable = ['page_key','status','content_json','editor_id','published_at'];

    protected $casts = ['published_at' => 'datetime'];

    public function editor()
    {
        return $this->belongsTo(User::class, 'editor_id');
    }

    public function scopeForPage($query, string $page)
    {
        return $query->where('page_key', $page);
    }
}
