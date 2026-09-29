<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class MessageSent implements ShouldBroadcastNow
{
    public $message;

    public function __construct($message)
    {
        $this->message = $message;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('chat.' . $this->message->conversation_id),
            new Channel('admin.chat')
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => (string) $this->message->id,
            'conversation_id' => (string) $this->message->conversation_id,
            'sender_id' => (string) $this->message->sender_id,
            'sender_type' => (string) $this->message->sender_type,
            'text' => $this->message->text,
            'created_at' => is_object($this->message->created_at) 
                ? \Illuminate\Support\Carbon::instance($this->message->created_at)->toISOString() 
                : $this->message->created_at,
        ];
    }
}
