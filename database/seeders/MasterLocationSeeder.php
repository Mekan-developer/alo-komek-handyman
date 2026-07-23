<?php

namespace Database\Seeders;

use App\Models\Master;
use App\Models\MasterLocation;
use Illuminate\Database\Seeder;

class MasterLocationSeeder extends Seeder
{
    /**
     * Generates a movement history (last 4h, ~1 ping/min) for every active master,
     * so the trajectory feature has data to render in dev.
     */
    public function run(): void
    {
        $masters = Master::with('latestLocation')->where('is_active', true)->get();

        foreach ($masters as $master) {
            $start = $master->latestLocation;

            if ($start === null) {
                continue;
            }

            $lat = (float) $start->latitude;
            $lng = (float) $start->longitude;

            // 240 minute history, drift ~50m per minute
            for ($minute = 240; $minute >= 1; $minute--) {
                $lat += (mt_rand(-50, 50) / 100000);
                $lng += (mt_rand(-50, 50) / 100000);

                MasterLocation::create([
                    'master_id' => $master->id,
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'recorded_at' => now()->subMinutes($minute),
                ]);
            }
        }
    }
}
