<?php

namespace App\Actions;

use App\Models\Client;
use App\Repositories\ClientRepository;

class CreateClientAction
{
    public function __construct(private readonly ClientRepository $repository) {}

    /** @param array<string, mixed> $data */
    public function handle(array $data): Client
    {
        return $this->repository->create($data);
    }
}
