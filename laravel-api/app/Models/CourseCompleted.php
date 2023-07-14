<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class CourseCompleted extends Model
{
    protected $fillable = [
        'person_id', 'course_id', 'content_id', 'content_type', 'page_time_spent', 'video_duration', 'video_time_spent', 'video_resume_time', 'video_percent', 'completed', 'score'
    ];

    protected $casts = [
        'last_change_date' => 'datetime',
        'created_at' => 'datetime'
    ];
}