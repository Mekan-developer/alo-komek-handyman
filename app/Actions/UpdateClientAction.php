<?php

namespace App\Actions;

use App\Models\Client;
use App\Repositories\ClientRepository;

class UpdateClientAction
{
    public function __construct(private readonly ClientRepository $repository) {}

    /** @param array<string, mixed> $data */
    public function handle(Client $client, array $data): Client
    {
        return $this->repository->update($client, $data);
    }
}
