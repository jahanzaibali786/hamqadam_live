<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowMessageTemplate extends Model
{
    protected $fillable = ['kind', 'language', 'title', 'opening', 'body', 'closing', 'is_active', 'display_order', 'created_by', 'updated_by'];

    protected $casts = ['is_active' => 'boolean'];

    public function scopeActive($query, string $kind, string $language = 'en')
    {
        return $query->where('kind', $kind)->where('language', $language)->where('is_active', true)->orderBy('display_order')->orderBy('id');
    }

    public function getCompleteMessageAttribute(): string
    {
        return trim(implode("\n\n", array_filter([$this->opening, $this->body, $this->closing], fn ($value) => filled($value))));
    }
}
