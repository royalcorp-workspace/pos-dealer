<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use HasUuids;

    protected $table = 'payment_methods';

    protected $appends = ['image_url'];

    public function getImageUrlAttribute(): ?string
    {
        return media_url($this->image);
    }

    protected $fillable = [
        'code',
        'name',
        'type',
        'provider',
        'image',
        'has_charge',
        'charge_type',
        'charge_value',
        'charge_bearer',
        'minimum_amount',
        'maximum_amount',
        'sort_order',
        'status',
        'creator',
        'editor',
        'deleted',
        'bank_info',
        'instructions',
    ];

    protected function casts(): array
    {
        return [
            'type' => 'integer',
            'has_charge' => 'boolean',
            'charge_type' => 'integer',
            'charge_value' => 'decimal:2',
            'minimum_amount' => 'decimal:2',
            'maximum_amount' => 'decimal:2',
            'sort_order' => 'integer',
            'status' => 'integer',
            'deleted' => 'boolean',
            'bank_info' => 'array',
            'instructions' => 'array',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::addGlobalScope('active', function ($query) {
            $query->where('status', 1)
                ->where('deleted', false);
        });
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1)
            ->where('deleted', false);
    }

    public function isTypeBankTransfer(): bool
    {
        return $this->type === 1;
    }

    public function isTypeVa(): bool
    {
        return $this->type === 2;
    }

    public function isTypeEwallet(): bool
    {
        return $this->type === 3;
    }

    public function isTypeQris(): bool
    {
        return $this->type === 4;
    }

    public function isTypeCreditCard(): bool
    {
        return $this->type === 5;
    }

    public function calculateCharge(float $amount): float
    {
        if (!$this->has_charge || !$this->charge_value) {
            return 0;
        }

        if ($this->charge_type === 1) {
            return ($amount * $this->charge_value) / 100;
        }

        return (float) $this->charge_value;
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            1 => 'Bank Transfer',
            2 => 'Virtual Account',
            3 => 'E-Wallet',
            4 => 'QRIS',
            5 => 'Credit Card',
            6 => 'Debit Card',
            7 => 'COD',
            8 => 'PayLater',
            default => 'Unknown',
        };
    }

    public static function typeOptions(): array
    {
        return [
            1 => 'Bank Transfer',
            2 => 'Virtual Account',
            3 => 'E-Wallet',
            4 => 'QRIS',
            5 => 'Credit Card',
            6 => 'Debit Card',
            7 => 'COD',
            8 => 'PayLater',
        ];
    }
}