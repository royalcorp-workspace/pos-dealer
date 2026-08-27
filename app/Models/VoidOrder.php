<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VoidOrder extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'order_number', 'customer_id', 'order_data', 'order_items_data', 'void_reason', 'voided_at'
    ];

    protected $casts = [
        'order_data' => 'array',
        'order_items_data' => 'array',
        'voided_at' => 'datetime',
    ];
}
