<?php

namespace App\Actions;

use App\Events\MasterAssigned;
use App\Exceptions\OrderException;
use App\Models\Order;
use App\Repositories\MasterRepository;
use App\Repositories\OrderRepository;

class AssignMasterAction
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly MasterRepository $masterRepository,
    ) {}

    public function handle(Order $order, int $masterId, ?string $changeReason = null): Order
    {
        if ($order->status->isFinal()) {
            throw OrderException::alreadyFinal();
        }

        $master = $this->masterRepository->findOrFail($masterId);

        if (! $master->is_active || ! $master->hasActiveAccess()) {
            throw OrderException::masterAccessExpired();
        }

        if (! $master->is_available) {
            throw OrderException::masterUnavailable();
        }

        if ($master->city_id !== $order->city_id) {
            throw OrderException::cityMismatch();
        }

        $masterCategoryIds = $master->categories()->pluck('categories.id')->all();
        if (! in_array($order->category_id, $masterCategoryIds, true)) {
            throw OrderException::categoryMismatch();
        }

        $isReassignment = $order->master_id !== null && $order->master_id !== $master->id;
        $assigned = $this->orderRepository->assignMaster($order, $master->id, $isReassignment ? $changeReason : null);

        MasterAssigned::dispatch($assigned->load('master'));

        return $assigned;
    }
}
