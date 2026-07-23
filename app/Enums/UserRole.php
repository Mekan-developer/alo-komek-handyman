<?php

namespace App\Enums;

enum UserRole: string
{
    case Administrator = 'administrator';
    case Manager = 'manager';
    case Operator = 'operator';

    /**
     * Roles that can be assigned when creating a user through the admin panel.
     *
     * @return array<int, self>
     */
    public static function assignable(): array
    {
        return [
            self::Administrator,
            self::Manager,
        ];
    }

    public function label(): string
    {
        return match ($this) {
            self::Administrator => __('users.roles.administrator'),
            self::Manager => __('users.roles.manager'),
            self::Operator => __('users.roles.operator'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Administrator => 'indigo',
            self::Manager => 'blue',
            self::Operator => 'green',
        };
    }

    public function canManage(self $role): bool
    {
        return match ($this) {
            self::Administrator => true,
            self::Manager => $role !== self::Administrator,
            self::Operator => false,
        };
    }
}
