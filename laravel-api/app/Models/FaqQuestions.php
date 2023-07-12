<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FaqQuestions extends Model
{
    protected $fillable = [
        'id', 'faq_category_id', 'title_tr', 'title_en', 'answers_tr', 'answers_en', 'check_faq_list', 'faq_order', 'view_count', 'status', 'deleted',
        'created_at', 'updated_at', 'deleted_at'
    ];

    protected $casts = [
        'expiration_date' => 'datetime',
        'active_date' => 'datetime'
    ];
}
