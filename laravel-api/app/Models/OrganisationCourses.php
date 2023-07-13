<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrganisationCourses extends Model
{
    protected $fillable = ['organisation_id', 'course_id', 'status', 'start_date', 'end_date', 'create_by', 'create_date', 'last_by', 'last_date'];
}
