<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MatchSuggestionFeedback extends Model
{
    protected $table = 'match_suggestion_feedback';

    protected $fillable = ['user_id', 'suggested_user_id', 'feedback', 'source', 'note'];
}
