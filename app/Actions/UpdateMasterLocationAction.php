<?php

namespace App\Actions;

use App\Events\MasterLocationUpdated;
use App\Models\Master;
use App\Models\MasterLocation;
use App\Repositories\OrderRepository;

class UpdateMasterLocationAction
{
    public function __construct(private readonly OrderRepository $orders) {}

    /**
     * Persist a location ping and broadcast it to the admin map channel.
     *
     * @param  array{latitude: float|int|string, longitude: float|int|string, order_id?: int|null, recorded_at?: string|null}  $data
     */
    public function handle(Master $master, array $data): MasterLocation
    {
        if (isset($data['order_id'])) {
            $this->orders->findForMasterOrFail((int) $data['order_id'], $master);
        }

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
