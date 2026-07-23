<?php

namespace App\Actions;

use App\Exceptions\OrderException;
use App\Models\Order;
use App\Repositories\OrderRepository;

class SetOrderFinalPriceAction
{
    public function __construct(private readonly OrderRepository $repository) {}

    public function handle(Order $order, float $finalPrice): Order
    {
        if ($order->status->isFinal()) {
            throw OrderException::alreadyFinal();
        }

        if ($order->master_id === null) {
            throw OrderException::masterNotAssigned();
        }

        return $this->repository->update($order, ['final_price' => $finalPrice]);
    }
}
