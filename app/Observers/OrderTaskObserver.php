<?php

namespace App\Observers;

use App\Models\OrderTask;
use App\Repositories\OrderRepository;

/**
 * Keeps `orders.final_price` in sync with the sum of its task prices.
 */
class OrderTaskObserver
{
    public function __construct(private readonly OrderRepository $repository) {}

    public function saved(OrderTask $task): void
    {
        // `wasChanged()` stays empty after an insert, so creations are checked separately.
        $createdWithPrice = $task->wasRecentlyCreated && $task->price !== null;

        if ($createdWithPrice || $task->wasChanged('price')) {
            $this->syncOrderTotal($task);
        }
    }

    public function deleted(OrderTask $task): void
    {
        $this->syncOrderTotal($task);
    }

    private function syncOrderTotal(OrderTask $task): void
    {
        $order = $task->order;

        if ($order === null) {
            return;
        }

        $this->repository->syncFinalPriceFromTasks($order);
    }
}
