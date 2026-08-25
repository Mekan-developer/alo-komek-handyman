<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class UserRepository
{
    /**
     * Every staff account — the audience for order notifications.
     *
     * @return Collection<int, User>
     */
    public function all(): Collection
    {
        return User::all();
    }

    public function paginate(int $perPage = 20, array $filters = []): LengthAwarePaginator
    {
        return User::query()
            ->when(isset($filters['role']), fn ($q) => $q->where('role', $filters['role']))
            ->latest()
            ->paginate($perPage);
    }

    public function findOrFail(int $id): User
    {
        return User::findOrFail($id);
    }
}
