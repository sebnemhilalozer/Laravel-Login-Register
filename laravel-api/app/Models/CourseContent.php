<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class CourseContent extends Model
{
    protected $fillable = [
        'id', 'parent_id', 'course_type_id', 'course_id', 'title', 'visibility', 'content_order', 'deleted', 'status'

    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];
}
