<?php

namespace App\Events;

use App\Models\MasterLocation;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MasterLocationUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public MasterLocation $location) {}

    /** @return array<int, Channel> */
    public function broadcastOn(): array
    {
        return [
            new Channel('masters-map.'.$this->location->master->city_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'master.location.updated';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'master_id' => $this->location->master_id,
            'order_id' => $this->location->order_id,
            'latitude' => (float) $this->location->latitude,
            'longitude' => (float) $this->location->longitude,
            'recorded_at' => $this->location->recorded_at->toIso8601String(),
        ];
    }
}
