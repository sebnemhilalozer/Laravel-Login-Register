<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class PurchaseMain extends Model
{
    protected $fillable = [
        'id',
        'status_check',
        'payment_status',
        'conversation_id',
        'price',
        'paid_price',
        'basket_id',
        'product_id',
        'product_title',
        'package_price',
        'kdv_rate',
        'kdv_price',
        'total_price',
        'purchase_discount_id',
        'discount_coupon',
        'discount_rate',   
        'discount_price',
        'discount_email',
        'person_id',
        'purchase_type',
        'purchase_end_date',
        'buyer_name',
        'buyer_surname',
        'buyer_phone',
        'buyer_email',
        'buyer_identity',
        'buyer_address',
        'buyer_ip',
        'buyer_city',
        'buyer_country',
        'billing_name',
        'billing_city',
        'billing_country',
        'billing_address',
        'created_by',
        'session_id',
        'browser',
        'platform'
    ];

    protected $casts = [
        'created_at' => 'datetime'
    ];
}