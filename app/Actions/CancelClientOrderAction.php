<?php

namespace App\Actions;

use App\Exceptions\OrderException;
use App\Models\Order;
use App\OrderStatus;

class CancelClientOrderAction
{
    public function __construct(private readonly UpdateOrderStatusAction $updateStatus) {}

    /**
     * Cancel a client's own order — allowed only while no master is assigned.
     * Once a master is on the order, the client loses the ability to cancel.
     */
    public function handle(Order $order, ?string $reason = null): Order
    {
        if ($order->master_id !== null) {
            throw OrderException::cannotCancelAssignedOrder();
        }

        return $this->updateStatus->handle($order, OrderStatus::Cancelled, $reason);
    }
}
