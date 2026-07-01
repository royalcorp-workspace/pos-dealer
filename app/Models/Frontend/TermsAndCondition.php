<?php

declare(strict_types=1);

namespace App\Models\Frontend;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class TermsAndCondition extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $table = 'terms_and_conditions';

    protected static function boot(): void
    {
        parent::boot();
        static::addGlobalScope('not-deleted', fn($q) => $q->where('terms_and_conditions.deleted', false));
        static::addGlobalScope('published', fn($q) => $q->where('terms_and_conditions.is_published', true));
    }

    protected $fillable = [
        'title',
        'slug',
        'content',
        'version',
        'effective_date',
        'is_published',
        'sort_order',
        'meta_title',
        'meta_description',
        'creator',
        'editor',
        'deleted',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'deleted' => 'boolean',
            'sort_order' => 'integer',
            'effective_date' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
