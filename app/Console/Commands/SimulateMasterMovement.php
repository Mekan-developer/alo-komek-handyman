<?php

namespace App\Console\Commands;

use App\Actions\UpdateMasterLocationAction;
use App\Models\Master;
use App\Models\Order;
use App\OrderStatus;
use Illuminate\Console\Command;

class SimulateMasterMovement extends Command
{
    protected $signature = 'master:simulate-movement
        {master? : Master ID; if omitted, simulate for all active masters}
        {--interval=3 : Seconds between location pings}
        {--steps=120 : How many pings to emit before stopping}
        {--step-size=0.001 : Latitude/longitude shift per step (~100m)}';

    protected $description = 'Emit fake live location updates so the admin map can be verified without a Flutter app.';

    public function handle(UpdateMasterLocationAction $action): int
    {
        $masterId = $this->argument('master');
        $interval = (int) $this->option('interval');
        $steps = (int) $this->option('steps');
        $stepSize = (float) $this->option('step-size');

        $masters = $masterId
            ? Master::where('id', $masterId)->get()
            : Master::where('is_active', true)
                ->where(fn ($q) => $q->whereNull('access_expires_at')->orWhere('access_expires_at', '>', now()))
                ->get();

        if ($masters->isEmpty()) {
            $this->error('No matching active masters found.');

            return self::FAILURE;
        }

        // Initialize per-master starting point (use last known or default Ashgabat center)
        $positions = [];
        foreach ($masters as $master) {
            $last = $master->latestLocation;
            $positions[$master->id] = [
                'lat' => $last?->latitude ? (float) $last->latitude : 37.95 + mt_rand(-50, 50) / 1000,
                'lng' => $last?->longitude ? (float) $last->longitude : 58.38 + mt_rand(-50, 50) / 1000,
            ];
        }

        $this->info("Simulating movement for {$masters->count()} master(s). Interval: {$interval}s, steps: {$steps}.");
        $this->comment('Press Ctrl+C to stop early.');

        // Cache active order IDs per master to avoid N+1 inside the loop
        $activeOrderIds = Order::whereIn('master_id', $masters->pluck('id'))
            ->whereIn('status', [OrderStatus::Assigned->value, OrderStatus::InProgress->value])
            ->pluck('id', 'master_id');

        for ($i = 1; $i <= $steps; $i++) {
            foreach ($masters as $master) {
                $positions[$master->id]['lat'] += (mt_rand(-100, 100) / 100) * $stepSize;
                $positions[$master->id]['lng'] += (mt_rand(-100, 100) / 100) * $stepSize;

                $action->handle($master, [
                    'latitude' => $positions[$master->id]['lat'],
                    'longitude' => $positions[$master->id]['lng'],
                    'order_id' => $activeOrderIds->get($master->id),
                ]);
            }

            $this->line("Step {$i}/{$steps} → broadcasted to {$masters->count()} master(s).");

            if ($i < $steps) {
                sleep($interval);
            }
        }

        $this->info('Done.');

        return self::SUCCESS;
    }
}
