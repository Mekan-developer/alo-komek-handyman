<?php

namespace App\Actions;

use App\Exceptions\OrderException;
use App\Models\Order;
use App\Repositories\OrderRepository;
use App\Repositories\SettingRepository;
use App\Support\OrderSchedule;

class UpdateOrderScheduleAction
{
    public function __construct(
        private readonly OrderRepository $repository,
        private readonly SettingRepository $settings,
    ) {}

    /**
     * @param  array{preferred_date?: string|null, time_slot?: string|null, is_urgent?: bool}  $data
     */
    public function handle(Order $order, array $data): Order
    {
        if ($order->status->isFinal()) {
            throw OrderException::scheduleNotEditable();
        }

        $data = OrderSchedule::applyUrgency($data, $this->settings);

        $updated = $this->repository->update($order, [
            'preferred_date' => $data['preferred_date'] ?? null,
            'time_slot' => $data['time_slot'] ?? null,
            'is_urgent' => $data['is_urgent'],
            'urgency_fee' => $data['urgency_fee'],
        ]);

        return $this->repository->syncFinalPriceFromTasks($updated);
    }
}
