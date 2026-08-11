<?php

declare(strict_types=1);

namespace App\Models\Frontend\ProductsCatalog;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductBundlingItem extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $table = 'products_bundling_items';

    protected $fillable = [
        'product_bundling_id',
        'product_id',
        'quantity',
        'variant_id',
        'creator',
        'editor',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function bundling(): BelongsTo
    {
        return $this->belongsTo(ProductBundling::class, 'product_bundling_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }
}
