<?php

namespace App;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Assigned = 'assigned';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Новая',
            self::Assigned => 'Назначен мастер',
            self::InProgress => 'В работе',
            self::Completed => 'Завершён',
            self::Cancelled => 'Отменён',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'yellow',
            self::Assigned => 'blue',
            self::InProgress => 'indigo',
            self::Completed => 'green',
            self::Cancelled => 'red',
        };
    }

    /** Final statuses — order cannot be transitioned away. */
    public function isFinal(): bool
    {
        return in_array($this, [self::Completed, self::Cancelled], true);
    }
}
