<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class PersonCookie extends Model
{
    protected $fillable = [
        'id', 'person_id', 'cookie_code', 'ip', 'browser_id'
    ];

    protected $casts = [
        'expiry_date' => 'datetime',
        'last_access_data' => 'datetime',
        'created_at' => 'datetime',
    ];
}