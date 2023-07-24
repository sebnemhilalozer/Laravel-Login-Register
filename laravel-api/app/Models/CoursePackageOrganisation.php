<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class CoursePackageOrganisation extends Model
{
    protected $fillable = [
        'id', 'package_id', 'organisation_id', 'status', 'created_by', 'updated_by'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
