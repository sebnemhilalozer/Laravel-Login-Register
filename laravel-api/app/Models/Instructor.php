<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Instructor extends Model
{
    protected $fillable = [
        'id', 'prefix', 'prefix_en', 'name', 'surname', 'slug', 'img', 'job', 'job_en', 'tag', 'instructor_order', 'deleted', 'status'
    ];
}