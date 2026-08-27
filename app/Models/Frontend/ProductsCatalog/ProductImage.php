<?php

declare(strict_types=1);

namespace App\Models\Frontend\ProductsCatalog;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImage extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $table = 'product_images';

    protected $fillable = [
        'product_id',
        'variant_id',
        'image',
        'alt_text',
        'sort_order',
        'status',
    ];

    protected $appends = ['image_url'];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'status' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image) {
            return null;
        }

        if (filter_var($this->image, FILTER_VALIDATE_URL)) {
            return $this->image;
        }

        $cmsUrl = env('CMS_URL', '');
        if ($cmsUrl) {
            return rtrim($cmsUrl, '/') . '/storage/' . ltrim($this->image, '/');
        }

        return asset('storage/' . ltrim($this->image, '/'));
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