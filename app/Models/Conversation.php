<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Conversation extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'customer_id',
        'subject',
        'status',
    ];

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer\Customer::class, 'customer_id');
    }

    public function latestMessage()
    {
        return $this->hasOne(Message::class)->latest('created_at');
    }

    protected function serializeDate(\DateTimeInterface $date)
    {
        return \Illuminate\Support\Carbon::instance($date)->toISOString();
    }
}
