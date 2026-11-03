<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasSchemaTable;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use HasSchemaTable;

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
     * 2. If codeOrBank matches known bank aliases (e.g. 'MANDIRI', 'BRI', 'CIMB', 'DANAMON', 'SAQU', 'BII'), return official product code.
     * 3. If numeric bank code (e.g. '014', '008'), escalate based on payment type (VA vs CC vs E-Wallet).
     */
    public static function resolveEspayProductCode(?string $codeOrBank, ?int $type = null, ?self $paymentMethod = null): string
    {
        $codeOrBank = trim((string)$codeOrBank);
        if ($paymentMethod && is_array($paymentMethod->bank_info) && !empty($paymentMethod->bank_info['product_code'])) {
            return (string)$paymentMethod->bank_info['product_code'];
        }

        $upper = strtoupper($codeOrBank);

        $aliasProductMap = [
            'BCA' => 'BCAATM',
            'BCAVA' => 'BCAATM',
            'BCA_VA' => 'BCAATM',
            'VA_BCA' => 'BCAATM',
            'BCAATM' => 'BCAATM',

            'MANDIRI' => 'MANDIRIATM',
            'MANDIRIVA' => 'MANDIRIATM',
            'MANDIRI_VA' => 'MANDIRIATM',
            'VA_MANDIRI' => 'MANDIRIATM',
            'MANDIRIATM' => 'MANDIRIATM',

            'BRI' => 'BRIATM',
            'BRIVA' => 'BRIATM',
            'BRI_VA' => 'BRIATM',
            'VA_BRI' => 'BRIATM',
            'BRIATM' => 'BRIATM',

            'BNI' => 'BNIATM',
            'BNIVA' => 'BNIATM',
            'BNI_VA' => 'BNIATM',
            'VA_BNI' => 'BNIATM',
            'BNIATM' => 'BNIATM',

            'CIMB' => 'CIMBATM',
            'CIMBVA' => 'CIMBATM',
            'CIMB_VA' => 'CIMBATM',
            'VA_CIMB' => 'CIMBATM',
            'CIMBNIAGA' => 'CIMBATM',
            'CIMBATM' => 'CIMBATM',

            'DANAMON' => 'DANAMONATM',
            'DANAMONVA' => 'DANAMONATM',
            'DANAMON_VA' => 'DANAMONATM',
            'VA_DANAMON' => 'DANAMONATM',
            'DANAMONATM' => 'DANAMONATM',

            'PERMATA' => 'PERMATAATM',
            'PERMATAVA' => 'PERMATAATM',
            'PERMATA_VA' => 'PERMATAATM',
            'VA_PERMATA' => 'PERMATAATM',
            'PERMATAATM' => 'PERMATAATM',

            'SAQU' => 'BANKSAQUATM',
            'BANKSAQU' => 'BANKSAQUATM',
            'SAQUVA' => 'BANKSAQUATM',
            'SAQU_VA' => 'BANKSAQUATM',
            'VA_SAQU' => 'BANKSAQUATM',
            'BANKSAQUATM' => 'BANKSAQUATM',

            'BII' => 'BIIATM',
            'BIIVA' => 'BIIATM',
            'BII_VA' => 'BIIATM',
            'VA_BII' => 'BIIATM',
            'MAYBANK' => 'BIIATM',
            'MAYBANKVA' => 'BIIATM',
            'MAYBANK_VA' => 'BIIATM',
            'VA_MAYBANK' => 'BIIATM',
            'BIIATM' => 'BIIATM',

            'BSI' => 'BSIATM',
            'BSIVA' => 'BSIATM',
            'BSI_VA' => 'BSIATM',
            'VA_BSI' => 'BSIATM',
            'BSIATM' => 'BSIATM',

            'QRIS' => 'QRISPLUS',
            'QRISPLUS' => 'QRISPLUS',
            'GOPAY' => 'GOPAYJUMPAPP',
            'GOPAYINAPP' => 'GOPAYJUMPAPP',
            'GOPAYJUMPAPP' => 'GOPAYJUMPAPP',
            'OVO' => 'OVO',
            'CREDITCARD' => 'CREDITCARD',
            'CREDIT_CARD' => 'CREDITCARD',
            'DEBITCARD' => 'DEBITCARD',
            'DEBIT_CARD' => 'DEBITCARD',
        ];

        if (isset($aliasProductMap[$upper])) {
            return $aliasProductMap[$upper];
        }

        if (!empty($codeOrBank) && !ctype_digit($codeOrBank)) {
            return $upper;
        }

        $bankCode = $codeOrBank ?: ($paymentMethod?->bank_info['bank_code'] ?? '');
        $bankCode = trim((string)$bankCode);
        $resolvedType = $type ?? $paymentMethod?->type;

        // Escalation map for shared clearing bank codes (e.g., 014 for BCA ATM vs Credit Card)
        $map = [
            '014' => [ // BCA / GoPay
                5 => 'CREDITCARD',
                2 => 'BCAATM',
                3 => 'GOPAYJUMPAPP',
                'default' => 'BCAATM',
            ],
            '008' => [ // Mandiri
                5 => 'CREDITCARD',
                2 => 'MANDIRIATM',
                3 => 'MANDIRIATM',
                4 => 'QRISPLUS',
                'default' => 'MANDIRIATM',
            ],
            '002' => [ // BRI
                5 => 'CREDITCARD',
                2 => 'BRIATM',
                'default' => 'BRIATM',
            ],
            '009' => [ // BNI
                5 => 'CREDITCARD',
                2 => 'BNIATM',
                'default' => 'BNIATM',
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
            '013' => [ // Permata
                5 => 'CREDITCARD',
                2 => 'PERMATAATM',
                'default' => 'PERMATAATM',
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
            '451' => [ // BSI
                2 => 'BSIATM',
                'default' => 'BSIATM',
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
            'BCA' => '014',
            'BCAVA' => '014',
            'BCA_VA' => '014',
            'VA_BCA' => '014',
            'GOPAYINAPP' => '014',
            'GOPAYJUMPAPP' => '014',
            'GOPAY' => '014',

            'MANDIRIATM' => '008',
            'MANDIRI' => '008',
            'MANDIRIVA' => '008',
            'MANDIRI_VA' => '008',
            'VA_MANDIRI' => '008',

            'QRISPLUS' => '008',
            'QRIS' => '008',
            'CREDITCARD' => '008',
            'DEBITCARD' => '008',

            'BRIATM' => '002',
            'BRI' => '002',
            'BRIVA' => '002',
            'BRI_VA' => '002',
            'VA_BRI' => '002',

            'BNIATM' => '009',
            'BNI' => '009',
            'BNIVA' => '009',
            'BNI_VA' => '009',
            'VA_BNI' => '009',

            'CIMBATM' => '022',
            'CIMB' => '022',
            'CIMBVA' => '022',
            'CIMB_VA' => '022',
            'VA_CIMB' => '022',
            'CIMBNIAGA' => '022',

            'DANAMONATM' => '011',
            'DANAMON' => '011',
            'DANAMONVA' => '011',
            'DANAMON_VA' => '011',
            'VA_DANAMON' => '011',

            'PERMATAATM' => '013',
            'PERMATA' => '013',
            'PERMATAVA' => '013',
            'PERMATA_VA' => '013',
            'VA_PERMATA' => '013',

            'BIIATM' => '016',
            'BII' => '016',
            'BIIVA' => '016',
            'BII_VA' => '016',
            'VA_BII' => '016',
            'MAYBANK' => '016',
            'MAYBANKATM' => '016',
            'MAYBANKVA' => '016',
            'MAYBANK_VA' => '016',
            'VA_MAYBANK' => '016',

            'BANKSAQUATM' => '472',
            'BANKSAQU' => '472',
            'SAQU' => '472',
            'SAQUATM' => '472',
            'SAQUVA' => '472',
            'SAQU_VA' => '472',
            'VA_SAQU' => '472',

            'BSIATM' => '451',
            'BSI' => '451',
            'BSIVA' => '451',
            'BSI_VA' => '451',
            'VA_BSI' => '451',

            'OVO' => '503',
        ];

        $upper = strtoupper($rawCandidate);
        if (isset($productToBankMap[$upper])) {
            return $productToBankMap[$upper];
        }

        return $rawCandidate;
    }

    /**
     * Checks if a code or alias belongs to a Virtual Account channel.
     */
    public static function isVaCodeOrAlias(?string $code): bool
    {
        if (empty($code)) {
            return false;
        }
        $upper = strtoupper(trim((string)$code));
        $vaCodes = [
            'BCAATM', 'BCAVA', 'BCA_VA', 'VA_BCA', 'BCA', '014',
            'MANDIRIATM', 'MANDIRIVA', 'MANDIRI_VA', 'VA_MANDIRI', 'MANDIRI', '008',
            'BRIATM', 'BRIVA', 'BRI_VA', 'VA_BRI', 'BRI', '002',
            'BNIATM', 'BNIVA', 'BNI_VA', 'VA_BNI', 'BNI', '009',
            'CIMBATM', 'CIMBVA', 'CIMB_VA', 'VA_CIMB', 'CIMB', 'CIMBNIAGA', '022',
            'DANAMONATM', 'DANAMONVA', 'DANAMON_VA', 'VA_DANAMON', 'DANAMON', '011',
            'PERMATAATM', 'PERMATAVA', 'PERMATA_VA', 'VA_PERMATA', 'PERMATA', '013',
            'BANKSAQUATM', 'BANKSAQUVA', 'BANKSAQU', 'SAQUATM', 'SAQUVA', 'SAQU', 'VA_SAQU', '472',
            'BIIATM', 'BIIVA', 'BII_VA', 'VA_BII', 'BII', 'MAYBANKATM', 'MAYBANKVA', 'MAYBANK', 'VA_MAYBANK', '016',
            'BSIATM', 'BSIVA', 'BSI_VA', 'VA_BSI', 'BSI', '451',
        ];

        return in_array($upper, $vaCodes, true) 
            || str_contains($upper, 'ATM') 
            || str_contains($upper, '_VA') 
            || str_contains($upper, 'VA_')
            || (str_ends_with($upper, 'VA') && strlen($upper) > 2);
    }

    /**
     * Find payment method by code, product code, or clearing bank code with type escalation.
     */
    public static function findByCodeOrBank(?string $code, ?int $type = null): ?self
    {
        if (empty($code)) {
            if ($type) {
                return static::withoutGlobalScope('active')->where('type', $type)->first();
            }
            return null;
        }

        $code = trim($code);

        if (\Illuminate\Support\Str::isUuid($code)) {
            $byUuid = static::withoutGlobalScope('active')->find($code);
            if ($byUuid) {
                return $byUuid;
            }
        }

        $upper = strtoupper($code);

        // 1. Khusus GoPay E-Wallet Jump App lookup (Type 3)
        if (in_array($upper, ['GOPAYJUMPAPP', 'GOPAYINAPP', 'GOPAY']) || ($type === 3 && $code === '014')) {
            $gopay = static::withoutGlobalScope('active')
                ->where('type', 3)
                ->where(function($q) {
                    $q->where('code', 'GOPAYJUMPAPP')
                      ->orWhere('code', '014')
                      ->orWhere('code', 'GOPAYINAPP')
                      ->orWhereRaw("UPPER(name) LIKE '%GOPAY%'");
                })
                ->first();
            if ($gopay) {
                return $gopay;
            }
        }

        // 2. Khusus BCA Virtual Account (Type 2)
        if (in_array($upper, ['BCAATM', 'BCA_VA', 'BCAVA', 'BCA', 'VA_BCA']) || ($type === 2 && $code === '014')) {
            $bca = static::withoutGlobalScope('active')
                ->where('type', 2)
                ->where(function($q) {
                    $q->where('code', 'BCAATM')
                      ->orWhere('code', '014')
                      ->orWhereRaw("UPPER(name) LIKE '%BCA%'");
                })
                ->first();
            if ($bca) {
                return $bca;
            }
        }

        // 3. Khusus Mandiri Virtual Account (Type 2)
        if (in_array($upper, ['MANDIRIATM', 'MANDIRI_VA', 'MANDIRIVA', 'MANDIRI', 'VA_MANDIRI']) || ($type === 2 && $code === '008')) {
            $mandiri = static::withoutGlobalScope('active')
                ->where(function($q) {
                    $q->where('type', 2)->orWhereRaw("UPPER(name) LIKE '%MANDIRI%'");
                })
                ->where(function($q) {
                    $q->where('code', 'MANDIRIATM')
                      ->orWhere('code', '008')
                      ->orWhereRaw("UPPER(name) LIKE '%MANDIRI%'");
                })
                ->orderByRaw("CASE WHEN type = 2 THEN 0 ELSE 1 END")
                ->first();
            if ($mandiri) {
                return $mandiri;
            }
        }

        // 4. Khusus BRI Virtual Account (Type 2)
        if (in_array($upper, ['BRIATM', 'BRI_VA', 'BRIVA', 'BRI', 'VA_BRI']) || ($type === 2 && $code === '002')) {
            $bri = static::withoutGlobalScope('active')
                ->where(function($q) {
                    $q->where('type', 2)->orWhereRaw("UPPER(name) LIKE '%BRI%'");
                })
                ->where(function($q) {
                    $q->where('code', 'BRIATM')
                      ->orWhere('code', '002')
                      ->orWhereRaw("UPPER(name) LIKE '%BRI%'");
                })
                ->orderByRaw("CASE WHEN type = 2 THEN 0 ELSE 1 END")
                ->first();
            if ($bri) {
                return $bri;
            }
        }

        // 5. Khusus CIMB Virtual Account (Type 2)
        if (in_array($upper, ['CIMBATM', 'CIMB_VA', 'CIMBVA', 'CIMB', 'VA_CIMB', 'CIMBNIAGA']) || ($type === 2 && $code === '022')) {
            $cimb = static::withoutGlobalScope('active')
                ->where(function($q) {
                    $q->where('type', 2)->orWhereRaw("UPPER(name) LIKE '%CIMB%'");
                })
                ->where(function($q) {
                    $q->where('code', 'CIMBATM')
                      ->orWhere('code', '022')
                      ->orWhereRaw("UPPER(name) LIKE '%CIMB%'");
                })
                ->orderByRaw("CASE WHEN type = 2 THEN 0 ELSE 1 END")
                ->first();
            if ($cimb) {
                return $cimb;
            }
        }

        // 6. Khusus Danamon Virtual Account (Type 2)
        if (in_array($upper, ['DANAMONATM', 'DANAMON_VA', 'DANAMONVA', 'DANAMON', 'VA_DANAMON']) || ($type === 2 && $code === '011')) {
            $danamon = static::withoutGlobalScope('active')
                ->where(function($q) {
                    $q->where('type', 2)->orWhereRaw("UPPER(name) LIKE '%DANAMON%'");
                })
                ->where(function($q) {
                    $q->where('code', 'DANAMONATM')
                      ->orWhere('code', '011')
                      ->orWhereRaw("UPPER(name) LIKE '%DANAMON%'");
                })
                ->orderByRaw("CASE WHEN type = 2 THEN 0 ELSE 1 END")
                ->first();
            if ($danamon) {
                return $danamon;
            }
        }

        // 7. Khusus Bank Saqu Virtual Account (Type 2)
        if (in_array($upper, ['BANKSAQUATM', 'BANKSAQU_VA', 'SAQUATM', 'SAQUVA', 'BANKSAQU', 'SAQU', 'VA_SAQU']) || ($type === 2 && $code === '472')) {
            $saqu = static::withoutGlobalScope('active')
                ->where(function($q) {
                    $q->where('type', 2)->orWhereRaw("UPPER(name) LIKE '%SAQU%'");
                })
                ->where(function($q) {
                    $q->where('code', 'BANKSAQUATM')
                      ->orWhere('code', '472')
                      ->orWhereRaw("UPPER(name) LIKE '%SAQU%'");
                })
                ->orderByRaw("CASE WHEN type = 2 THEN 0 ELSE 1 END")
                ->first();
            if ($saqu) {
                return $saqu;
            }
        }

        // 8. Khusus Maybank / BII Virtual Account (Type 2)
        if (in_array($upper, ['BIIATM', 'BII_VA', 'BIIVA', 'BII', 'MAYBANKATM', 'MAYBANKVA', 'MAYBANK', 'VA_MAYBANK']) || ($type === 2 && $code === '016')) {
            $bii = static::withoutGlobalScope('active')
                ->where(function($q) {
                    $q->where('type', 2)->orWhereRaw("UPPER(name) LIKE '%MAYBANK%' OR UPPER(name) LIKE '%BII%'");
                })
                ->where(function($q) {
                    $q->where('code', 'BIIATM')
                      ->orWhere('code', '016')
                      ->orWhereRaw("UPPER(name) LIKE '%MAYBANK%' OR UPPER(name) LIKE '%BII%'");
                })
                ->orderByRaw("CASE WHEN type = 2 THEN 0 ELSE 1 END")
                ->first();
            if ($bii) {
                return $bii;
            }
        }

        // 9. Khusus BNI Virtual Account (Type 2)
        if (in_array($upper, ['BNIATM', 'BNI_VA', 'BNIVA', 'BNI', 'VA_BNI']) || ($type === 2 && $code === '009')) {
            $bni = static::withoutGlobalScope('active')
                ->where(function($q) {
                    $q->where('type', 2)->orWhereRaw("UPPER(name) LIKE '%BNI%'");
                })
                ->where(function($q) {
                    $q->where('code', 'BNIATM')
                      ->orWhere('code', '009')
                      ->orWhereRaw("UPPER(name) LIKE '%BNI%'");
                })
                ->orderByRaw("CASE WHEN type = 2 THEN 0 ELSE 1 END")
                ->first();
            if ($bni) {
                return $bni;
            }
        }

        // 10. Khusus Permata Virtual Account (Type 2)
        if (in_array($upper, ['PERMATAATM', 'PERMATA_VA', 'PERMATAVA', 'PERMATA', 'VA_PERMATA']) || ($type === 2 && $code === '013')) {
            $permata = static::withoutGlobalScope('active')
                ->where(function($q) {
                    $q->where('type', 2)->orWhereRaw("UPPER(name) LIKE '%PERMATA%'");
                })
                ->where(function($q) {
                    $q->where('code', 'PERMATAATM')
                      ->orWhere('code', '013')
                      ->orWhereRaw("UPPER(name) LIKE '%PERMATA%'");
                })
                ->orderByRaw("CASE WHEN type = 2 THEN 0 ELSE 1 END")
                ->first();
            if ($permata) {
                return $permata;
            }
        }

        // 11. Khusus QRIS (Type 4)
        if (in_array($upper, ['QRISPLUS', 'QRIS']) || $type === 4) {
            $qris = static::withoutGlobalScope('active')
                ->where(function($q) {
                    $q->where('code', 'QRISPLUS')
                      ->orWhere('code', 'QRIS')
                      ->orWhere('type', 4)
                      ->orWhereRaw("UPPER(name) LIKE '%QRIS%'");
                })
                ->orderByRaw("CASE WHEN type = 4 THEN 0 ELSE 1 END")
                ->first();
            if ($qris) {
                return $qris;
            }
        }

        // 12. Khusus Kartu Kredit (Type 5)
        if ($type === 5 || in_array($upper, ['CREDITCARD', 'CREDIT_CARD', 'CREDIT CARD', 'CC', 'CREDIT'])) {
            $cc = static::withoutGlobalScope('active')
                ->where('type', 5)
                ->first();
            if ($cc) {
                return $cc;
            }
        }

        // 13. Khusus Kartu Debit (Type 6)
        if ($type === 6 || in_array($upper, ['DEBITCARD', 'DEBIT_CARD', 'DEBIT CARD', 'DEBIT'])) {
            $dc = static::withoutGlobalScope('active')
                ->where('type', 6)
                ->first();
            if ($dc) {
                return $dc;
            }
        }

        // Prioritas match code dengan type yang sesuai
        if ($type) {
            $found = static::withoutGlobalScope('active')->where('code', $code)->where('type', $type)->first();
            if ($found) {
                return $found;
            }

            $found = static::withoutGlobalScope('active')->whereRaw('UPPER(code) = ?', [$upper])->where('type', $type)->first();
            if ($found) {
                return $found;
            }
        }

        // Direct match on code
        $found = static::withoutGlobalScope('active')->where('code', $code)->first();
        if ($found) {
            return $found;
        }

        // Direct match on case-insensitive code
        $found = static::withoutGlobalScope('active')->whereRaw('UPPER(code) = ?', [$upper])->first();
        if ($found) {
            return $found;
        }

        // Escalation lookup: check if code is a numeric bank code (e.g. 014) or product_code
        $resolvedProductCode = static::resolveEspayProductCode($code, $type);
        if ($resolvedProductCode && strtoupper($resolvedProductCode) !== $upper) {
            $q = static::withoutGlobalScope('active')->whereRaw('UPPER(code) = ?', [strtoupper($resolvedProductCode)]);
            if ($type) {
                $q->where('type', $type);
            }
            $found = $q->first();
            if ($found) {
                return $found;
            }
        }

        // Match in bank_info JSON (product_code or bank_code)
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