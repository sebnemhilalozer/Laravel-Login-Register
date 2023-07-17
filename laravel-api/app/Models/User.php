<?php

namespace App\Models;

use App\Notifications\VerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

// implements MustVerifyEmail
class User extends Authenticatable
{
    use Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'organisation_id','person_role_id','prefix','first_name', 'last_name', 'email', 'password','password_change','expiration_date','deleted', 
        'kvkk_status', 'kvkk_date', 'kvkk_status', 'subs_date'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_confirm' => 'datetime',
    ];

    // public function sendEmailVerificationNotification()
    // {
    //     $this->notify(new VerifyEmail());
    // }
}
