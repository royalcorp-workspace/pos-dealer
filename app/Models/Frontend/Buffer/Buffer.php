<?php

declare(strict_types=1);

namespace App\Models\Frontend\Buffer;

use App\Models\Customer;
use App\Models\Frontend\Customer\Customer as FrontendCustomer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Buffer extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'customer_id',
        'session_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'subtotal',
        'tax',
        'discount',
        'total',
        'meta',
        'creator',
        'editor',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'tax' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
            'meta' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(FrontendCustomer::class, 'customer_id', 'id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BufferItem::class, 'buffer_id', 'id');
    }
}
