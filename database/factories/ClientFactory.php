<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'city_id' => City::factory(),
            'name' => fake()->name(),
            'phone' => fake()->unique()->numerify('+99361#######'),
            'is_blocked' => false,
        ];
    }

    public function blocked(): static
    {
        return $this->state(['is_blocked' => true]);
    }
}
