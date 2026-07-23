<?php

namespace Database\Factories;

use App\Models\Oblast;
use App\Models\Region;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Region> */
class RegionFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->city(),
            'oblast_id' => Oblast::factory(),
            'is_active' => true,
        ];
    }
}
