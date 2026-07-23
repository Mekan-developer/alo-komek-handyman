<?php

namespace App\Actions;

use App\Exceptions\OrderException;
use App\Models\Order;
use App\Models\OrderReview;
use App\OrderStatus;

class CreateOrderReviewAction
{
    /** @param array{rating: int, comment?: string|null} $data */
    public function handle(Order $order, array $data): OrderReview
    {
        if ($order->status !== OrderStatus::Completed) {
            throw OrderException::notCompletedYet();
        }

        if ($order->review()->exists()) {
            throw OrderException::alreadyReviewed();
        }

        return $order->review()->create([
            'master_id' => $order->master_id,
            'client_id' => $order->client_id,
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
        ]);
    }
}
