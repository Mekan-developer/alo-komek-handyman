<?php

namespace App\Observers;

use App\Models\Master;

class MasterObserver
{
    public function updated(Master $master): void
    {
        if ($master->wasChanged('is_active') && ! $master->is_active) {
            $master->tokens()->delete();
        }
    }
}
