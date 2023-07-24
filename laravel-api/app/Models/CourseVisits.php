<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class CourseVisits extends Model
{
    protected $fillable = ['id', 'person_id', 'course_id', 'created_at', 'session_id', 'ip', 'browser'];

    protected $casts = [
        'create_at' => 'datetime'
    ];
}
