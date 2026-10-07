<?php

declare(strict_types=1);

namespace App\Models\Frontend\Order;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderLog extends Model
{
    use HasUuids;

    protected $table = 'order_logs';

    protected $fillable = [
        'id',
        'order_id',
        'action',
        'status_from',
        'status_to',
        'notes',
        'creator',
        'editor',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }
}
