<?php

declare(strict_types=1);

namespace App\Models\Frontend;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'product_id',
        'order_id',
        'user_name',
        'user_email',
        'rating',
        'text',
        'image_url',
        'is_approved',
        'is_published',
        'report_count',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'is_approved' => 'boolean',
            'is_published' => 'boolean',
            'report_count' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::addGlobalScope('published', function ($query) {
            $query->where('is_published', true)
                ->where('is_approved', true);
        });
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true)
            ->where('is_approved', true);
    }

    public function scopeWithReports($query)
    {
        return $query->where('report_count', '>', 0);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Frontend\Order::class, 'order_id');
    }

    public static function avgRating(int $productId): float
    {
        return (float) static::where('product_id', $productId)
            ->where('is_published', true)
            ->where('is_approved', true)
            ->avg('rating');
    }

    public static function ratingDistribution(int $productId): array
    {
        $ratings = static::where('product_id', $productId)
            ->where('is_published', true)
            ->where('is_approved', true)
            ->selectRaw('rating, count(*) as count')
            ->groupBy('rating')
            ->pluck('count', 'rating')
            ->toArray();

        return [
            1 => $ratings[1] ?? 0,
            2 => $ratings[2] ?? 0,
            3 => $ratings[3] ?? 0,
            4 => $ratings[4] ?? 0,
            5 => $ratings[5] ?? 0,
        ];
    }
}
