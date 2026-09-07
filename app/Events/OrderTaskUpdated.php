<?php

namespace App\Events;

use App\Models\Order;
use App\Models\OrderTask;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderTaskUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Order $order, public OrderTask $task) {}

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
        return 'order.task.updated';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->order->id,
            'task_id' => $this->task->id,
            'title' => $this->task->title,
            'description' => $this->task->description,
        ];
    }
}
