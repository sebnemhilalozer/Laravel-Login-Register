<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class CoursePackageDetail extends Model
{
    protected $fillable = [
        'id', 'title', 'slug', 'status', 'total_course', 'tag', 'cover_img', 'color_1', 'color_2', 'video_path', 'detail', 'package_price', 'kdv_rate', 'kdv_price', 'total_price', 'package_order', 'deleted', 'created_at', 'exam_url'
    ];
}
