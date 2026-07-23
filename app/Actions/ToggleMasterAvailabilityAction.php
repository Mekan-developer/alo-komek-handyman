<?php

namespace App\Actions;

use App\Models\Master;

class ToggleMasterAvailabilityAction
{
    public function handle(Master $master, bool $isAvailable): Master
    {
        $master->update(['is_available' => $isAvailable]);

        return $master;
    }
}
