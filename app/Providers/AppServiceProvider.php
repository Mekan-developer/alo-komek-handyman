<?php

namespace App\Providers;

use App\Broadcasting\ResilientBroadcaster;
use App\Models\Master;
use App\Models\OrderTask;
use App\Observers\MasterObserver;
use App\Observers\OrderTaskObserver;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        Master::observe(MasterObserver::class);
        OrderTask::observe(OrderTaskObserver::class);

        $this->registerResilientBroadcaster();
        $this->registerQueueHeartbeat();
    }

    /**
     * Регистрирует broadcast-драйвер `resilient` — обёртку над реальным
     * соединением, которая не даёт упавшему Reverb уронить запрос.
     *
     * Оборачиваемое соединение задаётся ключом `connection` в
     * `config/broadcasting.php`.
     */
    private function registerResilientBroadcaster(): void
    {
        Broadcast::extend('resilient', function (Application $app, array $config): ResilientBroadcaster {
            $connection = $config['connection'] ?? 'reverb';

            return new ResilientBroadcaster(
                Broadcast::connection($connection),
                $connection,
                (int) ($config['unavailable_cooldown'] ?? 30),
            );
        });
    }

    /**
     * Пишет heartbeat живого queue-воркера на каждой итерации его цикла.
     *
     * Событие `Queue::looping` срабатывает только пока запущен `queue:work`,
     * поэтому индикатор очереди в админке отражает именно состояние воркера и
     * больше не зависит от планировщика (`schedule:work`).
     */
    private function registerQueueHeartbeat(): void
    {
        Queue::looping(function (): void {
            Cache::put('queue:worker_heartbeat', now()->timestamp, 180);
        });
    }
}
