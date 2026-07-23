<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CategoryContent;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CategoryContentTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function actingAsAdmin(): void
    {
        $this->actingAs(User::factory()->create());
    }

    private function subcategory(): Category
    {
        $parent = Category::factory()->create(['parent_id' => null]);

        return Category::factory()->create(['parent_id' => $parent->id]);
    }

    private function fakeImage(): UploadedFile
    {
        return UploadedFile::fake()->image('photo.jpg', 100, 100);
    }

    // ── Auth ──────────────────────────────────────────────────────────────────

    public function test_upsert_requires_authentication(): void
    {
        $category = $this->subcategory();

        $this->post(route('categories.content.upsert', $category))
            ->assertRedirect(route('login'));
    }

    // ── Validation ────────────────────────────────────────────────────────────

    public function test_root_category_cannot_have_content(): void
    {
        $this->actingAsAdmin();
        Storage::fake('public');

        $root = Category::factory()->create(['parent_id' => null]);

        $this->post(route('categories.content.upsert', $root), [
            'title_ru' => 'Test',
            'title_tk' => 'Test',
            'images' => [$this->fakeImage()],
        ])->assertStatus(422);
    }

    public function test_title_is_required(): void
    {
        $this->actingAsAdmin();
        Storage::fake('public');

        $category = $this->subcategory();

        $this->post(route('categories.content.upsert', $category), [
            'images' => [$this->fakeImage()],
        ])->assertSessionHasErrors(['title_ru', 'title_tk']);
    }

    public function test_at_least_one_image_is_required_on_create(): void
    {
        $this->actingAsAdmin();
        Storage::fake('public');

        $category = $this->subcategory();

        $this->post(route('categories.content.upsert', $category), [
            'title_ru' => 'Test',
            'title_tk' => 'Test',
        ])->assertSessionHasErrors('images');
    }

    // ── Create ────────────────────────────────────────────────────────────────

    public function test_user_can_create_content_for_subcategory(): void
    {
        $this->actingAsAdmin();
        Storage::fake('public');

        $category = $this->subcategory();

        $this->post(route('categories.content.upsert', $category), [
            'title_ru' => 'Описание услуги',
            'title_tk' => 'Hyzmatyň beýany',
            'description_ru' => 'Подробное описание',
            'description_tk' => 'Jikme-jik beýan',
            'price' => 'от 100 TMT',
            'images' => [$this->fakeImage(), $this->fakeImage()],
        ])->assertRedirect(route('categories.index'));

        $content = $category->fresh()->content;
        $this->assertNotNull($content);
        $this->assertSame('Описание услуги', $content->title_ru);
        $this->assertSame('Hyzmatyň beýany', $content->title_tk);
        $this->assertSame('Подробное описание', $content->description_ru);
        $this->assertSame('Jikme-jik beýan', $content->description_tk);
        $this->assertSame('от 100 TMT', $content->price);
        $this->assertCount(2, $content->images);
    }

    public function test_content_without_optional_fields(): void
    {
        $this->actingAsAdmin();
        Storage::fake('public');

        $category = $this->subcategory();

        $this->post(route('categories.content.upsert', $category), [
            'title_ru' => 'Минимальный',
            'title_tk' => 'Minimal',
            'images' => [$this->fakeImage()],
        ])->assertRedirect(route('categories.index'));

        $content = $category->fresh()->content;
        $this->assertNull($content->description_ru);
        $this->assertNull($content->description_tk);
        $this->assertNull($content->price);
        $this->assertCount(1, $content->images);
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function test_user_can_update_existing_content(): void
    {
        $this->actingAsAdmin();
        Storage::fake('public');

        $category = $this->subcategory();

        // Create initial
        $this->post(route('categories.content.upsert', $category), [
            'title_ru' => 'Старый заголовок',
            'title_tk' => 'Köne sözbaşy',
            'images' => [$this->fakeImage()],
        ]);

        $content = $category->fresh()->content;
        $keepId = $content->images->first()->id;

        // Update: keep existing image, add new, change title
        $this->post(route('categories.content.upsert', $category), [
            'title_ru' => 'Новый заголовок',
            'title_tk' => 'Täze sözbaşy',
            'keep_ids' => [$keepId],
            'images' => [$this->fakeImage()],
        ])->assertRedirect(route('categories.index'));

        $content->refresh();
        $this->assertSame('Новый заголовок', $content->title_ru);
        $this->assertCount(2, $content->images);
        $this->assertContains($keepId, $content->images->pluck('id')->all());
    }

    public function test_images_removed_when_not_in_keep_ids(): void
    {
        $this->actingAsAdmin();
        Storage::fake('public');

        $category = $this->subcategory();

        // Create with 2 images
        $this->post(route('categories.content.upsert', $category), [
            'title_ru' => 'Test',
            'title_tk' => 'Test',
            'images' => [$this->fakeImage(), $this->fakeImage()],
        ]);

        $content = $category->fresh()->content;
        $ids = $content->images->pluck('id')->all();

        // Update: keep only first image
        $this->post(route('categories.content.upsert', $category), [
            'title_ru' => 'Updated',
            'title_tk' => 'Updated',
            'keep_ids' => [$ids[0]],
        ])->assertRedirect(route('categories.index'));

        $content->refresh();
        $this->assertCount(1, $content->images);
        $this->assertSame($ids[0], $content->images->first()->id);
    }

    // ── Index with content ────────────────────────────────────────────────────

    public function test_index_includes_content_for_subcategories(): void
    {
        $this->actingAsAdmin();
        Storage::fake('public');

        $category = $this->subcategory();

        $this->post(route('categories.content.upsert', $category), [
            'title_ru' => 'Контент',
            'title_tk' => 'Mazmun',
            'images' => [$this->fakeImage()],
        ]);

        $this->get(route('categories.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Categories/Index')
                ->has('categories')
            );

        $this->assertNotNull(CategoryContent::where('category_id', $category->id)->first());
    }
}
