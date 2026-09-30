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
        'courier_id',
        'voucher_id',
        'voucher_nominal',
        'shipping_cost',
        'shipping_cost_subsidy',
        'shipping_addresses_id',
        'transaction_fee',
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
            'voucher_nominal' => 'decimal:2',
            'shipping_cost' => 'decimal:2',
            'shipping_cost_subsidy' => 'decimal:2',
            'transaction_fee' => 'decimal:2',
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

    public static function resolveActiveCart(): ?self
    {
        $customerId = null;
        if (session()->get('is_logged_in')) {
            $user = session()->get('user', []);
            $userId = $user['id'] ?? $user['sub'] ?? null;
            $email = $user['email'] ?? null;
            if ($userId) {
                $customer = FrontendCustomer::where('user_id', $userId)->first();
                if (!$customer && $email) {
                    $customer = FrontendCustomer::where('email', $email)->first();
                }
                $customerId = $customer?->id;
            }
        }

        $sessionGuestId = session()->get('guest_session_id');
        $cookieToken = request()->cookie('guest_session_id');
        $cookieBufferId = request()->cookie('buffer_cart_id');
        $currentSessionId = session()->getId();

        $sessionIds = array_values(array_filter(array_unique([$sessionGuestId, $cookieToken, $currentSessionId])));

        $query = self::where(function ($q) use ($customerId, $sessionIds) {
            if ($customerId) {
                $q->where('customer_id', $customerId);
                if (!empty($sessionIds)) {
                    $q->orWhereIn('session_id', $sessionIds);
                }
            } elseif (!empty($sessionIds)) {
                $q->whereIn('session_id', $sessionIds);
            }
        });

        // 1. Buffer that has items, latest updated
        $buffer = (clone $query)->whereHas('items')->latest('updated_at')->first();

        // 2. Cookie buffer_cart_id if has items
        if (!$buffer && $cookieBufferId) {
            $buffer = self::where('id', $cookieBufferId)->whereHas('items')->first();
        }

        // 3. Fallback: latest updated matching query
        if (!$buffer) {
            $buffer = (clone $query)->latest('updated_at')->first();
        }

        // 4. Fallback: buffer by cookie buffer_cart_id even if empty
        if (!$buffer && $cookieBufferId) {
            $buffer = self::where('id', $cookieBufferId)->first();
        }

        return $buffer;
    }
}
