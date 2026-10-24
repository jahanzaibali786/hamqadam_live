<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HamqadamTeamMember extends Model
{
    protected $fillable = [
        'name', 'initials', 'role', 'badge', 'group', 'bio', 'focus',
        'location', 'action_text', 'action_link', 'image_id', 'sort_order', 'is_active',
    ];
    protected $casts = ['is_active' => 'boolean', 'sort_order' => 'integer', 'image_id' => 'integer'];
}
