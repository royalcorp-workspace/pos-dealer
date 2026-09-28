<?php

declare(strict_types=1);

namespace App\Models\Frontend\Customer;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CustomerGroup extends Model
{
    use HasUuids;

    protected $table = 'customer_groups';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'discount_percent',
        'is_active',
        'creator',
        'editor',
        'deleted',
    ];

    protected function casts(): array
    {
        return [
            'discount_percent' => 'decimal:2',
            'is_active' => 'boolean',
            'deleted' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::addGlobalScope('active', function ($query) {
            $query->where($query->getModel()->getTable() . '.deleted', false);
        });
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class, 'customer_group_members', 'customer_group_id', 'customer_id')
            ->wherePivot('deleted', false)
            ->withTimestamps();
    }
}
