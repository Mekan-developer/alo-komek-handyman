<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Models\User;
use App\Notifications\NewOrderNotification;

class NotifyAdminsOnNewOrder
{
    public function handle(OrderCreated $event): void
    {
        User::all()->each->notify(new NewOrderNotification($event->order));
    }
}
