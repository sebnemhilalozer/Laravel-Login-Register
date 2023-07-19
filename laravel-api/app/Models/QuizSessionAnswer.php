<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class QuizSessionAnswer extends Model
{
    protected $fillable = [
        'id', 'session_start_id', 'person_id', 'course_id', 'quiz_id', 'question_id', 'answer_id', 'is_correct', 'score', 'last_change_date', 'session_id', 'ip', 'browser'
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}
