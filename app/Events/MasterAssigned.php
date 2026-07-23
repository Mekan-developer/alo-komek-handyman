<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MasterAssigned implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Order $order) {}

    /** @return array<int, Channel> */
    public function broadcastOn(): array
    {
        $channels = [new Channel('orders')];

        if ($this->order->client_id) {
            $channels[] = new PrivateChannel('client.'.$this->order->client_id);
        }

        $channels[] = new PrivateChannel('master.'.$this->order->master_id);

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'master.assigned';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->order->id,
            'client_name' => $this->order->client_name,
            'master_id' => $this->order->master_id,
            'master_name' => $this->order->master?->name,
            'master_phone' => $this->order->master?->phone,
        ];
    }
}
