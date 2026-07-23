<?php

namespace Tests\Feature\Api\V1;

use App\Models\Category;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ClientCatalogSearchTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_finds_active_categories_by_name(): void
    {
        Category::factory()->create(['name_ru' => 'Сантехника']);
        Category::factory()->create(['name_ru' => 'Электрика']);

        $this->getJson(route('api.v1.client.categories.search', ['q' => 'тех']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Сантехника')
            ->assertJsonStructure(['data' => [['id', 'name', 'parent_id', 'icon_type', 'icon', 'icon_url']]]);
    }

    public function test_includes_parent_group_for_subcategory(): void
    {
        $parent = Category::factory()->create(['name_ru' => 'Сантехника']);
        $child = Category::factory()->child($parent)->create(['name_ru' => 'Замена труб']);

        $this->getJson(route('api.v1.client.categories.search', ['q' => 'труб']))
            ->assertOk()
            ->assertJsonPath('data.0.id', $child->id)
            ->assertJsonPath('data.0.parent.id', $parent->id)
            ->assertJsonPath('data.0.parent.name', 'Сантехника');
    }

    public function test_excludes_inactive_categories(): void
    {
        Category::factory()->inactive()->create(['name_ru' => 'Сантехника']);

        $this->getJson(route('api.v1.client.categories.search', ['q' => 'сан']))
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_validates_query_is_required(): void
    {
        $this->getJson(route('api.v1.client.categories.search'))
            ->assertStatus(422)
            ->assertJsonValidationErrors('q');
    }

    public function test_validates_query_minimum_length(): void
    {
        $this->getJson(route('api.v1.client.categories.search', ['q' => 'a']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('q');
    }
}
