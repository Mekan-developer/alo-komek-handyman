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
}
