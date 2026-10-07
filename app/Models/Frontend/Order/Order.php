<?php

declare(strict_types=1);

namespace App\Models\Frontend\Order;

use App\Models\Frontend\Customer\Customer;
use App\Models\Frontend\Order\OrderItem;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasUuids;

    protected $table = 'orders';

    public const STATUS_DRAFT = 0;
    public const STATUS_PENDING_APPROVAL = 1;
    public const STATUS_CONFIRMED = 2;
    public const STATUS_PROCESSING = 3;
    public const STATUS_SHIPPED = 4;
    public const STATUS_DELIVERED = 5;
    public const STATUS_CANCELLED = 6;
    public const STATUS_RETURNED = 7;

    protected $fillable = [
        'order_number',
        'order_date',
        'customer_id',
        'courier_id',
        'status',
        'jde_push_status',
        'jde_push_date',
        'payment_method',
        'payment_status',
        'subtotal',
        'tax',
        'discount',
        'total',
        'notes',
        'meta',
        'creator',
        'editor',
        'deleted',
    ];

    protected function casts(): array
    {
        return [
            'order_date' => 'date:Y-m-d',
            'jde_push_date' => 'date:Y-m-d',
            'jde_push_status' => 'integer',
            'status' => 'integer',
            'payment_status' => 'integer',
            'subtotal' => 'decimal:2',
            'tax' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
            'meta' => 'array',
            'deleted' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public static function generateOrderNumber($date = null): string
    {
        $dateObj = $date ? \Illuminate\Support\Carbon::parse($date) : now();
        $prefix = 'ORD.' . $dateObj->format('Ymd') . '.';

        $lastOrderNumber = static::withoutGlobalScopes()
            ->where('order_number', 'ilike', $prefix . '%')
            ->orderByRaw("LENGTH(SPLIT_PART(order_number, '.', 3)) DESC, SPLIT_PART(order_number, '.', 3) DESC")
            ->value('order_number');

        if ($lastOrderNumber) {
            $parts = explode('.', $lastOrderNumber);
            $lastSeq = (int) end($parts);
            $nextSeq = $lastSeq + 1;
        } else {
            $nextSeq = 1;
        }

        return $prefix . str_pad((string) $nextSeq, 4, '0', STR_PAD_LEFT);
    }

    protected static function boot(): void
    {
        parent::boot();

        static::addGlobalScope('active', function ($query) {
            $query->where('deleted', false);
        });

        static::creating(function ($model) {
            if (!$model->order_number) {
                $model->order_number = static::generateOrderNumber($model->order_date ?? now());
            }
            if ($model->status === null || $model->status === self::STATUS_DRAFT || $model->status === 0) {
                $model->status = self::STATUS_PENDING_APPROVAL;
            }
            if (empty($model->order_date)) {
                $model->order_date = now()->format('Y-m-d');
            }
            if ($model->jde_push_status === null) {
                $model->jde_push_status = 0;
            }
            $username = \App\Concerns\HasAuditUser::resolveCurrentUsername();
            if (empty($model->creator)) {
                $model->creator = $username;
            }
            if (empty($model->editor)) {
                $model->editor = $username;
            }
            if (empty($model->created_at)) {
                $model->created_at = now();
            }
            if (empty($model->updated_at)) {
                $model->updated_at = now();
            }
        });

        static::created(function ($model) {
            try {
                \App\Models\Frontend\Order\OrderLog::create([
                    'order_id' => $model->id,
                    'action' => 'created',
                    'status_from' => null,
                    'status_to' => (string) $model->status,
                    'notes' => 'Pesanan baru dibuat dengan status ' . $model->status,
                    'creator' => $model->creator ?: \App\Concerns\HasAuditUser::resolveCurrentUsername(),
                    'editor' => $model->editor ?: \App\Concerns\HasAuditUser::resolveCurrentUsername(),
                ]);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Failed logging order creation: ' . $e->getMessage());
            }
        });

        static::updating(function ($model) {
            $username = \App\Concerns\HasAuditUser::resolveCurrentUsername();
            $model->editor = $username;
            $model->updated_at = now();

            if ((int) $model->jde_push_status === 2 && empty($model->jde_push_date)) {
                $model->jde_push_date = now()->format('Y-m-d');
            }
        });

        static::updated(function ($model) {
            try {
                $username = \App\Concerns\HasAuditUser::resolveCurrentUsername();

                if ($model->wasChanged('status')) {
                    \App\Models\Frontend\Order\OrderLog::create([
                        'order_id' => $model->id,
                        'action' => 'status_changed',
                        'status_from' => (string) $model->getOriginal('status'),
                        'status_to' => (string) $model->status,
                        'notes' => 'Status pesanan diubah dari ' . $model->getOriginal('status') . ' ke ' . $model->status,
                        'creator' => $username,
                        'editor' => $username,
                    ]);
                }

                if ($model->wasChanged('payment_status')) {
                    \App\Models\Frontend\Order\OrderLog::create([
                        'order_id' => $model->id,
                        'action' => 'payment_status_changed',
                        'status_from' => (string) $model->getOriginal('payment_status'),
                        'status_to' => (string) $model->payment_status,
                        'notes' => 'Status pembayaran diubah dari ' . $model->getOriginal('payment_status') . ' ke ' . $model->payment_status,
                        'creator' => $username,
                        'editor' => $username,
                    ]);
                }

                if ($model->wasChanged('jde_push_status')) {
                    $statusLabel = \App\Models\Reff::getShow('JdePushStatus', $model->jde_push_status, (string) $model->jde_push_status);
                    \App\Models\Frontend\Order\OrderLog::create([
                        'order_id' => $model->id,
                        'action' => 'jde_push_status_changed',
                        'status_from' => (string) $model->getOriginal('jde_push_status'),
                        'status_to' => (string) $model->jde_push_status,
                        'notes' => 'Status push JDE diubah ke ' . $statusLabel . ($model->jde_push_date ? ' (Tanggal: ' . $model->jde_push_date . ')' : ''),
                        'creator' => $username,
                        'editor' => $username,
                    ]);
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Failed logging order update: ' . $e->getMessage());
            }
        });
    }

    public function logs(): HasMany
    {
        return $this->hasMany(\App\Models\Frontend\Order\OrderLog::class, 'order_id', 'id')->orderBy('created_at', 'desc');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }

    public function courier(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Frontend\Shipping\Courier::class, 'courier_id', 'id');
    }

    public function delivery(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\App\Models\Frontend\Shipping\Delivery::class, 'order_id', 'id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id', 'id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'order_id', 'id');
    }

    public static function statusLabels(): array
    {
        $dbLabels = \App\Models\Reff::getOptions('StatusOrder');
        if (!empty($dbLabels)) {
            return $dbLabels;
        }

        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_PENDING_APPROVAL => 'Ordered',
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
        return \App\Models\Reff::getShow('StatusOrder', $this->status, self::statusLabels()[$this->status] ?? 'Unknown');
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'bg-gray-100 text-gray-600',
            self::STATUS_PENDING_APPROVAL => 'bg-yellow-100 text-yellow-700',
            self::STATUS_CONFIRMED => 'bg-blue-100 text-blue-700',
            self::STATUS_PROCESSING => 'bg-indigo-100 text-indigo-700',
            self::STATUS_SHIPPED => 'bg-purple-100 text-purple-700',
            self::STATUS_DELIVERED => 'bg-green-100 text-green-700',
            self::STATUS_CANCELLED => 'bg-red-100 text-red-700',
            self::STATUS_RETURNED => 'bg-orange-100 text-orange-700',
            default => 'bg-gray-100 text-gray-600',
        };
    }
}
