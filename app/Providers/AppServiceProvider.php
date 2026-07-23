<?php

namespace App\Providers;

use App\Models\Master;
use App\Observers\MasterObserver;
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

        $this->registerQueueHeartbeat();
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
