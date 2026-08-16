<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HiddenProfileUser extends Model
{
    protected $fillable = ['user_id', 'hidden_from_user_id'];
}
