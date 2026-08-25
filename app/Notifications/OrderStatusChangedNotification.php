<?php

namespace App\Notifications;

use App\Models\Order;
use App\OrderStatus;
use Illuminate\Notifications\Notification;

/**
 * Stored (bell panel) copy of an order status change.
 *
 * Deliberately NOT queued: the matching `order.status.changed` broadcast leaves
 * before listeners run, and the admin UI reloads its unread counter right after
 * receiving it. A queued write would land after that reload and the badge would
 * stay stale until the next navigation.
 */
class OrderStatusChangedNotification extends Notification
{
    public function __construct(
        public Order $order,
        public OrderStatus $from,
        public OrderStatus $to,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Raw status values only — labels are translated client-side via
     * `orders.statuses.*`, so a stored notification follows the reader's locale.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'order.status.changed',
            'order_id' => $this->order->id,
            'client_name' => $this->order->client_name,
            'from' => $this->from->value,
            'to' => $this->to->value,
        ];
    }
}
