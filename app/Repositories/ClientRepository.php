<?php

namespace App\Repositories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ClientRepository
{
    public function paginate(int $perPage = 20): LengthAwarePaginator
    {
        return Client::withCount('orders')
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Active (non-blocked) clients for selection in forms.
     *
     * @return Collection<int, Client>
     */
    public function allForSelect(): Collection
    {
        return Client::where('is_blocked', false)
            ->orderBy('name')
            ->get(['id', 'name', 'phone']);
    }

    public function findOrFail(int $id): Client
    {
        return Client::findOrFail($id);
    }

    public function findByPhone(string $phone): ?Client
    {
        return Client::where('phone', $phone)->first();
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): Client
    {
        return Client::create($data);
    }

    /** @param array<string, mixed> $data */
    public function update(Client $client, array $data): Client
    {
        $client->update($data);

        return $client->fresh();
    }
}
