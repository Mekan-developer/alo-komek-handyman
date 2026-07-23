<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdministrator();
    }

    public function create(User $user): bool
    {
        return $user->isAdministrator();
    }

    public function update(User $user, User $target): bool
    {
        return $user->isAdministrator();
    }

    public function delete(User $user, User $target): bool
    {
        if (! $user->isAdministrator()) {
            return false;
        }

        if ($user->id === $target->id) {
            return false;
        }

        // Another administrator may only be removed by the administrator who created them.
        if ($target->isAdministrator()) {
            return $target->created_by === $user->id;
        }

        return true;
    }
}
