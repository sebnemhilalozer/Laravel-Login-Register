<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class CourseEnrollDropout extends Model
{
    protected $fillable = [
        'id', 'person_id', 'course_id', 'last_access_date', 'enroll_date', 'course_completed', 'course_completed_date',
        'dropout_date'
    ];
}
