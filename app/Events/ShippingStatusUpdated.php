<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class ShippingStatusUpdated implements ShouldBroadcastNow
{
    public array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function broadcastOn(): array
    {
        $channels = [];

        if (!empty($this->data['user_id'])) {
            $channels[] = new Channel('user.' . $this->data['user_id']);
        }

        if (!empty($this->data['customer_id'])) {
            $channels[] = new Channel('customer.' . $this->data['customer_id']);
        }

        $channels[] = new Channel('admin.orders');

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'shipping.updated';
    }

    public function broadcastWith(): array
    {
        return $this->data;
    }
}
