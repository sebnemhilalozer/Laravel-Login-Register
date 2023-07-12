<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    protected $fillable = [
        'id', 'title', 'slug', 'img_path', 'web_link', 'support', 'color_code', 'deleted', 'status'
    ];

    protected $casts = [
        'expiration_date' => 'datetime',
        'active_date' => 'datetime'
    ];
}
