<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\CategoryContent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CategoryContent>
 */
class CategoryContentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(3);
        $description = fake()->paragraph();

        return [
            'category_id' => Category::factory(),
            'title_ru' => $title,
            'title_tk' => $title.' (tk)',
            'description_ru' => $description,
            'description_tk' => $description.' (tk)',
            'price' => fake()->randomElement(['от 50 TMT', 'от 100 TMT', null]),
        ];
    }
}
