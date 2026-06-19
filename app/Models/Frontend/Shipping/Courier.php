<?php

declare(strict_types=1);

namespace App\Models\Frontend\Shipping;

use App\Models\Frontend\Shipping\ShippingAddress;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Courier extends Model
{
    use HasUuids;

    protected $table = 'couriers';

    protected $fillable = [
        'code',
        'name',
        'type',
        'is_active',
        'sort_order',
        'creator',
        'editor',
        'deleted',
    ];

    protected function casts(): array
    {
        return [
            'type' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'deleted' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::addGlobalScope('active', function ($query) {
            $query->where('is_active', true)
                ->where('deleted', false);
        });
    }

    public function shippingAddresses(): HasMany
    {
        return $this->hasMany(ShippingAddress::class, 'courier_id', 'id');
    }

    public function shippingPrices(): HasMany
    {
        return $this->hasMany(ShippingAddress::class, 'courier_id', 'id')->where('type', 1);
    }
}
