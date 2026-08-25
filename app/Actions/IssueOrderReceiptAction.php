<?php

namespace App\Actions;

use App\Models\Order;
use App\Models\OrderReceipt;
use App\Repositories\OrderReceiptRepository;

/**
 * Выдаёт чек по завершённому заказу.
 *
 * Вызывается автоматически из {@see UpdateOrderStatusAction} при переходе в Completed —
 * вручную звать не нужно. Цены задач и скидка после завершения уже не редактируются
 * (см. {@see SetOrderTaskPriceAction}), поэтому снапшот на этот момент финальный.
 */
class IssueOrderReceiptAction
{
    public function __construct(private readonly OrderReceiptRepository $repository) {}

    public function handle(Order $order): OrderReceipt
    {
        $existing = $this->repository->findForOrder($order);

        if ($existing !== null) {
            return $existing;
        }

        $order->loadMissing(['tasks', 'master', 'category']);

        $pricedTasks = $order->tasks->whereNotNull('price');
        $subtotal = round((float) $pricedTasks->sum('price'), 2);
        $discountAmount = $order->discountAmountFor($subtotal);
        $issuedAt = $order->completed_at ?? now();

        return $this->repository->create([
            'order_id' => $order->id,
            'client_name' => $order->client_name,
            'client_phone' => $order->client_phone,
            'master_name' => $order->master?->name,
            'master_phone' => $order->master?->phone,
            'category_name' => $order->category?->name,
            'subtotal' => $this->decimal($subtotal),
            'discount_percent' => $this->decimal((float) $order->discount_percent),
            'discount_amount' => $this->decimal($discountAmount),
            'total' => $this->decimal($subtotal - $discountAmount),
        ], $pricedTasks->map(fn ($task) => [
            'order_task_id' => $task->id,
            'title' => $task->title,
            'description' => $task->description,
            'price' => $this->decimal((float) $task->price),
        ])->values()->all(), $issuedAt);
    }

    /**
     * Decimal casts go through brick/math, which deprecates float input — pass strings.
     */
    private function decimal(float $value): string
    {
        return number_format($value, 2, '.', '');
    }
}
