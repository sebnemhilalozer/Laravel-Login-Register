<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    protected $fillable = [
        'id', 'name', 'surname', 'email', 'phone', 'organisation', 'number_of_employees', 'message', 'email_notification', 'ip'
    ];

    protected $casts = [
        'creation_date' => 'datetime',
    ];
}
