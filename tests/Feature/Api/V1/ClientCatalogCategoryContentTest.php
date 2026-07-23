<?php

namespace Tests\Feature\Api\V1;

use App\Models\Category;
use App\Models\CategoryContent;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ClientCatalogCategoryContentTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function activeChild(): Category
    {
        $parent = Category::factory()->create();

        return Category::factory()->child($parent)->create();
    }

    public function test_returns_content_for_active_category(): void
    {
        $category = $this->activeChild();
        $content = CategoryContent::factory()->create(['category_id' => $category->id]);

        $this->getJson(route('api.v1.client.categories.content', $category))
            ->assertOk()
            ->assertJsonStructure(['data' => ['id', 'title', 'description', 'price', 'image_url']])
            ->assertJsonPath('data.id', $content->id)
            ->assertJsonPath('data.title', $content->title);
    }

    public function test_returns_404_when_category_does_not_exist(): void
    {
        $this->getJson(route('api.v1.client.categories.content', 99999))
            ->assertNotFound()
            ->assertJsonPath('message', __('notifications.not_found'));
    }

    public function test_returns_404_when_category_is_inactive(): void
    {
        $parent = Category::factory()->create();
        $category = Category::factory()->child($parent)->inactive()->create();
        CategoryContent::factory()->create(['category_id' => $category->id]);

        $this->getJson(route('api.v1.client.categories.content', $category))
            ->assertNotFound()
            ->assertJsonPath('message', __('categories.content_not_found'));
    }

    public function test_returns_404_when_category_has_no_content(): void
    {
        $category = $this->activeChild();

        $this->getJson(route('api.v1.client.categories.content', $category))
            ->assertNotFound()
            ->assertJsonPath('message', __('categories.content_not_found'));
    }

    public function test_image_url_is_null_when_no_image_attached(): void
    {
        $category = $this->activeChild();
        CategoryContent::factory()->create(['category_id' => $category->id]);

        $this->getJson(route('api.v1.client.categories.content', $category))
            ->assertOk()
            ->assertJsonPath('data.image_url', null);
    }
}
