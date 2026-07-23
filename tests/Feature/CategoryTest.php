<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use App\Support\CategoryIcon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function actingAsAdmin(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        return $user;
    }

    private function fakeSvg(string $name = 'icon.svg'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M4 4h16v16H4z"/></svg>',
        );
    }

    // ── Index ─────────────────────────────────────────────────────────────────

    public function test_categories_index_requires_authentication(): void
    {
        $this->get(route('categories.index'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_categories_index(): void
    {
        $this->actingAsAdmin();
        Category::factory()->count(3)->create();

        $this->get(route('categories.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Categories/Index')
                ->has('categories')
                ->has('parentCategories')
            );
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function test_user_can_create_a_root_category(): void
    {
        $this->actingAsAdmin();

        $this->post(route('categories.store'), [
            'name_ru' => 'Электрика',
            'name_tk' => 'Elektrika',
            'is_active' => true,
            'parent_id' => null,
        ])->assertRedirect(route('categories.index'));

        $this->assertDatabaseHas('categories', [
            'name_ru' => 'Электрика',
            'name_tk' => 'Elektrika',
            'parent_id' => null,
            'is_active' => true,
        ]);
    }

    public function test_user_can_create_a_child_category(): void
    {
        $this->actingAsAdmin();
        $parent = Category::factory()->create(['name_ru' => 'Электрика']);

        $this->post(route('categories.store'), [
            'name_ru' => 'Розетки',
            'name_tk' => 'Rozetka',
            'is_active' => true,
            'parent_id' => $parent->id,
        ])->assertRedirect(route('categories.index'));

        $this->assertDatabaseHas('categories', [
            'name_ru' => 'Розетки',
            'parent_id' => $parent->id,
        ]);
    }

    public function test_store_fails_when_name_is_missing(): void
    {
        $this->actingAsAdmin();

        $this->post(route('categories.store'), [
            'name_ru' => '',
            'name_tk' => '',
            'is_active' => true,
            'parent_id' => null,
        ])->assertSessionHasErrors(['name_ru', 'name_tk']);
    }

    public function test_store_fails_when_parent_does_not_exist(): void
    {
        $this->actingAsAdmin();

        $this->post(route('categories.store'), [
            'name_ru' => 'Тест',
            'name_tk' => 'Test',
            'is_active' => true,
            'parent_id' => 9999,
        ])->assertSessionHasErrors('parent_id');
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function test_user_can_update_a_category(): void
    {
        $this->actingAsAdmin();
        $category = Category::factory()->create(['name_ru' => 'Старое']);

        $this->put(route('categories.update', $category), [
            'name_ru' => 'Новое',
            'name_tk' => 'Täze',
            'is_active' => false,
            'parent_id' => null,
        ])->assertRedirect(route('categories.index'));

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name_ru' => 'Новое',
            'name_tk' => 'Täze',
            'is_active' => false,
        ]);
    }

    public function test_user_can_assign_parent_on_update(): void
    {
        $this->actingAsAdmin();
        $parent = Category::factory()->create();
        $child = Category::factory()->create(['parent_id' => null]);

        $this->put(route('categories.update', $child), [
            'name_ru' => $child->name_ru,
            'name_tk' => $child->name_tk,
            'is_active' => true,
            'parent_id' => $parent->id,
        ])->assertRedirect(route('categories.index'));

        $this->assertDatabaseHas('categories', [
            'id' => $child->id,
            'parent_id' => $parent->id,
        ]);
    }

    public function test_update_fails_when_name_is_missing(): void
    {
        $this->actingAsAdmin();
        $category = Category::factory()->create();

        $this->put(route('categories.update', $category), [
            'name_ru' => '',
            'name_tk' => '',
            'is_active' => true,
            'parent_id' => null,
        ])->assertSessionHasErrors(['name_ru', 'name_tk']);
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function test_user_can_delete_a_category(): void
    {
        $this->actingAsAdmin();
        $category = Category::factory()->create();

        $this->delete(route('categories.destroy', $category))
            ->assertRedirect(route('categories.index'));

        $this->assertModelMissing($category);
    }

    public function test_deleting_parent_nullifies_children_parent_id(): void
    {
        $this->actingAsAdmin();
        $parent = Category::factory()->create();
        $child = Category::factory()->child($parent)->create();

        $this->delete(route('categories.destroy', $parent))
            ->assertRedirect(route('categories.index'));

        $this->assertModelMissing($parent);
        $this->assertDatabaseHas('categories', [
            'id' => $child->id,
            'parent_id' => null,
        ]);
    }

    public function test_deleting_nonexistent_category_returns_404(): void
    {
        $this->actingAsAdmin();

        $this->delete(route('categories.destroy', 9999))->assertNotFound();
    }

    // ── Icons ─────────────────────────────────────────────────────────────────

    public function test_every_preset_icon_has_a_matching_svg_file(): void
    {
        $keys = CategoryIcon::presetKeys();

        $this->assertNotEmpty($keys);

        foreach ($keys as $key) {
            $this->assertFileExists(
                public_path("icons/services/{$key}.svg"),
                "Missing SVG file for preset icon [{$key}]",
            );
        }
    }

    public function test_can_create_category_with_preset_icon(): void
    {
        $this->actingAsAdmin();

        $this->post(route('categories.store'), [
            'name_ru' => 'Электрика',
            'name_tk' => 'Elektrika',
            'is_active' => true,
            'parent_id' => null,
            'icon_type' => 'preset',
            'icon' => 'bolt',
        ])->assertRedirect(route('categories.index'));

        $this->assertDatabaseHas('categories', [
            'name_ru' => 'Электрика',
            'icon_type' => 'preset',
            'icon' => 'bolt',
        ]);
    }

    public function test_subcategory_can_have_preset_icon(): void
    {
        $this->actingAsAdmin();
        $parent = Category::factory()->create();

        $this->post(route('categories.store'), [
            'name_ru' => 'Розетки',
            'name_tk' => 'Rozetka',
            'is_active' => true,
            'parent_id' => $parent->id,
            'icon_type' => 'preset',
            'icon' => 'cpu-chip',
        ])->assertRedirect(route('categories.index'));

        $this->assertDatabaseHas('categories', [
            'name_ru' => 'Розетки',
            'parent_id' => $parent->id,
            'icon' => 'cpu-chip',
        ]);
    }

    public function test_store_rejects_preset_icon_outside_the_set(): void
    {
        $this->actingAsAdmin();

        $this->post(route('categories.store'), [
            'name_ru' => 'Тест',
            'name_tk' => 'Test',
            'is_active' => true,
            'parent_id' => null,
            'icon_type' => 'preset',
            'icon' => 'definitely-not-a-real-icon',
        ])->assertSessionHasErrors('icon');
    }

    public function test_can_create_category_with_uploaded_svg_icon(): void
    {
        Storage::fake('service_icons');
        $this->actingAsAdmin();

        $this->post(route('categories.store'), [
            'name_ru' => 'Сантехника',
            'name_tk' => 'Santehnika',
            'is_active' => true,
            'parent_id' => null,
            'icon_type' => 'custom',
            'icon_file' => $this->fakeSvg('plumber.svg'),
        ])->assertRedirect(route('categories.index'));

        $category = Category::where('name_ru', 'Сантехника')->firstOrFail();

        $this->assertSame('custom', $category->icon_type->value);
        $this->assertNotNull($category->icon);
        $this->assertMatchesRegularExpression('/^u-/', $category->icon);
        Storage::disk('service_icons')->assertExists("{$category->icon}.svg");
    }

    public function test_store_rejects_non_svg_icon_file(): void
    {
        Storage::fake('service_icons');
        $this->actingAsAdmin();

        $this->post(route('categories.store'), [
            'name_ru' => 'Тест',
            'name_tk' => 'Test',
            'is_active' => true,
            'parent_id' => null,
            'icon_type' => 'custom',
            'icon_file' => UploadedFile::fake()->create('photo.png', 10, 'image/png'),
        ])->assertSessionHasErrors('icon_file');
    }

    public function test_uploaded_svg_becomes_a_shared_asset_for_all_categories(): void
    {
        Storage::fake('service_icons');
        $this->actingAsAdmin();

        // Category A uploads an icon
        $this->post(route('categories.store'), [
            'name_ru' => 'Сантехника',
            'name_tk' => 'Santehnika',
            'is_active' => true,
            'parent_id' => null,
            'icon_type' => 'custom',
            'icon_file' => $this->fakeSvg('plumber.svg'),
        ]);

        $categoryA = Category::where('name_ru', 'Сантехника')->firstOrFail();
        $uploadedKey = $categoryA->icon;

        // The key appears in the uploaded icon pool
        $this->assertContains($uploadedKey, CategoryIcon::uploadedKeys());
    }

    public function test_updating_category_with_new_svg_keeps_old_uploaded_file(): void
    {
        Storage::fake('service_icons');
        $this->actingAsAdmin();

        // Seed an existing custom icon directly on the disk
        $oldKey = 'u-old-uuid';
        Storage::disk('service_icons')->put("{$oldKey}.svg", '<svg/>');
        $category = Category::factory()->create([
            'icon_type' => 'custom',
            'icon' => $oldKey,
        ]);

        $this->put(route('categories.update', $category), [
            'name_ru' => $category->name_ru,
            'name_tk' => $category->name_tk,
            'is_active' => true,
            'parent_id' => null,
            'icon_type' => 'custom',
            'icon_file' => $this->fakeSvg('new.svg'),
        ])->assertRedirect(route('categories.index'));

        $category->refresh();

        // New icon stored
        $this->assertNotSame($oldKey, $category->icon);
        Storage::disk('service_icons')->assertExists("{$category->icon}.svg");

        // Old file kept — it's a shared asset that other categories might reference
        Storage::disk('service_icons')->assertExists("{$oldKey}.svg");
    }

    public function test_keeping_custom_icon_on_update_without_reupload(): void
    {
        Storage::fake('service_icons');
        $this->actingAsAdmin();

        $key = 'u-existing-uuid';
        Storage::disk('service_icons')->put("{$key}.svg", '<svg/>');
        $category = Category::factory()->create([
            'icon_type' => 'custom',
            'icon' => $key,
        ]);

        // Mirrors the frontend: icon_type stays custom, no file, icon = null.
        $this->put(route('categories.update', $category), [
            'name_ru' => 'Обновлённое',
            'name_tk' => 'Täzelenen',
            'is_active' => true,
            'parent_id' => null,
            'icon_type' => 'custom',
            'icon' => null,
        ])->assertRedirect(route('categories.index'));

        $category->refresh();
        $this->assertSame('custom', $category->icon_type->value);
        $this->assertSame($key, $category->icon);
        Storage::disk('service_icons')->assertExists("{$key}.svg");
    }

    public function test_switching_from_custom_to_preset_keeps_uploaded_file(): void
    {
        Storage::fake('service_icons');
        $this->actingAsAdmin();

        $key = 'u-uploaded-uuid';
        Storage::disk('service_icons')->put("{$key}.svg", '<svg/>');
        $category = Category::factory()->create([
            'icon_type' => 'custom',
            'icon' => $key,
        ]);

        $this->put(route('categories.update', $category), [
            'name_ru' => $category->name_ru,
            'name_tk' => $category->name_tk,
            'is_active' => true,
            'parent_id' => null,
            'icon_type' => 'preset',
            'icon' => 'wrench',
        ])->assertRedirect(route('categories.index'));

        $category->refresh();
        $this->assertSame('preset', $category->icon_type->value);
        $this->assertSame('wrench', $category->icon);

        // Uploaded file is a shared asset — not deleted on switch
        Storage::disk('service_icons')->assertExists("{$key}.svg");
    }

    public function test_can_clear_category_icon(): void
    {
        $this->actingAsAdmin();
        $category = Category::factory()->withPresetIcon('home')->create();

        $this->put(route('categories.update', $category), [
            'name_ru' => $category->name_ru,
            'name_tk' => $category->name_tk,
            'is_active' => true,
            'parent_id' => null,
            'icon_type' => null,
        ])->assertRedirect(route('categories.index'));

        $category->refresh();
        $this->assertNull($category->icon_type);
        $this->assertNull($category->icon);
    }

    public function test_deleting_category_does_not_purge_uploaded_icon_file(): void
    {
        Storage::fake('service_icons');
        $this->actingAsAdmin();

        $key = 'u-shared-uuid';
        Storage::disk('service_icons')->put("{$key}.svg", '<svg/>');
        $category = Category::factory()->create([
            'icon_type' => 'custom',
            'icon' => $key,
        ]);

        $this->delete(route('categories.destroy', $category))
            ->assertRedirect(route('categories.index'));

        // File is a shared asset — kept after category deletion
        Storage::disk('service_icons')->assertExists("{$key}.svg");
    }

    public function test_deleting_category_purges_legacy_custom_icon(): void
    {
        Storage::fake('public');
        $this->actingAsAdmin();

        $legacyPath = $this->fakeSvg()->store('category-icons', 'public');
        $category = Category::factory()->create([
            'icon_type' => 'custom',
            'icon' => $legacyPath,
        ]);
        Storage::disk('public')->assertExists($legacyPath);

        $this->delete(route('categories.destroy', $category))
            ->assertRedirect(route('categories.index'));

        Storage::disk('public')->assertMissing($legacyPath);
    }
}
