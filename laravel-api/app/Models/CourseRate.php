<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class CourseRate extends Model
{
    protected $fillable = [
        'id', 'person_id', 'course_id', 'like_status', 'instructor_id', 'course_rate', 'course_comments', 'instructor_rate', 'instructor_comments', 'rate_scale'

    ];

    protected $casts = [
        'last_change_date' => 'datetime',
        'create_date' => 'datetime'
    ];
}
