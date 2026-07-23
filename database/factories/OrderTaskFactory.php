<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderTask;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderTask>
 */
class OrderTaskFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'title' => fake()->sentence(3),
            'before_photo_path' => null,
            'after_photo_path' => null,
            'before_status' => OrderTask::STATUS_PENDING,
            'after_status' => OrderTask::STATUS_PENDING,
        ];
    }
}
