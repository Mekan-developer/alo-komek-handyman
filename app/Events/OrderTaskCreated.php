<?php

namespace App\Events;

use App\Models\OrderTask;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderTaskCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public OrderTask $task) {}

    /** @return array<int, PrivateChannel> */
    public function broadcastOn(): array
    {
        $this->task->loadMissing('order');

        $channels = [new PrivateChannel('orders')];

        if ($this->task->order?->client_id) {
            $channels[] = new PrivateChannel('client.'.$this->task->order->client_id);
        }

        if ($this->task->order?->master_id) {
            $channels[] = new PrivateChannel('master.'.$this->task->order->master_id);
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'order.task.created';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->task->order_id,
            'task_id' => $this->task->id,
            'title' => $this->task->title,
            'description' => $this->task->description,
        ];
    }
}
