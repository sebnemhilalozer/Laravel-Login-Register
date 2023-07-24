<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class CoursePackage extends Model
{
    protected $fillable = [
        'id', 'package_id', 'course_id', 'status', 'deleted', 'created_at'
    ];
}