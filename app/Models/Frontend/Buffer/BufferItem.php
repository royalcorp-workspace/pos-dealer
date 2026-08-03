<?php

declare(strict_types=1);

namespace App\Models\Frontend\Buffer;

use App\Models\Frontend\ProductsCatalog\Product;
use App\Models\Frontend\ProductsCatalog\ProductVariant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BufferItem extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'buffer_id',
        'product_id',
        'product_variant_id',
        'name',
        'quantity',
        'unit_price',
        'total',
        'discount_nominal',
        'discount_percent',
        'item_notes',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'total' => 'decimal:2',
            'discount_nominal' => 'decimal:2',
            'discount_percent' => 'decimal:2',
            'meta' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function buffer(): BelongsTo
    {
        return $this->belongsTo(Buffer::class, 'buffer_id', 'id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id', 'id');
    }
}
