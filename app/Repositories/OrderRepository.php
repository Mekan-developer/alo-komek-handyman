<?php

namespace App\Repositories;

use App\Models\Client;
use App\Models\Master;
use App\Models\Order;
use App\OrderStatus;
use Illuminate\Pagination\LengthAwarePaginator;

class OrderRepository
{
    /** @param array<string, mixed> $filters */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Order::with(['city', 'category', 'master'])
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['city_id'] ?? null, fn ($q, $cityId) => $q->where('city_id', $cityId))
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
        return Order::with(['category', 'city', 'master.latestLocation', 'review'])
            ->where('client_id', $client->id)
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();
    }

    public function findForClientOrFail(int $orderId, Client $client): Order
    {
        return Order::with(['category', 'city', 'master.latestLocation', 'photos', 'tasks', 'review'])
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
            'city',
            'category',
            'master.latestLocation',
            'photos',
            'tasks.beforePhotos',
            'tasks.afterPhotos',
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
