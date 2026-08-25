<?php

namespace App\Repositories;

use App\Models\Order;
use App\Models\OrderReceipt;
use App\OrderStatus;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class OrderReceiptRepository
{
    public function findForOrder(Order $order): ?OrderReceipt
    {
        return OrderReceipt::with('items')
            ->where('order_id', $order->id)
            ->first();
    }

    public function findForOrderOrFail(Order $order): OrderReceipt
    {
        return OrderReceipt::with('items')
            ->where('order_id', $order->id)
            ->firstOrFail();
    }

    /**
     * Завершённые заказы, для которых чек ещё не выдан — заказы из времён до чеков.
     *
     * @return Collection<int, Order>
     */
    public function completedWithoutReceipt(): Collection
    {
        return Order::with(['tasks', 'master', 'category'])
            ->where('status', OrderStatus::Completed->value)
            ->whereDoesntHave('receipt')
            ->orderBy('completed_at')
            ->get();
    }

    /**
     * Номер считается счётчиком за день, поэтому две параллельные выдачи могут
     * претендовать на один и тот же. Уникальный индекс отсекает вторую — она берёт
     * следующий свободный номер. Коллизия по `order_id` означает, что чек уже выдан.
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<int, array<string, mixed>>  $items
     */
    public function create(array $attributes, array $items, Carbon $issuedAt): OrderReceipt
    {
        $attributes['issued_at'] = $issuedAt;

        for ($attempt = 1; ; $attempt++) {
            try {
                return DB::transaction(function () use ($attributes, $items, $issuedAt): OrderReceipt {
                    $attributes['number'] = $this->nextNumberFor($issuedAt);

                    $receipt = OrderReceipt::create($attributes);
                    $receipt->items()->createMany($items);

                    return $receipt->load('items');
                });
            } catch (UniqueConstraintViolationException $e) {
                $existing = OrderReceipt::with('items')->where('order_id', $attributes['order_id'])->first();

                if ($existing !== null) {
                    return $existing;
                }

                if ($attempt >= 3) {
                    throw $e;
                }
            }
        }
    }

    /**
     * Номер вида `824-0036`: месяц и день без разделителя, затем счётчик чеков за этот день.
     */
    public function nextNumberFor(Carbon $issuedAt): string
    {
        $prefix = $issuedAt->month.$issuedAt->format('d');

        $sequence = OrderReceipt::whereDate('issued_at', $issuedAt->toDateString())->count() + 1;

        return sprintf('%s-%04d', $prefix, $sequence);
    }
}
