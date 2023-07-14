<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class CourseCategory extends Model
{
    protected $fillable = [
        'id, parent_id', 'title', 'slug', 'status', 'category_order', 'deleted' ];

}
