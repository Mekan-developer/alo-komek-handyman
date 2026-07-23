<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\Master;
use App\PaymentModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Master>
 */
class MasterFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'city_id' => City::factory(),
            'name' => fake()->name(),
            'phone' => fake()->unique()->numerify('+99362#######'),
            'payment_model' => PaymentModel::Percentage,
            'payment_value' => fake()->randomFloat(2, 5, 50),
            'monthly_salary' => 0,
            'balance' => 0,
            'access_expires_at' => now()->addDays(30),
            'is_active' => true,
            'photo' => null,
        ];
    }

    public function salary(): static
    {
        return $this->state([
            'payment_model' => PaymentModel::Salary,
            'payment_value' => 0,
            'monthly_salary' => 1500,
        ]);
    }

    public function salaryPercentage(): static
    {
        return $this->state([
            'payment_model' => PaymentModel::SalaryPercentage,
            'payment_value' => 35,
            'monthly_salary' => 1500,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function unavailable(): static
    {
        return $this->state(['is_available' => false]);
    }

    public function expired(): static
    {
        return $this->state(['access_expires_at' => now()->subDay()]);
    }
}
