<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class courseContentQuizQuestionAlternative extends Model
{
    protected $fillable = [
        'id','cc_question_id','title','correct','deleted'
    ];

}