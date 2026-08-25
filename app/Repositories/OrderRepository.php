<?php

namespace App\Repositories;

use App\Models\Client;
use App\Models\Master;
use App\Models\Order;
use App\Models\OrderTask;
use App\OrderStatus;
use Illuminate\Pagination\LengthAwarePaginator;

class OrderRepository
{
    /** @param array<string, mixed> $filters */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Order::with(['category', 'master'])
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['master_id'] ?? null, fn ($q, $masterId) => $q->where('master_id', $masterId))
            ->when($filters['search'] ?? null, fn ($q, $search) => $q->where(
                fn ($sub) => $sub->where('client_name', 'like', "%{$search}%")
                    ->orWhere('client_phone', 'like', "%{$search}%")
            ))
            ->when($filters['date_from'] ?? null, fn ($q, $from) => $q->whereDate('created_at', '>=', $from))
            ->when($filters['date_to'] ?? null, fn ($q, $to) => $q->whereDate('created_at', '<=', $to))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function forClient(Client $client, ?string $status = null): LengthAwarePaginator
    {
        return Order::with(['category', 'master.latestLocation', 'review'])
            ->where('client_id', $client->id)
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();
    }

    public function findForClientOrFail(int $orderId, Client $client): Order
    {
        return Order::with([
            'category',
            'master.latestLocation',
            'photos',
            'tasks.beforePhotos',
            'tasks.afterPhotos',
            'review',
        ])
            ->where('client_id', $client->id)
            ->findOrFail($orderId);
    }

    public function forMaster(Master $master, ?string $filter = null): LengthAwarePaginator
    {
        return Order::with(['category'])
            ->where('master_id', $master->id)
            ->when($filter === 'active', fn ($q) => $q->whereIn('status', [OrderStatus::Assigned->value, OrderStatus::InProgress->value]))
            ->when($filter === 'history', fn ($q) => $q->whereIn('status', [OrderStatus::Completed->value, OrderStatus::Cancelled->value]))
            ->latest()
            ->paginate(15)
            ->withQueryString();
    }

    public function findForMasterOrFail(int $orderId, Master $master): Order
    {
        return Order::with(['category', 'photos', 'tasks.beforePhotos', 'tasks.afterPhotos'])
            ->where('master_id', $master->id)
            ->findOrFail($orderId);
    }

    public function findOrFail(int $id): Order
    {
        return Order::with([
            'category',
            'master.latestLocation',
            'photos',
            'tasks.beforePhotos',
            'tasks.afterPhotos',
            'receipt.items',
        ])->findOrFail($id);
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): Order
    {
        return Order::create($data);
    }

    /** @param array<string, mixed> $data */
    public function update(Order $order, array $data): Order
    {
        $order->update($data);

        return $order->fresh();
    }

    public function delete(Order $order): void
    {
        $order->delete();
    }

    public function assignMaster(Order $order, int $masterId, ?string $changeReason = null): Order
    {
        $order->update([
            'master_id' => $masterId,
            'status' => OrderStatus::Assigned,
            'assigned_at' => now(),
            'master_change_reason' => $changeReason,
        ]);

        return $order->fresh();
    }

    /**
     * Recalculate the order total from its task prices, minus the order discount.
     *
     * Tasks without a price are ignored; when no task is priced the order total
     * is reset to null so the "completed without price" guards keep working.
     */
    public function syncFinalPriceFromTasks(Order $order): Order
    {
        $subtotal = $this->tasksSubtotal($order);

        // Decimal casts go through brick/math, which deprecates float input — pass strings.
        $order->update([
            'final_price' => $subtotal === null
                ? null
                : number_format($subtotal - $order->discountAmountFor($subtotal), 2, '.', ''),
        ]);

        return $order->fresh();
    }

    /**
     * Sum of the priced tasks straight from the database, before the discount.
     */
    public function tasksSubtotal(Order $order): ?float
    {
        $prices = OrderTask::where('order_id', $order->id)
            ->whereNotNull('price')
            ->pluck('price');

        return $prices->isEmpty() ? null : round((float) $prices->sum(), 2);
    }

    public function findTaskForOrderOrFail(int $orderId, int $taskId): OrderTask
    {
        return OrderTask::where('order_id', $orderId)->findOrFail($taskId);
    }

    /** @param array<string, mixed> $data */
    public function updateTask(OrderTask $task, array $data): OrderTask
    {
        $task->update($data);

        return $task->fresh();
    }

    public function changeStatus(Order $order, OrderStatus $status): Order
    {
        $payload = ['status' => $status];

        match ($status) {
            OrderStatus::InProgress => $payload['started_at'] = now(),
            OrderStatus::Completed => $payload['completed_at'] = now(),
            OrderStatus::Cancelled => $payload['cancelled_at'] = now(),
            default => null,
        };

        $order->update($payload);

        return $order->fresh();
    }
}
