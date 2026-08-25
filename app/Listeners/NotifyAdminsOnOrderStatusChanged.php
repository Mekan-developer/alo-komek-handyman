<?php

namespace App\Listeners;

use App\Events\OrderStatusChanged;
use App\Notifications\OrderStatusChangedNotification;
use App\Repositories\UserRepository;

class NotifyAdminsOnOrderStatusChanged
{
    public function __construct(private readonly UserRepository $repository) {}

    public function handle(OrderStatusChanged $event): void
    {
        $this->repository->all()->each->notify(
            new OrderStatusChangedNotification($event->order, $event->from, $event->to)
        );
    }
}
