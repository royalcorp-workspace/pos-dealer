<?php

declare(strict_types=1);

namespace App\Models\Frontend;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $table = 'banners';

    protected $fillable = [
        'title',
        'link_url',
        'is_active',
        'sort_order',
        'type',
        'device_flag',
        'placement_size',
        'content_type',
        'image_web_url',
        'image_mobile_url',
        'embed_web_content',
        'embed_mobile_content',
        'deleted',
        'creator',
        'editor',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'deleted' => 'boolean',
        'sort_order' => 'integer',
        'type' => 'integer',
        'device_flag' => 'integer',
        'placement_size' => 'integer',
        'content_type' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public const TYPE_SLIDER = 1;
    public const TYPE_RUNNING_BANNER = 2;

    public const DEVICE_ALL = 1;
    public const DEVICE_WEB = 2;
    public const DEVICE_MOBILE = 3;

    public const CONTENT_UPLOAD = 1;
    public const CONTENT_EMBED = 2;

    protected static function boot(): void
    {
        parent::boot();
        static::addGlobalScope('active', fn($q) => $q->where('is_active', true)->where('deleted', false));
    }

    public function scopeSliders($query)
    {
        return $query->where('type', self::TYPE_SLIDER)->orderBy('sort_order');
    }

    public function scopeRunningBanners($query)
    {
        return $query->where('type', self::TYPE_RUNNING_BANNER)->orderBy('sort_order');
    }

    public function scopeForWeb($query)
    {
        return $query->whereIn('device_flag', [self::DEVICE_ALL, self::DEVICE_WEB]);
    }

    public function getDisplayImageUrlAttribute(): ?string
    {
        return media_url($this->image_web_url ?: $this->image_mobile_url);
    }

    public function getImageWebFullUrlAttribute(): ?string
    {
        return media_url($this->image_web_url);
    }

    public function getImageMobileFullUrlAttribute(): ?string
    {
        return media_url($this->image_mobile_url);
    }

    public function images()
    {
        return $this->hasMany(BannerImage::class)->where('deleted', false)->orderBy('sort_order');
    }
}

class BannerImage extends Model
{
    use HasUuids;

    protected $table = 'banner_images';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'banner_id',
        'image_web_url',
        'image_mobile_url',
        'link_url',
        'sort_order',
        'deleted',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'deleted'    => 'boolean',
    ];

    public function getImageWebFullUrlAttribute(): ?string
    {
        return media_url($this->image_web_url);
    }

    public function getImageMobileFullUrlAttribute(): ?string
    {
        return media_url($this->image_mobile_url);
    }
}
