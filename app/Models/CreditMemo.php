<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasSchemaTable;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Frontend\Order;

class CreditMemo extends Model
{
    use HasSchemaTable;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'credit_memo_number',
        'order_id',
        'gateway',
        'transaction_id',
        'amount',
        'status',
        'payload',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payload' => 'array',
            'paid_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) \Illuminate\Support\Str::uuid();
            }
            if (empty($model->credit_memo_number)) {
                $model->credit_memo_number = static::generateNumber($model->paid_at ?? now());
            }
        });
    }

    /**
     * Format: CM.tahunbulantanggal.4digit (e.g. CM.20261120.0001)
     */
    public static function generateNumber($date = null): string
    {
        $dateObj = $date ? \Illuminate\Support\Carbon::parse($date) : now();
        $prefix = 'CM.' . $dateObj->format('Ymd') . '.';

        $last = static::withoutGlobalScopes()
            ->where('credit_memo_number', 'like', $prefix . '%')
            ->orderByRaw('LENGTH(credit_memo_number) DESC, credit_memo_number DESC')
            ->value('credit_memo_number');

        $nextSeq = 1;
        if ($last) {
            $seqPart = substr($last, strlen($prefix));
            if (is_numeric($seqPart)) {
                $nextSeq = (int) $seqPart + 1;
            }
        }

        $number = $prefix . str_pad((string) $nextSeq, 4, '0', STR_PAD_LEFT);
        while (static::withoutGlobalScopes()->where('credit_memo_number', $number)->exists()) {
            $nextSeq++;
            $number = $prefix . str_pad((string) $nextSeq, 4, '0', STR_PAD_LEFT);
        }

        return $number;
    }
}
