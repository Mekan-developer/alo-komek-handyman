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
            'price' => null,
        ];
    }

    /** @return Factory<OrderTask> */
    public function priced(float $price): Factory
    {
        return $this->state(fn () => ['price' => $price]);
    }
}
