<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PersonInfo extends Model
{
    protected $fillable = [
        'id', 'person_id', 'cv', 'linkedin', 'instagram', 'twitter', 'youtube', 'information', 'phone', 'tc_number', 'city', 'address'
    ];
}
