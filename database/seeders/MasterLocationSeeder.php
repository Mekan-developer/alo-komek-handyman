<?php

namespace Database\Seeders;

use App\Models\Master;
use App\Models\MasterLocation;
use Illuminate\Database\Seeder;

class MasterLocationSeeder extends Seeder
{
    /**
     * Seeds one current position per active master, so the admin map has
     * markers to render in dev. Real trajectories only ever come from
     * location pings recorded during an order (master_locations.order_id).
     */
    public function run(): void
    {
        $masters = Master::where('is_active', true)->get();

        foreach ($masters as $master) {
            MasterLocation::create([
                'master_id' => $master->id,
                'latitude' => 37.95 + mt_rand(-50, 50) / 1000,
                'longitude' => 58.38 + mt_rand(-50, 50) / 1000,
                'recorded_at' => now(),
            ]);
        }
    }
}
