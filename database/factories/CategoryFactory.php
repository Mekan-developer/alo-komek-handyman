<?php

namespace Database\Factories;

use App\Enums\CategoryIconType;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'parent_id' => null,
            'name_ru' => $name,
            'name_tk' => $name.' (tk)',
            'is_active' => true,
        ];
    }

    public function child(Category $parent): static
    {
        return $this->state(['parent_id' => $parent->id]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function withPresetIcon(string $key = 'wrench'): static
    {
        return $this->state([
            'icon_type' => CategoryIconType::Preset->value,
            'icon' => $key,
        ]);
    }
}
