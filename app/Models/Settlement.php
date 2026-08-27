<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Settlement extends Model
{
    use HasUuids;

    protected $fillable = [
        'reference_id',
        'settlement_date',
        'gross_amount',
        'fee_amount',
        'net_amount',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'settlement_date' => 'datetime',
            'gross_amount' => 'decimal:2',
            'fee_amount' => 'decimal:2',
            'net_amount' => 'decimal:2',
        ];
    }
}
