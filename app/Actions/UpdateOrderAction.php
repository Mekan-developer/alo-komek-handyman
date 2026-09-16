<?php

namespace App\Actions;

use App\Exceptions\OrderException;
use App\Models\Order;
use App\OrderStatus;
use App\Repositories\OrderRepository;
use App\Repositories\SettingRepository;
use App\Support\OrderSchedule;

class UpdateOrderAction
{
    public function __construct(
        private readonly OrderRepository $repository,
        private readonly SettingRepository $settings,
    ) {}

    /** @param array<string, mixed> $data */
    public function handle(Order $order, array $data): Order
    {
        if ($order->status !== OrderStatus::Pending) {
            throw OrderException::notEditable();
        }

        if (array_key_exists('is_urgent', $data) || array_key_exists('preferred_date', $data) || array_key_exists('time_slot', $data)) {
            $data['is_urgent'] = (bool) ($data['is_urgent'] ?? $order->is_urgent);
            $data = OrderSchedule::applyUrgency($data, $this->settings);
        }

        return $this->repository->update($order, $data);
    }
}
