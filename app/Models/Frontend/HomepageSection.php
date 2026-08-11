<?php

declare(strict_types=1);

namespace App\Models\Frontend;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class HomepageSection extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $table = 'homepage_sections';

    protected $fillable = [
        'section_key',
        'title',
        'sort_order',
        'is_visible',
        'meta',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_visible' => 'boolean',
        'meta' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public static function orderedVisible(): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('is_visible', true)
            ->orderBy('sort_order', 'asc')
            ->get();
    }
}
