<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderReceipt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderReceipt>
 */
class OrderReceiptFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 20, 500);

        return [
            'order_id' => Order::factory(),
            'number' => fake()->unique()->numerify('###-####'),
            'client_name' => fake()->name(),
            'client_phone' => fake()->numerify('6#######'),
            'master_name' => fake()->name(),
            'master_phone' => fake()->numerify('6#######'),
            'category_name' => fake()->word(),
            'subtotal' => $subtotal,
            'discount_percent' => 0,
            'discount_amount' => 0,
            'total' => $subtotal,
            'issued_at' => now(),
        ];
    }
}
