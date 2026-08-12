<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Message extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'sender_type',
        'text',
        'is_read',
    ];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function freshTimestamp()
    {
        return \Illuminate\Support\Carbon::now('UTC');
    }

    protected function asDateTime($value)
    {
        $date = parent::asDateTime($value);
        if (is_string($value)) {
            return \Illuminate\Support\Carbon::parse($value, 'UTC')->setTimezone(config('app.timezone'));
        }
        return $date;
    }
}
