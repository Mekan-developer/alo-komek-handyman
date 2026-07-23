<?php

namespace App;

enum PaymentModel: string
{
    case Percentage = 'percentage';
    case FixedPerJob = 'fixed_per_job';
    case Salary = 'salary';
    case SalaryPercentage = 'salary_percentage';

    public function label(): string
    {
        return match ($this) {
            self::Percentage => 'Процент от заказа',
            self::FixedPerJob => 'Фиксированная сумма за работу',
            self::Salary => 'Оклад',
            self::SalaryPercentage => 'Оклад + Процент',
        };
    }

    /**
     * Whether the per-order earning depends on the order's final price.
     */
    public function requiresFinalPrice(): bool
    {
        return match ($this) {
            self::Percentage, self::SalaryPercentage => true,
            default => false,
        };
    }
}
