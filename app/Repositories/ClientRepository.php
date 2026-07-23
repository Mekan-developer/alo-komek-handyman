<?php

namespace App\Repositories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ClientRepository
{
    /**
     * @param  array{oblast_id?: int|string, city_id?: int|string}  $filters
     */
    public function paginate(int $perPage = 20, array $filters = []): LengthAwarePaginator
    {
        return Client::with('city')
            ->withCount('orders')
            ->when(
                ! empty($filters['city_id']),
                fn ($q) => $q->where('city_id', $filters['city_id'])
            )
            ->when(
                ! empty($filters['oblast_id']) && empty($filters['city_id']),
                fn ($q) => $q->whereHas('city', fn ($cq) => $cq->where('oblast_id', $filters['oblast_id']))
            )
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
            ->get(['id', 'name', 'phone', 'city_id']);
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
