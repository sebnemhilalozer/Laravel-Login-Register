<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class QuizSessionStart extends Model
{
    protected $fillable = [
        'person_id', 'course_id', 'quiz_id', 'quiz_status', 'time_spent', 'last_time_spent_date', 'session_start_date', 'session_end_date', 'session_id', 'ip', 'browser',
        'deleted', 'completed', 'score'
    ];

}
