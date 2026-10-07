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

    public function isTypeDebitCard(): bool
    {
        return $this->type === 6;
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

    /**
     * Resolves Espay product code when multiple payment methods share the same clearing bank code (e.g. '014').
     * Escalation priority:
     * 1. If explicit product_code exists in bank_info or model, return it.
     * 2. If codeOrBank is already alphanumeric (e.g. 'BCAATM', 'CREDITCARD'), return it.
     * 3. If numeric bank code (e.g. '014', '008'), escalate based on payment type (VA vs CC vs E-Wallet).
     */
    public static function resolveEspayProductCode(?string $codeOrBank, ?int $type = null, ?self $paymentMethod = null): string
    {
        $codeOrBank = trim((string)$codeOrBank);
        if ($paymentMethod && is_array($paymentMethod->bank_info) && !empty($paymentMethod->bank_info['product_code'])) {
            return (string)$paymentMethod->bank_info['product_code'];
        }

        if (!empty($codeOrBank) && !ctype_digit($codeOrBank)) {
            return strtoupper($codeOrBank);
        }

        $bankCode = $codeOrBank ?: ($paymentMethod?->bank_info['bank_code'] ?? '');
        $bankCode = trim((string)$bankCode);
        $resolvedType = $type ?? $paymentMethod?->type;

        // Escalation map for shared clearing bank codes (e.g., 014 for BCA ATM vs Credit Card)
        $map = [
            '014' => [ // BCA
                5 => 'CREDITCARD',
                2 => 'BCAATM',
                3 => 'GOPAYINAPP',
                'default' => 'BCAATM',
            ],
            '008' => [ // Mandiri
                5 => 'CREDITCARD',
                2 => 'MANDIRIATM',
                3 => 'QRISPLUS',
                4 => 'QRISPLUS',
                'default' => 'MANDIRIATM',
            ],
            '002' => [ // BRI
                5 => 'CREDITCARD',
                2 => 'BRIATM',
                'default' => 'BRIATM',
            ],
            '022' => [ // CIMB Niaga
                5 => 'CREDITCARD',
                2 => 'CIMBATM',
                'default' => 'CIMBATM',
            ],
            '011' => [ // Danamon
                5 => 'CREDITCARD',
                2 => 'DANAMONATM',
                'default' => 'DANAMONATM',
            ],
            '016' => [ // Maybank / BII
                5 => 'CREDITCARD',
                2 => 'BIIATM',
                'default' => 'BIIATM',
            ],
            '472' => [ // Bank Saqu
                2 => 'BANKSAQUATM',
                'default' => 'BANKSAQUATM',
            ],
            '503' => [ // OVO
                3 => 'OVO',
                'default' => 'OVO',
            ],
        ];

        if (isset($map[$bankCode])) {
            if ($resolvedType && isset($map[$bankCode][$resolvedType])) {
                return $map[$bankCode][$resolvedType];
            }
            return $map[$bankCode]['default'];
        }

        return $bankCode ?: ($paymentMethod?->code ?? '');
    }

    /**
     * Resolves Espay numeric clearing bank code (e.g. '014', '008', '002').
     * The Espay SendInvoice endpoint expects bank_code to be the clearing bank code (contoh: 014, 008, 002).
     */
    public static function resolveEspayBankCode(?string $codeOrBank, ?int $type = null, ?self $paymentMethod = null): string
    {
        $codeOrBank = trim((string)$codeOrBank);

        // 1. If explicit bank_code exists in payment_method->bank_info
        if ($paymentMethod && is_array($paymentMethod->bank_info) && !empty($paymentMethod->bank_info['bank_code'])) {
            return trim((string)$paymentMethod->bank_info['bank_code']);
        }

        // 2. If codeOrBank or model code is already purely numeric (e.g. '014', '008', '002', '472', '503')
        $rawCandidate = !empty($codeOrBank) ? $codeOrBank : ($paymentMethod?->code ?? '');
        $rawCandidate = trim((string)$rawCandidate);
        if (!empty($rawCandidate) && ctype_digit($rawCandidate)) {
            return $rawCandidate;
        }

        // 3. Mapping from known alphanumeric product codes to numeric clearing bank codes
        $productToBankMap = [
            'BCAATM' => '014',
            'GOPAYINAPP' => '014',
            'GOPAYJUMPAPP' => '014',
            'MANDIRIATM' => '008',
            'QRISPLUS' => '008',
            'QRIS' => '008',
            'CREDITCARD' => '008',
            'BRIATM' => '002',
            'CIMBATM' => '022',
            'DANAMONATM' => '011',
            'BIIATM' => '016',
            'BANKSAQUATM' => '472',
            'OVO' => '503',
        ];

        $upper = strtoupper($rawCandidate);
        if (isset($productToBankMap[$upper])) {
            return $productToBankMap[$upper];
        }

        return $rawCandidate;
    }



    /**
     * Find payment method by code, product code, or clearing bank code with type escalation.
     */
    public static function findByCodeOrBank(?string $code, ?int $type = null): ?self
    {
        if (empty($code)) {
            return null;
        }

        $code = trim($code);

        // 1. Direct match on code
        $found = static::withoutGlobalScope('active')->where('code', $code)->first();
        if ($found) {
            return $found;
        }

        // 2. Direct match on case-insensitive code
        $found = static::withoutGlobalScope('active')->whereRaw('UPPER(code) = ?', [strtoupper($code)])->first();
        if ($found) {
            return $found;
        }

        // 3. Escalation lookup: check if code is a numeric bank code (e.g. 014) or product_code
        $resolvedProductCode = static::resolveEspayProductCode($code, $type);
        if ($resolvedProductCode && strtoupper($resolvedProductCode) !== strtoupper($code)) {
            $found = static::withoutGlobalScope('active')->whereRaw('UPPER(code) = ?', [strtoupper($resolvedProductCode)])->first();
            if ($found) {
                return $found;
            }
        }

        // 4. Match in bank_info JSON (product_code or bank_code)
        $query = static::withoutGlobalScope('active')
            ->where(function ($q) use ($code, $resolvedProductCode) {
                $q->where('bank_info->product_code', $code)
                  ->orWhere('bank_info->bank_code', $code)
                  ->orWhere('bank_info->product_code', $resolvedProductCode);
            });

        if ($type) {
            $query->where('type', $type);
        }

        return $query->first();
    }
}