<?php

namespace Database\Factories;

use App\Models\Oblast;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Oblast> */
class OblastFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->city(),
            'is_active' => true,
        ];
    }
}
