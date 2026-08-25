<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Notifications\NewOrderNotification;
use App\Repositories\UserRepository;

class NotifyAdminsOnNewOrder
{
    public function __construct(private readonly UserRepository $repository) {}

    public function handle(OrderCreated $event): void
    {
        $this->repository->all()->each->notify(new NewOrderNotification($event->order));
    }
}
