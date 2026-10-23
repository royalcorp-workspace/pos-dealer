<?php

namespace App\Models;

use App\Traits\HasSchemaTable;

use Illuminate\Database\Eloquent\Model;

class VoidOrder extends Model
{
    use HasSchemaTable;

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
