<?php

namespace App\Events;

use App\Models\MasterLocation;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Вещается через очередь, а не синхронно: мастер присылает координаты каждые
 * несколько секунд, и HTTP-запрос мобильного приложения не должен ждать
 * публикации в Reverb.
 */
class MasterLocationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Отдельная очередь: тяжёлая конвертация фото на `default` не должна
     * задерживать трекинг на карте.
     * Воркер обязан слушать обе: `php artisan queue:work --queue=broadcasts,default`.
     */
    public string $broadcastQueue = 'broadcasts';

    /**
     * Повторять бессмысленно — к моменту retry координата уже устарела,
     * а следом придёт свежая.
     */
    public int $tries = 1;

    public function __construct(public MasterLocation $location) {}

    /** @return array<int, Channel> */
    public function broadcastOn(): array
    {
        return [
            new Channel('masters-map'),
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
