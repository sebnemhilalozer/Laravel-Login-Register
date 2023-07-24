<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationAllow extends Model
{
    protected $fillable = [
        'id', 'notification_id','person_id', 'status_allow'
    ];
}