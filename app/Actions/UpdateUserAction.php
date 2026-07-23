<?php

namespace App\Actions;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Hash;

class UpdateUserAction
{
    /**
     * @param  array<string, mixed>  $data
     *
     * @throws AuthorizationException
     */
    public function handle(User $executor, User $target, array $data): User
    {
        $targetRole = UserRole::from($data['role']);

        if (! $executor->role->canManage($targetRole)) {
            throw new AuthorizationException('Недостаточно прав для назначения этой роли.');
        }

        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $targetRole,
        ];

        if (! empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }

        $target->update($payload);

        return $target->fresh();
    }
}
