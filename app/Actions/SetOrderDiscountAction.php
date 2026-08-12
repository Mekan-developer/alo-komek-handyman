<?php

namespace App\Actions;

use App\Exceptions\OrderException;
use App\Models\Order;
use App\Repositories\OrderRepository;

class SetOrderDiscountAction
{
    public function __construct(private readonly OrderRepository $repository) {}

    /**
     * Set the discount percentage applied to the order total.
     *
     * `final_price` is recalculated right away, so the discounted total is what
     * the master's balance is credited from at completion.
     */
    public function handle(Order $order, float $percent): Order
    {
        if ($order->status->isFinal()) {
            throw OrderException::alreadyFinal();
        }

        $updated = $this->repository->update($order, [
            'discount_percent' => number_format($percent, 2, '.', ''),
        ]);

        return $this->repository->syncFinalPriceFromTasks($updated);
    }
}
