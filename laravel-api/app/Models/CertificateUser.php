<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CertificateUser extends Model
{
    protected $fillable = [
        'id', 'certificate_template_id', 'certificate_token', 'course_enroll_id', 'course_id', 'course_title', 'person_id', 'person_name', 'certificate_path'
    ];

    protected $casts = [
        'certificate_start_date' => 'datetime',
        'certificate_end_date' => 'datetime',
        'created_at' => 'datetime',
        'deleted_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
