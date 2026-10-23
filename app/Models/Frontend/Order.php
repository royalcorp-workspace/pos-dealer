<?php

declare(strict_types=1);

namespace App\Models\Frontend;

use App\Traits\HasSchemaTable;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use App\Models\Customer;
use App\Models\Frontend\Shipping\Courier;
use App\Models\Payment;

class Order extends Model
{
    use HasSchemaTable;

    protected $table = 'orders';
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
        'id',
        'order_number',
        'order_date',
        'customer_id',
        'courier_id',
        'status',
        'jde_push_status',
        'jde_push_date',
        'payment_method',
        'payment_status',
        'settlement_id',
        'subtotal',
        'tax',
        'discount',
        'total',
        'notes',
        'meta',
        'creator',
        'editor',
        'deleted',
        'voucher_id',
        'voucher_nominal',
        'shipping_cost',
        'shipping_cost_subsidy',
        'shipping_addresses_id',
        'transaction_fee',
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
            'voucher_nominal' => 'decimal:2',
            'shipping_cost' => 'decimal:2',
            'shipping_cost_subsidy' => 'decimal:2',
            'transaction_fee' => 'decimal:2',
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
                $statusLabel = \App\Models\Reff::getShow('StatusOrder', $model->status, (string) $model->status);

                \App\Models\Frontend\Order\OrderLog::create([
                    'order_id' => $model->id,
                    'action' => 'created',
                    'status_from' => null,
                    'status_to' => (string) $model->status,
                    'notes' => 'Pesanan baru dibuat dengan status ' . $statusLabel,
                    'creator' => $model->creator ?: \App\Concerns\HasAuditUser::resolveCurrentUsername(),
                    'editor' => $model->editor ?: \App\Concerns\HasAuditUser::resolveCurrentUsername(),
                ]);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Failed logging order creation: ' . $e->getMessage());
            }
        });

        static::updating(function ($model) {
            if ($model->isDirty('payment_status') && !$model->isDirty('editor')) {
                $model->editor = 'inquiry payment';
            } elseif (!$model->isDirty('editor')) {
                $username = \App\Concerns\HasAuditUser::resolveCurrentUsername();
                $model->editor = $username;
            }
            $model->updated_at = now();

            if ((int) $model->jde_push_status === 2 && empty($model->jde_push_date)) {
                $model->jde_push_date = now()->format('Y-m-d');
            }
        });

        static::updated(function ($model) {
            try {
                $username = \App\Concerns\HasAuditUser::resolveCurrentUsername();

                if ($model->wasChanged('status')) {
                    $fromStatusLabel = \App\Models\Reff::getShow('StatusOrder', $model->getOriginal('status'), (string) $model->getOriginal('status'));
                    $toStatusLabel = \App\Models\Reff::getShow('StatusOrder', $model->status, (string) $model->status);

                    \App\Models\Frontend\Order\OrderLog::create([
                        'order_id' => $model->id,
                        'action' => 'status_changed',
                        'status_from' => (string) $model->getOriginal('status'),
                        'status_to' => (string) $model->status,
                        'notes' => 'Status pesanan diubah dari ' . $fromStatusLabel . ' ke ' . $toStatusLabel,
                        'creator' => $username,
                        'editor' => $username,
                    ]);
                }

                if ($model->wasChanged('payment_status')) {
                    $fromPayLabel = \App\Models\Reff::getShow('PaymentStatus', $model->getOriginal('payment_status'), (string) $model->getOriginal('payment_status'));
                    $toPayLabel = \App\Models\Reff::getShow('PaymentStatus', $model->payment_status, (string) $model->payment_status);

                    \App\Models\Frontend\Order\OrderLog::create([
                        'order_id' => $model->id,
                        'action' => 'payment_status_changed',
                        'status_from' => (string) $model->getOriginal('payment_status'),
                        'status_to' => (string) $model->payment_status,
                        'notes' => 'Status pembayaran diubah dari ' . $fromPayLabel . ' ke ' . $toPayLabel,
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

                // Real-time push ke JDE ketika pesanan lunas & status >= Processing (Status 3)
                $statusBecameProcessing = $model->wasChanged('status') && (int) $model->status >= self::STATUS_PROCESSING;
                $paymentBecamePaid = $model->wasChanged('payment_status') && ((int) $model->payment_status === 2 || (int) $model->payment_status === 1);
                $isPaid = (int) $model->payment_status === 2 || (int) $model->payment_status === 1;
                $isProcessingOrAbove = (int) $model->status >= self::STATUS_PROCESSING;
                $isNotCancelled = !in_array((int) $model->status, [self::STATUS_DRAFT, self::STATUS_CANCELLED, self::STATUS_RETURNED], true);
                $isNotAlreadySuccess = (int) $model->jde_push_status !== 2;

                if (($statusBecameProcessing || $paymentBecamePaid) && $isPaid && $isProcessingOrAbove && $isNotCancelled && $isNotAlreadySuccess) {
                    try {
                        $cmsUrl = rtrim((string) config('services.cms.url', env('CMS_INTERNAL_URL', 'http://172.17.0.1:82')), '/');
                        \Illuminate\Support\Facades\Http::timeout(5)->post("{$cmsUrl}/api/v1/orders/{$model->id}/push-jde");
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning("Trigger JDE push to CMS from pos-dealer failed for order {$model->id}: " . $e->getMessage());
                    }
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
        return $this->belongsTo(Customer::class);
    }

    public function courier(): BelongsTo
    {
        return $this->belongsTo(Courier::class);
    }

    public function delivery(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\App\Models\Frontend\Shipping\Delivery::class, 'order_id');
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Frontend\Promo\Voucher::class);
    }

    public function shippingAddressRelation(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Frontend\Shipping\ShippingAddress::class, 'shipping_addresses_id');
    }

    public function paymentMethodRelation(): BelongsTo
    {
        return $this->belongsTo(\App\Models\PaymentMethod::class, 'payment_method', 'id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(\App\Models\PaymentMethod::class, 'payment_method', 'id')->withoutGlobalScope('active');
    }

    public function getPaymentMethodNameAttribute(): string
    {
        if (!empty($this->meta['payment_method_name'])) {
            return (string) $this->meta['payment_method_name'];
        }

        if ($this->relationLoaded('paymentMethod') && $this->paymentMethod) {
            return (string) $this->paymentMethod->name;
        }

        $raw = (string) ($this->payment_method ?? '');
        if (empty($raw)) {
            return 'N/A';
        }

        if (\Illuminate\Support\Str::isUuid($raw)) {
            $pm = \App\Models\PaymentMethod::withoutGlobalScope('active')->find($raw);
            if ($pm && !empty($pm->name)) {
                return (string) $pm->name;
            }
        }

        if (!empty($this->meta['payment_method_id']) && \Illuminate\Support\Str::isUuid((string)$this->meta['payment_method_id'])) {
            $pm = \App\Models\PaymentMethod::withoutGlobalScope('active')->find($this->meta['payment_method_id']);
            if ($pm && !empty($pm->name)) {
                return (string) $pm->name;
            }
        }

        $pmByCode = \App\Models\PaymentMethod::findByCodeOrBank($raw);
        if ($pmByCode && !empty($pmByCode->name)) {
            return (string) $pmByCode->name;
        }

        $code = strtolower((string)($this->meta['payment_method_code'] ?? $raw));
        if (in_array($code, ['transfer_manual', 'trf', 'manual'], true)) {
            return 'Transfer Bank Manual';
        }
        if ($code === '014' || str_contains($code, 'bca')) {
            return 'BCA Virtual Account';
        }
        if ($code === '008' || str_contains($code, 'mandiri')) {
            return 'Mandiri Virtual Account';
        }
        if ($code === '002' || str_contains($code, 'bri')) {
            return 'BRI Virtual Account';
        }
        if ($code === '009' || str_contains($code, 'bni')) {
            return 'BNI Virtual Account';
        }
        if ($code === '022' || str_contains($code, 'cimb')) {
            return 'CIMB Niaga Virtual Account';
        }
        if ($code === '011' || str_contains($code, 'danamon')) {
            return 'Danamon Virtual Account';
        }
        if ($code === '016' || str_contains($code, 'maybank') || str_contains($code, 'bii')) {
            return 'Maybank Virtual Account';
        }
        if ($code === '013' || str_contains($code, 'permata')) {
            return 'Permata Virtual Account';
        }
        if (str_contains($code, 'gopay')) {
            return 'GoPay';
        }
        if (str_contains($code, 'ovo')) {
            return 'OVO';
        }
        if (str_contains($code, 'qris')) {
            return 'QRIS';
        }
        if (str_contains($code, 'credit') || str_contains($code, 'card')) {
            return 'Kartu Kredit / Debit';
        }

        if (!\Illuminate\Support\Str::isUuid($raw)) {
            return ucwords(str_replace(['_', '-'], ' ', $raw));
        }

        return 'Metode Pembayaran';
    }

    public function getPaymentMethodCodeAttribute(): string
    {
        if (!empty($this->meta['payment_method_code'])) {
            return (string) $this->meta['payment_method_code'];
        }

        if ($this->relationLoaded('paymentMethod') && $this->paymentMethod) {
            return (string) $this->paymentMethod->code;
        }

        $raw = (string) ($this->payment_method ?? '');
        if (\Illuminate\Support\Str::isUuid($raw)) {
            $pm = \App\Models\PaymentMethod::withoutGlobalScope('active')->find($raw);
            if ($pm && !empty($pm->code)) {
                return (string) $pm->code;
            }
        }

        return $raw;
    }

    public function isManualTransfer(): bool
    {
        $code = strtolower($this->payment_method_code);
        $raw = strtolower((string)($this->payment_method ?? ''));
        return str_contains($code, 'transfer_manual')
            || str_contains($code, 'manual')
            || str_contains($raw, 'transfer_manual')
            || str_contains($raw, 'manual')
            || strtolower($this->payment_method_name) === 'transfer bank manual'
            || strtolower($this->payment_method_name) === 'bank transfers';
    }

    public function items(): HasMany
    {
        return $this->hasMany(\App\Models\Frontend\Order\OrderItem::class, 'order_id', 'id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
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
            self::STATUS_PROCESSING => 'Pesanan Diproses',
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
