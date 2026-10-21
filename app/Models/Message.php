<?php

namespace App\Models;

use App\Traits\HasSchemaTable;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Message extends Model
{
    use HasSchemaTable;

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

    protected function serializeDate(\DateTimeInterface $date)
    {
        return \Illuminate\Support\Carbon::instance($date)->toISOString();
    }
}
