<?php

namespace App\Actions;

use App\Exceptions\OrderException;
use App\Models\Order;
use App\Models\OrderTask;
use App\Observers\OrderTaskObserver;
use App\Repositories\OrderRepository;

class SetOrderTaskPriceAction
{
    public function __construct(private readonly OrderRepository $repository) {}

    /**
     * Set (or clear) the price of a single task.
     *
     * The order total is recalculated by {@see OrderTaskObserver}.
     */
    public function handle(Order $order, OrderTask $task, ?float $price): OrderTask
    {
        if ($order->status->isFinal()) {
            throw OrderException::alreadyFinal();
        }

        if ($order->master_id === null) {
            throw OrderException::masterNotAssigned();
        }

        return $this->repository->updateTask($task, ['price' => $price]);
    }
}
