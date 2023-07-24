<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class CourseContentQuizQuestion extends Model
{
    protected $fillable = [
        'id', 'cc_quiz_id', 'title', 'score', 'alternative_random', 'deleted'
    ];
}
