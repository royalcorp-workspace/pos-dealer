<?php

declare(strict_types=1);

namespace App\Models\Frontend\ProductsCatalog;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductCategory extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $table = 'product_category';

    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'description',
        'sort_order',
        'status',
        'creator',
        'editor',
        'deleted',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'status' => 'boolean',
            'deleted' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ProductCategory::class, 'parent_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    public function getProductsCountWithChildren(): int
    {
        $ids = [$this->id];
        $childrenIds = $this->children()->where('deleted', false)->pluck('id')->toArray();
        $ids = array_merge($ids, $childrenIds);

        foreach ($childrenIds as $childId) {
            $child = self::find($childId);
            if ($child) {
                $grandchildrenIds = $child->children()->where('deleted', false)->pluck('id')->toArray();
                $ids = array_merge($ids, $grandchildrenIds);
            }
        }

        return Product::where('deleted', false)
            ->whereIn('category_id', array_unique($ids))
            ->count();
    }
}