<?php

declare(strict_types=1);

namespace App\Models\Frontend\ProductsCatalog;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $table = 'product_variants';

    protected $fillable = [
        'product_id',
        'sku',
        'variant_name',
        'width',
        'length',
        'height',
        'weight',
        'price',
        'reseller_price',
        'stock_quantity',
        'min_order_qty',
        'sort_order',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'width' => 'decimal:2',
            'length' => 'decimal:2',
            'height' => 'decimal:2',
            'weight' => 'decimal:2',
            'price' => 'decimal:2',
            'reseller_price' => 'decimal:2',
            'stock_quantity' => 'integer',
            'min_order_qty' => 'integer',
            'sort_order' => 'integer',
            'status' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}