<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class FullAccessEnroll extends Model
{
    protected $fillable = [
        'id', 'person_id', 'status', 'purchase_main_id', 'enrolled_by'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'end_date' => 'datetime'
    ];
}
