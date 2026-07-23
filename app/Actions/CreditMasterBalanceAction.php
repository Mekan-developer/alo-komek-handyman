<?php

namespace App\Actions;

use App\Models\Order;
use App\PaymentModel;
use App\Repositories\MasterRepository;

class CreditMasterBalanceAction
{
    public function __construct(private readonly MasterRepository $repository) {}

    public function handle(Order $order): void
    {
        $master = $order->master;

        if ($master === null) {
            return;
        }

        $amount = $this->calculateEarning($order);

        if ($amount > 0) {
            $this->repository->incrementBalance($master, $amount);
        }
    }

    private function calculateEarning(Order $order): float
    {
        $master = $order->master;

        return match ($master->payment_model) {
            PaymentModel::Percentage,
            PaymentModel::SalaryPercentage => $order->final_price !== null
                ? round((float) $order->final_price * (float) $master->payment_value / 100, 2)
                : 0.0,
            PaymentModel::FixedPerJob => (float) $master->payment_value,
            PaymentModel::Salary => 0.0,
        };
    }
}
