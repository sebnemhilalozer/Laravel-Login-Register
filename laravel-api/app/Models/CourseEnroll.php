<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class courseEnroll extends Model
{
    protected $fillable = ['person_id', 'course_id', 'last_access_date', 'created_at', 'course_completed', 'course_completed_date',
     'dropout_status', 'dropout_date', 'enroll_status', 'enroll_date', 'enroll_by', 'cPackage_enroll_id', 'fullAccess_enroll_id', 'purchase_main_id'];
}