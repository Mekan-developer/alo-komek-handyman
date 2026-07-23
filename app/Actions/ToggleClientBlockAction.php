<?php

namespace App\Actions;

use App\Models\Client;

class ToggleClientBlockAction
{
    public function handle(Client $client): Client
    {
        $client->update(['is_blocked' => ! $client->is_blocked]);

        return $client->fresh();
    }
}
