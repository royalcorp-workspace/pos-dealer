<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Reff extends Model
{
    use HasUuids;

    protected $table = 'reff';

    protected $fillable = [
        'name',
        'value',
        'show',
        'description',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Dapatkan teks label tampilan ('show') berdasarkan nama group dan nilai value-nya.
     */
    public static function getShow(string $name, int|string|null $value, string $default = '-'): string
    {
        if ($value === null || $value === '') {
            return $default;
        }

        $options = self::getOptions($name);
        return $options[(int) $value] ?? $default;
    }

    /**
     * Dapatkan mapping key-value [value => show] untuk dropdown atau pilihan filter.
     */
    public static function getOptions(string $name): array
    {
        return Cache::remember("reff_options_{$name}", 3600, function () use ($name) {
            return self::where('name', $name)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->pluck('show', 'value')
                ->toArray();
        });
    }

    /**
     * Bersihkan cache saat ada create/update/delete data reff.
     */
    protected static function booted(): void
    {
        static::saved(function ($model) {
            Cache::forget("reff_options_{$model->name}");
        });

        static::deleted(function ($model) {
            Cache::forget("reff_options_{$model->name}");
        });
    }
}
