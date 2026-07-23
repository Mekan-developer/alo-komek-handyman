<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderPhoto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderPhoto>
 */
class OrderPhotoFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'path' => 'orders/sample.webp',
            'original_name' => fake()->word().'.jpg',
            'status' => OrderPhoto::STATUS_DONE,
        ];
    }
}
