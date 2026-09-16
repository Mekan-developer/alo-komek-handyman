<?php

namespace App\Actions;

use App\Models\Order;
use App\OrderStatus;

class CancelClientOrderAction
{
    public function __construct(private readonly UpdateOrderStatusAction $updateStatus) {}

    /**
     * Cancel a client's own order while it is still Pending, Assigned, or InProgress.
     * Cancel fee is applied only when work has already started (InProgress).
     */
    public function handle(Order $order, ?string $reason = null): Order
    {
        return $this->updateStatus->handle($order, OrderStatus::Cancelled, $reason);
    }
}
