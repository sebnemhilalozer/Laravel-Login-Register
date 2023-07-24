<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class CourseContentVideo extends Model
{
    protected $fillable = [
        'id', 'course_content_id', 'cover_img', 'description', 'duration'
    ];
}