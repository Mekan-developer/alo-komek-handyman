<?php

namespace App\Actions;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Hash;

class CreateUserAction
{
    /**
     * @param  array<string, mixed>  $data
     *
     * @throws AuthorizationException
     */
    public function handle(User $creator, array $data): User
    {
        $targetRole = UserRole::from($data['role']);

        if (! $creator->role->canManage($targetRole)) {
            throw new AuthorizationException('Недостаточно прав для назначения этой роли.');
        }

        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $targetRole,
            'created_by' => $creator->id,
        ]);
    }
}
