<?php

namespace App\Actions;

use App\Events\OrderTaskUpdated;
use App\Exceptions\OrderException;
use App\Models\Master;
use App\Models\OrderTask;
use App\OrderStatus;

class UpdateOrderTaskAction
{
    /**
     * Edits the title/description a master typed on a task.
     *
     * Only the fields present in `$data` are touched, so the mobile app can PATCH
     * a single field. Price stays out of reach — it is the admin's to set via
     * {@see SetOrderTaskPriceAction}.
     *
     * @param  array{title?: string, description?: string|null}  $data
     */
    public function handle(Master $master, OrderTask $task, array $data): OrderTask
    {
        $order = $task->order;

        if ($order->master_id !== $master->id) {
            throw OrderException::taskNotOwned();
        }

        if ($order->status !== OrderStatus::InProgress) {
            throw OrderException::taskNotEditable();
        }

        foreach (['title', 'description'] as $field) {
            if (array_key_exists($field, $data)) {
                $task->{$field} = $data[$field];
            }
        }

        $task->save();

        if ($task->wasChanged()) {
            OrderTaskUpdated::dispatch($order, $task);
        }

        return $task;
    }
}
