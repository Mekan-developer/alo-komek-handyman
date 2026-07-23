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
        foreach ($order->photos as $photo) {
            Storage::disk('public')->delete($photo->path);
        }

        foreach ($order->tasks as $task) {
            if ($task->before_photo_path) {
                Storage::disk('public')->delete($task->before_photo_path);
            }
            if ($task->after_photo_path) {
                Storage::disk('public')->delete($task->after_photo_path);
            }
        }

        $this->repository->delete($order);
    }
}
