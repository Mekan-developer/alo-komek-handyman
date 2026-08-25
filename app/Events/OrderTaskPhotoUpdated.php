<?php

namespace App\Events;

use App\Models\OrderTaskPhoto;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderTaskPhotoUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public OrderTaskPhoto $photo) {}

    /** @return array<int, Channel> */
    public function broadcastOn(): array
    {
        return [new Channel('orders')];
    }

    public function broadcastAs(): string
    {
        return 'order.task.photo.updated';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->photo->task->order_id,
            'task_id' => $this->photo->order_task_id,
            'photo_id' => $this->photo->id,
            'type' => $this->photo->type,
            'status' => $this->photo->status,
        ];
    }
}
