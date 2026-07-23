<?php

namespace Database\Factories;

use App\Models\Master;
use App\Models\MasterLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MasterLocation>
 */
class MasterLocationFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'master_id' => Master::factory(),
            'latitude' => fake()->latitude(37.8, 38.1),
            'longitude' => fake()->longitude(58.2, 58.6),
            'recorded_at' => now()->subMinutes(fake()->numberBetween(1, 480)),
        ];
    }
}
