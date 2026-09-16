<?php

namespace App\Actions;

use App\Models\Client;

class ToggleClientBlockAction
{
    /**
     * Flip the client block flag and revoke API tokens when blocking.
     */
    public function handle(Client $client): Client
    {
        $blocking = ! $client->is_blocked;

        $client->update(['is_blocked' => $blocking]);

        if ($blocking) {
            $client->tokens()->delete();
        }

        return $client->fresh();
    }
}
