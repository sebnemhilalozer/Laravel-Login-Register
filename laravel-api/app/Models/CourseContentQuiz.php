<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class CourseContentQuiz extends Model
{
    protected $fillable = [
        'id','package_id','course_content_id','title','duration','question_random','attempt','passing_score','status','start_dt','end_dt','deleted'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];
}