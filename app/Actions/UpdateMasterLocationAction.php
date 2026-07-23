<?php

namespace App\Actions;

use App\Events\MasterLocationUpdated;
use App\Models\Master;
use App\Models\MasterLocation;

class UpdateMasterLocationAction
{
    /** @param array{latitude: float, longitude: float, order_id?: int|null, recorded_at?: string|null} $data */
    public function handle(Master $master, array $data): MasterLocation
    {
        $location = $master->locations()->create([
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'order_id' => $data['order_id'] ?? null,
            'recorded_at' => $data['recorded_at'] ?? now(),
        ]);

        MasterLocationUpdated::dispatch($location);

        return $location;
    }
}
