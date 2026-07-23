<?php

namespace App\Actions;

use App\Models\Client;

class DeleteClientAction
{
    public function handle(Client $client): void
    {
        $client->delete();
    }
}
