<?php

namespace Database\Factories;

use App\Models\Master;
use App\Models\MasterPayout;
use App\Models\User;
use App\PaymentModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MasterPayout>
 */
class MasterPayoutFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'master_id' => Master::factory(),
            'master_name' => fake()->name(),
            'amount' => fake()->randomFloat(2, 100, 3000),
            'payment_model' => PaymentModel::Percentage,
            'paid_by' => User::factory(),
            'note' => null,
        ];
    }
}
