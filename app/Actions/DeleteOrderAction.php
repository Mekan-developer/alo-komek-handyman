<?php

namespace App\Actions;

use App\Models\Order;
use App\Repositories\OrderRepository;
use Illuminate\Support\Facades\Storage;

class DeleteOrderAction
{
    public function __construct(private readonly OrderRepository $repository) {}

    public function handle(Order $order): void
    {
        $order->loadMissing(['photos', 'tasks.photos']);

        foreach ($order->photos as $photo) {
            Storage::disk('public')->delete($photo->path);
        }

        foreach ($order->tasks as $task) {
            foreach ($task->photos as $photo) {
                Storage::disk('public')->delete($photo->path);
            }
        }

        $this->repository->delete($order);
    }
}
