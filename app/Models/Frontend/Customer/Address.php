<?php

declare(strict_types=1);

namespace App\Models\Frontend\Customer;

use App\Models\Frontend\Customer\Customer;
use App\Models\Frontend\Location\City;
use App\Models\Frontend\Location\SubDistrict;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    use HasUuids;

    protected $table = 'addresses';

    protected $fillable = [
        'user_id',
        'sub_district_id',
        'city_id',
        'label',
        'recipient_name',
        'phone',
        'address',
        'postal_code',
        'is_primary',
        'creator',
        'editor',
        'deleted',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'deleted' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'user_id', 'user_id');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_id', 'id');
    }

    public function subDistrict(): BelongsTo
    {
        return $this->belongsTo(SubDistrict::class, 'sub_district_id', 'id');
    }
}
