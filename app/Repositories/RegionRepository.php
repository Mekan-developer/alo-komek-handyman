<?php

namespace App\Repositories;

use App\Models\Region;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class RegionRepository
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Region::with('oblast')->orderBy('name')->paginate($perPage);
    }

    /** @return Collection<int, Region> */
    public function activeWithOblast(): Collection
    {
        return Region::where('is_active', true)
            ->with(['oblast' => fn ($q) => $q->select('id', 'name')])
            ->orderBy('name')
            ->get(['id', 'name', 'oblast_id']);
    }

    public function findOrFail(int $id): Region
    {
        return Region::findOrFail($id);
    }

    public function create(array $data): Region
    {
        return Region::create($data);
    }

    public function update(Region $region, array $data): Region
    {
        $region->update($data);

        return $region;
    }

    public function delete(Region $region): void
    {
        $region->delete();
    }
}
