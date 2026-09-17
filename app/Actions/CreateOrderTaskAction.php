<?php

namespace App\Actions;

use App\Events\OrderTaskCreated;
use App\Exceptions\OrderException;
use App\Models\Master;
use App\Models\Order;
use App\Models\OrderTask;
use App\OrderStatus;
use App\Repositories\OrderRepository;

class CreateOrderTaskAction
{
    public function __construct(private readonly OrderRepository $repository) {}

    /**
     * Мастер создаёт задачу только на своей заявке в статусе InProgress.
     *
     * @param  array{title: string, description?: string|null}  $data
     */
    public function handle(Master $master, Order $order, array $data): OrderTask
    {
        if ($order->master_id !== $master->id) {
            throw OrderException::taskNotOwned();
        }

        if ($order->status !== OrderStatus::InProgress) {
            throw OrderException::taskNotEditable();
        }

        return $this->create($order, $data);
    }

    /**
     * Админ/менеджер добавляет задачу, пока заявка не финальна и мастер назначен.
     *
     * @param  array{title: string, description?: string|null, price?: float|null}  $data
     */
    public function handleForStaff(Order $order, array $data): OrderTask
    {
        if ($order->status->isFinal()) {
            throw OrderException::alreadyFinal();
        }

        if ($order->master_id === null) {
            throw OrderException::masterNotAssigned();
        }

        $price = $data['price'] ?? null;
        unset($data['price']);

        $task = $this->create($order, $data);

        if ($price !== null) {
            $task = $this->repository->updateTask($task, [
                'price' => number_format($price, 2, '.', ''),
            ]);
        }

        return $task;
    }

    /**
     * @param  array{title: string, description?: string|null}  $data
     */
    private function create(Order $order, array $data): OrderTask
    {
        $task = $this->repository->createTask($order, [
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
        ]);

        OrderTaskCreated::dispatch($task);

        return $task;
    }
}
