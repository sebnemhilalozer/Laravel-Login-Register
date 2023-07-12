<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Course extends Model
{
    protected $fillable = [
        'id', 'instructor_id', 'category_id', 'title', 'title_en', 'slug', 'audience', 'audience_en', 'details', 'details_en',
        'meta_description', 'meta_description_en', 'class_length', 'img_path', 'home_img_path', 'intro_path', 'top_list', 'course_order',
        'course_price', 'buy_full_access', 'deleted', 'status'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'published_at' => 'datetime'
    ];
}