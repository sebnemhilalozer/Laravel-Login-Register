<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoursePackagePerson extends Model
{
    protected $fillable = ['purchase_type', 'package_id', 'person_id', 'conversation_id', 'status', 'start_date', 'end_date', 'created_by', 'created_at', 'fullAccess_enroll_id', 'purchase_main_id'];
}
