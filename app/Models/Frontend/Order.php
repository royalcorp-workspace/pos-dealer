<?php

declare(strict_types=1);

namespace App\Models\Frontend;

use App\Traits\HasSchemaTable;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use App\Models\Customer;
use App\Models\Frontend\Shipping\Courier;
use App\Models\Payment;
use App\Models\CreditMemo;

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

    public const PAYMENT_UNPAID = 0;
    public const PAYMENT_PAID = 1;
    public const PAYMENT_FAILED = 2;
    public const PAYMENT_REFUNDED = 3;
    public const PAYMENT_PARTIAL = 4;

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
        'dpp_amount',
        'ppn_rate',
        'ppn_amount',
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
            'dpp_amount' => 'decimal:2',
            'ppn_rate' => 'decimal:2',
            'ppn_amount' => 'decimal:2',
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
        $year2Digit = $dateObj->format('y');
        $prefix = 'IMG' . $year2Digit;

        if (\Illuminate\Support\Facades\DB::getDriverName() === 'pgsql') {
            $maxSeq = static::withoutGlobalScopes()
                ->where('order_number', 'like', $prefix . '%')
                ->selectRaw("COALESCE(MAX(CAST(SUBSTRING(order_number FROM '^' || ? || '([0-9]+)$') AS BIGINT)), 0) as max_seq", [$prefix])
                ->value('max_seq');
        } else {
            $lastOrder = static::withoutGlobalScopes()
                ->where('order_number', 'like', $prefix . '%')
                ->orderByRaw('LENGTH(order_number) DESC, order_number DESC')
                ->value('order_number');
            $maxSeq = 0;
            if ($lastOrder && preg_match('/^' . preg_quote($prefix, '/') . '(\d+)$/', $lastOrder, $m)) {
                $maxSeq = (int) $m[1];
            }
        }

        $nextSeq = ((int) $maxSeq) + 1;
        $orderNumber = $prefix . str_pad((string) $nextSeq, 4, '0', STR_PAD_LEFT);

        while (static::withoutGlobalScopes()->where('order_number', $orderNumber)->exists()) {
            $nextSeq++;
            $orderNumber = $prefix . str_pad((string) $nextSeq, 4, '0', STR_PAD_LEFT);
        }

        return $orderNumber;
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
            if ($model->payment_status === null) {
                $model->payment_status = self::PAYMENT_UNPAID;
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

            // Ketika status pesanan menjadi Processing (3) atau lebih, otomatis ubah payment_status menjadi Paid (1)
            if ((int) $model->status >= self::STATUS_PROCESSING && (int) $model->status !== self::STATUS_CANCELLED) {
                if ((int) $model->payment_status === self::PAYMENT_UNPAID) {
                    $model->payment_status = self::PAYMENT_PAID;
                }
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

        static::saved(function ($model) {
            try {
                // 1. Saat customer bayar / order masuk status Processing (3) atau lebih:
                $isPaidOrProcessing = ((int)$model->payment_status === self::PAYMENT_PAID || (int)$model->status >= self::STATUS_PROCESSING)
                    && (int)$model->status !== self::STATUS_CANCELLED
                    && (int)$model->status !== self::STATUS_DRAFT;

                if ($isPaidOrProcessing) {
                    $model->ensureCreditMemoRecorded();
                }

                // 2. Saat pesanan dikirimkan (Shipped / 4) atau diterima (Delivered / 5):
                $isShippedOrDelivered = in_array((int)$model->status, [self::STATUS_SHIPPED, self::STATUS_DELIVERED], true);
                if ($isShippedOrDelivered) {
                    $model->ensurePaymentRecorded();
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("Failed ensuring CreditMemo / Payment for Order #{$model->order_number}: " . $e->getMessage());
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

    public function creditMemo(): HasOne
    {
        return $this->hasOne(CreditMemo::class, 'order_id', 'id')->latest('created_at');
    }

    public function creditMemos(): HasMany
    {
        return $this->hasMany(CreditMemo::class, 'order_id', 'id');
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class, 'order_id', 'id')->latest('created_at');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'order_id', 'id');
    }

    /**
     * Catat pembayaran pelanggan ke tabel sales.credit_memos saat status pesanan Paid / Processing.
     */
    public function ensureCreditMemoRecorded(array $overrides = []): ?CreditMemo
    {
        $existing = CreditMemo::where('order_id', $this->id)->first();
        if ($existing) {
            return $existing;
        }

        $gateway = $overrides['gateway'] ?? null;
        if (!$gateway) {
            if ($this->relationLoaded('paymentMethod') && $this->paymentMethod) {
                $gateway = $this->paymentMethod->code ?? $this->paymentMethod->name;
            } elseif ($this->payment_method) {
                $pm = \App\Models\PaymentMethod::find($this->payment_method);
                $gateway = $pm?->code ?? ($pm?->name ?? 'manual');
            }
            $gateway = $gateway ?: ($this->meta['gateway'] ?? ($this->meta['payment_gateway'] ?? 'manual_transfer'));
        }

        $trxId = $overrides['transaction_id'] ?? ($this->meta['trx_id'] ?? ($this->meta['reference'] ?? ($this->meta['espay_reference'] ?? (string) Str::uuid())));
        $payload = $overrides['payload'] ?? ($this->meta ?? []);
        $amount = isset($overrides['amount']) ? (float) $overrides['amount'] : (float) ($this->total ?? 0);

        return CreditMemo::create([
            'id' => (string) Str::uuid(),
            'credit_memo_number' => CreditMemo::generateNumber(),
            'order_id' => $this->id,
            'gateway' => (string) $gateway,
            'transaction_id' => (string) $trxId,
            'amount' => $amount,
            'status' => 'paid',
            'payload' => $payload,
            'paid_at' => now(),
        ]);
    }

    /**
     * Pindahkan / catat pembayaran resmi ke sales.payments saat pesanan Dikirim (Shipped) atau Diterima (Delivered).
     */
    public function ensurePaymentRecorded(array $overrides = []): ?Payment
    {
        $existing = Payment::where('order_id', $this->id)->first();
        if ($existing) {
            return $existing;
        }

        $creditMemo = CreditMemo::where('order_id', $this->id)->first();
        if (!$creditMemo) {
            $creditMemo = $this->ensureCreditMemoRecorded($overrides);
        }

        return Payment::create([
            'id' => (string) Str::uuid(),
            'payment_number' => Payment::generateNumber(),
            'order_id' => $this->id,
            'gateway' => $overrides['gateway'] ?? ($creditMemo->gateway ?? 'manual'),
            'transaction_id' => $overrides['transaction_id'] ?? ($creditMemo->transaction_id ?? (string) Str::uuid()),
            'amount' => isset($overrides['amount']) ? (float) $overrides['amount'] : (float) ($creditMemo->amount ?? $this->total),
            'status' => 'settlement',
            'payload' => $overrides['payload'] ?? ($creditMemo->payload ?? ($this->meta ?? [])),
            'paid_at' => $creditMemo->paid_at ?: now(),
        ]);
    }

    public static function statusLabels(): array
    {
        $dbLabels = \App\Models\Reff::getOptions('StatusOrder');
        if (!empty($dbLabels)) {
            return $dbLabels;
        }

        return [
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

    public function statusLabelIndo(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING_APPROVAL => 'Menunggu Pembayaran',
            self::STATUS_CONFIRMED => 'Dikonfirmasi',
            self::STATUS_PROCESSING => 'Diproses',
            self::STATUS_SHIPPED => 'Dikirim',
            self::STATUS_DELIVERED => 'Terkirim',
            self::STATUS_CANCELLED => 'Dibatalkan',
            self::STATUS_RETURNED => 'Dikembalikan',
            default => 'Unknown',
        };
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

    public function getSourcePlatformAttribute(): string
    {
        $platform = strtolower(trim((string) ($this->meta['platform'] ?? '')));
        if (in_array($platform, ['mobile_app', 'mobile', 'app', 'android', 'ios'])) {
            return 'mobile';
        }
        if (in_array($platform, ['website', 'web', 'store_web', 'frontend'])) {
            return 'web';
        }
        if (in_array($platform, ['pos', 'store', 'outlet', 'retail_pos'])) {
            return 'pos';
        }

        // Fallbacks based on order_number or creator patterns
        $orderNum = (string) ($this->order_number ?? '');
        if (str_starts_with($orderNum, 'MOB-') || str_starts_with($orderNum, 'ORD-260831')) {
            return 'mobile';
        }
        if (str_starts_with($orderNum, 'ORD-POS-') || str_starts_with((string)$this->creator, 'Store - ')) {
            return 'pos';
        }
        if (str_starts_with($orderNum, 'WEB-') || str_starts_with($orderNum, 'ORD.')) {
            return 'web';
        }

        return 'web';
    }

    public function getSourcePlatformLabelAttribute(): string
    {
        return match($this->source_platform) {
            'mobile' => 'Mobile',
            'pos' => 'POS',
            default => 'Web',
        };
    }

    public function getSourcePlatformBadgeClassAttribute(): string
    {
        return match($this->source_platform) {
            'mobile' => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
            'pos' => 'bg-purple-50 text-purple-700 border border-purple-200',
            default => 'bg-blue-50 text-blue-700 border border-blue-200',
        };
    }

    public function isPaymentPaid(): bool
    {
        return ((int) $this->payment_status === self::PAYMENT_PAID || (int) $this->payment_status === 2 || (int) $this->status >= self::STATUS_PROCESSING)
            && (int) $this->status !== self::STATUS_CANCELLED;
    }

    public function paymentStatusLabel(): string
    {
        if ($this->isPaymentPaid()) {
            return 'Paid';
        }
        if ((int) $this->payment_status === self::PAYMENT_UNPAID) {
            return 'Unpaid';
        }
        return \App\Models\Reff::getShow('PaymentStatus', $this->payment_status, 'Unpaid');
    }

    public function getPaymentStatusBadgeClassAttribute(): string
    {
        if ($this->isPaymentPaid()) {
            return 'bg-success/10 text-success';
        }
        if ((int) $this->payment_status === self::PAYMENT_UNPAID) {
            return 'bg-gray-100 text-gray-600';
        }
        return match ($this->payment_status) {
            self::PAYMENT_FAILED => 'bg-danger/10 text-danger',
            self::PAYMENT_REFUNDED => 'bg-orange-100 text-orange-700',
            self::PAYMENT_PARTIAL => 'bg-warning/10 text-warning',
            default => 'bg-gray-100 text-gray-600',
        };
    }

    public function getSourcePlatformIconAttribute(): string
    {
        return match($this->source_platform) {
            'mobile' => 'smartphone',
            'pos' => 'storefront',
            default => 'language',
        };
    }

    /**
     * Status PPN Aktif
     */
    public function getIsPpnActiveAttribute(): bool
    {
        if (isset($this->attributes['is_ppn_active'])) {
            return (bool) $this->attributes['is_ppn_active'];
        }
        if (isset($this->meta['is_ppn_active'])) {
            return (bool) $this->meta['is_ppn_active'];
        }
        return (float) ($this->attributes['ppn_rate'] ?? 0) > 0;
    }

    /**
     * Dapatkan tarif PPN order secara dinamis
     */
    public function getPpnRateAttribute($value): float
    {
        if (isset($this->attributes['is_ppn_active']) && !$this->attributes['is_ppn_active']) {
            return 0.0;
        }

        if ($value !== null && $value !== '') {
            return (float) $value;
        }

        return (float) config('services.tax.ppn_rate', 11.0);
    }

    /**
     * Dapatkan Sub Total Produk Bersih (setelah Diskon Promo & Voucher)
     * Formula Excel "Cara Hitung PPN Website.xlsx":
     * Sub Total = Harga Produk - Diskon - Voucher
     */
    public function getNetProductSubtotal(): float
    {
        $hargaProduk = (float) ($this->subtotal ?? 0);
        $diskon = (float) ($this->discount ?? 0);
        return max(0.0, round($hargaProduk - $diskon, 2));
    }

    /**
     * Dapatkan total nilai Sebelum PPN (DPP)
     * Formula Excel: DPP = Sub Total / (1 + (ppn_rate / 100))
     */
    public function getDppAmountAttribute($value): float
    {
        $netSubtotal = $this->getNetProductSubtotal();
        if ($netSubtotal <= 0) {
            return 0.0;
        }

        $rate = (float) $this->ppn_rate;
        if ($rate <= 0) {
            return $netSubtotal;
        }

        return round($netSubtotal / (1 + ($rate / 100)), 2);
    }

    /**
     * Dapatkan total nominal PPN
     * Formula Excel: PPN = DPP * (ppn_rate / 100) atau Sub Total - DPP
     */
    public function getPpnAmountAttribute($value): float
    {
        $netSubtotal = $this->getNetProductSubtotal();
        if ($netSubtotal <= 0) {
            return 0.0;
        }

        $rate = (float) $this->ppn_rate;
        if ($rate <= 0) {
            return 0.0;
        }

        return round($netSubtotal - $this->dpp_amount, 2);
    }
}
