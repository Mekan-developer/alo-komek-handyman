<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class DeleteUserAction
{
    /**
     * @throws AuthorizationException
     */
    public function handle(User $executor, User $target): void
    {
        if ($executor->id === $target->id) {
            throw new AuthorizationException(__('users.cant_delete_self'));
        }

        $target->delete();
    }
}
