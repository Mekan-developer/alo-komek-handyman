<?php

namespace App\Actions;

use App\Exceptions\OrderException;
use App\Models\Order;
use App\OrderStatus;
use App\Repositories\OrderRepository;

class UpdateOrderAction
{
    public function __construct(private readonly OrderRepository $repository) {}

    /** @param array<string, mixed> $data */
    public function handle(Order $order, array $data): Order
    {
        if ($order->status !== OrderStatus::Pending) {
            throw OrderException::notEditable();
        }

        return $this->repository->update($order, $data);
    }
}
