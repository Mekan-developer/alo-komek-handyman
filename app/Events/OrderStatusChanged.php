<?php

namespace App\Events;

use App\Models\Order;
use App\OrderStatus;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderStatusChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Order $order,
        public OrderStatus $from,
        public OrderStatus $to,
    ) {}

    /** @return array<int, Channel> */
    public function broadcastOn(): array
    {
        $channels = [new Channel('orders')];

        if ($this->order->client_id) {
            $channels[] = new PrivateChannel('client.'.$this->order->client_id);
        }

        if ($this->order->master_id) {
            $channels[] = new PrivateChannel('master.'.$this->order->master_id);
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'order.status.changed';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->order->id,
            'client_name' => $this->order->client_name,
            'from' => $this->from->value,
            'to' => $this->to->value,
            'to_label' => $this->to->label(),
        ];
    }
}
