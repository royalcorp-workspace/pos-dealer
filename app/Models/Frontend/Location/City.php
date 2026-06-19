<?php

declare(strict_types=1);

namespace App\Models\Frontend\Location;

use App\Models\Frontend\Location\Province;
use App\Models\Frontend\Location\SubDistrict;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    use HasUuids;

    protected $table = 'cities';

    protected $fillable = [
        'province_id',
        'name',
        'is_active',
        'sort_order',
        'creator',
        'editor',
        'deleted',
    ];

    protected function casts(): array
    {
        return [
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

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'province_id', 'id');
    }

    public function subDistricts(): HasMany
    {
        return $this->hasMany(SubDistrict::class, 'city_id', 'id');
    }
}
