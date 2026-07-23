<?php

namespace App\Repositories;

use App\Models\Oblast;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class OblastRepository
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Oblast::orderBy('name')->paginate($perPage);
    }

    public function all(): Collection
    {
        return Oblast::where('is_active', true)->orderBy('name')->get();
    }

    public function allWithCities(): Collection
    {
        return Oblast::where('is_active', true)
            ->with(['cities' => fn ($q) => $q->where('is_active', true)->orderBy('name')])
            ->orderBy('name')
            ->get();
    }

    public function list(): Collection
    {
        return Oblast::orderBy('name')->get();
    }

    public function findOrFail(int $id): Oblast
    {
        return Oblast::findOrFail($id);
    }

    public function create(array $data): Oblast
    {
        return Oblast::create($data);
    }

    public function update(Oblast $oblast, array $data): Oblast
    {
        $oblast->update($data);

        return $oblast;
    }

    public function delete(Oblast $oblast): void
    {
        $oblast->delete();
    }
}
