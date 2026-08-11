<?php

declare(strict_types=1);

namespace App\Models\Frontend\Customer;

use App\Models\Frontend\Customer\Address;
use App\Models\Frontend\Order\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasUuids;

    protected $table = 'customers';

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'customer_type',
        'membership_level',
        'meta',
        'creator',
        'editor',
        'deleted',
    ];

    protected function casts(): array
    {
        return [
            'customer_type' => 'integer',
            'meta' => 'array',
            'deleted' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::addGlobalScope('active', function ($query) {
            $query->where('deleted', false);
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class, 'user_id', 'user_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'customer_id', 'id');
    }
}
