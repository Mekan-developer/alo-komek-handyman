<?php

namespace Database\Factories;

use App\Models\OrderReceipt;
use App\Models\OrderReceiptItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderReceiptItem>
 */
class OrderReceiptItemFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'order_receipt_id' => OrderReceipt::factory(),
            'order_task_id' => null,
            'title' => fake()->sentence(3),
            'description' => null,
            'price' => fake()->randomFloat(2, 10, 200),
        ];
    }
}
