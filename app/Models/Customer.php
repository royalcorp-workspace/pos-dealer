<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Frontend\Order;
use App\Models\Frontend\ProductsCatalog\Address;

class Customer extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'meta',
        'creator',
        'editor',
        'deleted',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted' => 'boolean',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();
        static::addGlobalScope('active', fn ($q) => $q->where('deleted', false));
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class, 'user_id', 'user_id');
    }
}
