<?php

namespace App\Repositories;

use App\Models\Master;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class MasterRepository
{
    /** @param array{search?: string} $filters */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return Master::with(['categories'])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->when($filters['search'] ?? null, function ($q, $search) {
                $escaped = addcslashes($search, '%_\\');
                $q->where(fn ($sub) => $sub
                    ->where('name', 'like', "%{$escaped}%")
                    ->orWhere('phone', 'like', "%{$escaped}%")
                );
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    /** All active masters with latest location — for map view. */
    public function forMap(): Collection
    {
        return Master::with(['latestLocation'])
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('access_expires_at')
                    ->orWhere('access_expires_at', '>', now());
            })
            ->get();
    }

    public function findOrFail(int $id): Master
    {
        return Master::findOrFail($id);
    }

    public function create(array $data): Master
    {
        $categories = $data['category_ids'] ?? [];
        unset($data['category_ids']);

        $master = Master::create($data);
        $master->categories()->sync($categories);

        return $master;
    }

    public function update(Master $master, array $data): Master
    {
        $categories = $data['category_ids'] ?? [];
        unset($data['category_ids']);

        $master->update($data);
        $master->categories()->sync($categories);

        return $master;
    }

    public function delete(Master $master): void
    {
        $master->delete();
    }

    public function incrementBalance(Master $master, float $amount): void
    {
        $master->increment('balance', $amount);
    }

    public function decrementBalance(Master $master, float $amount): void
    {
        $master->decrement('balance', $amount);
    }

    public function resetBalance(Master $master): void
    {
        $master->update(['balance' => 0]);
    }

    /** Masters with a positive outstanding balance — awaiting payout. */
    public function withOutstandingBalance(): Collection
    {
        return Master::where('balance', '>', 0)
            ->orderByDesc('balance')
            ->get();
    }

    /** Sum of all outstanding (not yet paid out) master balances. */
    public function totalOutstandingBalance(): float
    {
        return (float) Master::where('balance', '>', 0)->sum('balance');
    }

    public function countWithOutstandingBalance(): int
    {
        return Master::where('balance', '>', 0)->count();
    }

    /** Active masters, optionally filtered by category — for order assignment dropdown. */
    public function eligibleForOrder(?int $categoryId = null): Collection
    {
        return Master::with(['categories', 'latestLocation'])
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('access_expires_at')
                    ->orWhere('access_expires_at', '>', now());
            })
            ->when($categoryId, fn ($q, $catId) => $q->whereHas(
                'categories',
                fn ($c) => $c->where('categories.id', $catId)
            ))
            ->get();
    }
}
