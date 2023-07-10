<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Auth extends Model
{

    protected $table = 'person';

    // protected $primaryKey = 'id';

    protected $fillable = ['person_role_id', 'organisation_id', 'first_name', 'last_name', 'email', 'password', 'kvkk_status', 'kvkk_date', 'subs_status', 'subs_date', 'email_confirm'];
}
