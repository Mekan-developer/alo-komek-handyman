<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Master;
use App\Models\Order;
use App\Models\OrderReview;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderReview>
 */
class OrderReviewFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'master_id' => Master::factory(),
            'client_id' => Client::factory(),
            'rating' => fake()->numberBetween(1, 5),
            'comment' => fake()->optional(0.7)->sentence(10),
        ];
    }
}
