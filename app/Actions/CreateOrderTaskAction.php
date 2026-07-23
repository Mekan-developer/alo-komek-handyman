<?php

namespace App\Actions;

use App\Exceptions\OrderException;
use App\Models\Master;
use App\Models\Order;
use App\Models\OrderTask;
use App\OrderStatus;

class CreateOrderTaskAction
{
    /** @param array{title: string, description?: string|null} $data */
    public function handle(Master $master, Order $order, array $data): OrderTask
    {
        if ($order->master_id !== $master->id) {
            throw OrderException::invalidTransition($order->status->value, $order->status->value);
        }

        if ($order->status !== OrderStatus::InProgress) {
            throw new \DomainException('Tasks can only be created for orders that are in progress.');
        }

        return $order->tasks()->create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'before_status' => OrderTask::STATUS_PENDING,
            'after_status' => OrderTask::STATUS_PENDING,
        ]);
    }
}
