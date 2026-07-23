<?php

namespace App\Repositories;

use App\Models\City;
use App\Models\Master;
use App\Models\Order;
use App\OrderStatus;

class DashboardRepository
{
    /** @return array<string, int> */
    public function stats(): array
    {
        return [
            'total_orders' => Order::count(),
            'active_masters' => Master::where('is_active', true)->count(),
            'pending_orders' => Order::where('status', OrderStatus::Pending)->count(),
            'total_cities' => City::where('is_active', true)->count(),
            'completed_orders' => Order::where('status', OrderStatus::Completed)->count(),
            'in_progress_orders' => Order::where('status', OrderStatus::InProgress)->count(),
        ];
    }

    /**
     * @return array<int, array{status: string, color: string, count: int}>
     */
    public function ordersByStatus(): array
    {
        $counts = Order::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return array_map(
            fn (OrderStatus $s) => [
                'status' => $s->value,
                'color' => $s->color(),
                'count' => $counts[$s->value] ?? 0,
            ],
            OrderStatus::cases()
        );
    }

    /**
     * @return array<int, array{id: int, client_name: string, category: string, city: string, status: string, color: string, created_at: string}>
     */
    public function recentOrders(): array
    {
        return Order::with(['category', 'city'])
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn (Order $order) => [
                'id' => $order->id,
                'client_name' => $order->client_name,
                'category' => $order->category?->name ?? '—',
                'city' => $order->city?->name ?? '—',
                'status' => $order->status->value,
                'color' => $order->status->color(),
                'created_at' => $order->created_at->format('d.m.Y H:i'),
            ])
            ->toArray();
    }
}
