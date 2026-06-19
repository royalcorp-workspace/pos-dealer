<?php

declare(strict_types=1);

namespace App\Models\Frontend;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Customer;
use App\Models\Payment;

class Order extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    public const STATUS_DRAFT = 0;
    public const STATUS_PENDING_APPROVAL = 1;
    public const STATUS_CONFIRMED = 2;
    public const STATUS_PROCESSING = 3;
    public const STATUS_SHIPPED = 4;
    public const STATUS_DELIVERED = 5;
    public const STATUS_CANCELLED = 6;
    public const STATUS_RETURNED = 7;

    protected $fillable = [
        'customer_id',
        'status',
        'payment_method',
        'payment_status',
        'subtotal',
        'tax',
        'discount',
        'total',
        'notes',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'integer',
            'payment_status' => 'integer',
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
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_PENDING_APPROVAL => 'Pending Approval',
            self::STATUS_CONFIRMED => 'Confirmed',
            self::STATUS_PROCESSING => 'Processing',
            self::STATUS_SHIPPED => 'Shipped',
            self::STATUS_DELIVERED => 'Delivered',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_RETURNED => 'Returned',
        ];
    }

    public function statusLabel(): string
    {
        return self::statusLabels()[$this->status] ?? 'Unknown';
    }
}
