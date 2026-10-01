<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
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

    /**
     * PNG of the given size. With $noise every pixel is random, so it barely
     * compresses — used to push the WebP over the 50 KB budget.
     */
    private function fakePng(int $width, int $height, bool $noise = false, string $name = 'photo.png'): UploadedFile
    {
        $image = imagecreatetruecolor($width, $height);
        imagefill($image, 0, 0, imagecolorallocate($image, 40, 120, 200));

        if ($noise) {
            for ($x = 0; $x < $width; $x++) {
                for ($y = 0; $y < $height; $y++) {
                    imagesetpixel($image, $x, $y, mt_rand(0, 0xFFFFFF));
                }
            }
        }

        ob_start();
        imagepng($image);
        $content = (string) ob_get_clean();
        imagedestroy($image);

        return UploadedFile::fake()->createWithContent($name, $content);
    }

    /** @return array{0: int, 1: int} */
    private function storedImageSize(string $path): array
    {
        [$width, $height] = getimagesize(Storage::disk('public')->path($path));

        return [$width, $height];
    }

    /** @param  array<string, mixed>  $overrides */
    private function storeCategoryWithImage(UploadedFile $file, array $overrides = []): Category
    {
        $this->post(route('categories.store'), array_merge([
            'name_ru' => 'Клининг',
            'name_tk' => 'Arassaçylyk',
            'is_active' => true,
            'parent_id' => null,
            'icon_type' => 'image',
            'icon_file' => $file,
        ], $overrides))->assertRedirect(route('categories.index'));

        return Category::where('name_ru', $overrides['name_ru'] ?? 'Клининг')->firstOrFail();
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

    public function test_deleting_a_category_with_orders_is_blocked_with_a_readable_message(): void
    {
        $this->actingAsAdmin();
        $category = Category::factory()->create();
        Order::factory()->count(2)->create(['category_id' => $category->id]);

        $this->delete(route('categories.destroy', $category))
            ->assertRedirect(route('categories.index'))
            ->assertSessionHas('notification', fn (array $notification) => $notification['type'] === 'error'
                && $notification['message'] === __('categories.errors.has_orders', ['count' => 2]));

        $this->assertModelExists($category);
    }

    public function test_category_index_exposes_orders_count(): void
    {
        $this->actingAsAdmin();
        $category = Category::factory()->create();
        Order::factory()->create(['category_id' => $category->id]);

        $this->get(route('categories.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('categories.data.0.orders_count', 1));
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

    // ── Image icons ───────────────────────────────────────────────────────────

    public function test_can_create_category_with_image_icon_converted_to_webp(): void
    {
        Storage::fake('public');
        $this->actingAsAdmin();

        $category = $this->storeCategoryWithImage($this->fakePng(1200, 600));

        $this->assertSame('image', $category->icon_type->value);
        $this->assertMatchesRegularExpression('#^category-images/.+\.webp$#', $category->icon);
        Storage::disk('public')->assertExists($category->icon);
        $this->assertCount(1, Storage::disk('public')->files('category-images'), 'Original upload must be removed');
        $this->assertSame(asset("storage/{$category->icon}"), $category->icon_url);
        $this->assertSame([400, 200], $this->storedImageSize($category->icon));
    }

    public function test_image_icon_narrower_than_limit_keeps_its_size(): void
    {
        Storage::fake('public');
        $this->actingAsAdmin();

        $category = $this->storeCategoryWithImage($this->fakePng(120, 80, name: 'small.png'));

        $this->assertSame([120, 80], $this->storedImageSize($category->icon));
    }

    public function test_tall_image_icon_is_shrunk_to_500px_height(): void
    {
        Storage::fake('public');
        $this->actingAsAdmin();

        // 400 wide, 800 tall → height >= 700, so it becomes 500 tall (width proportional).
        $category = $this->storeCategoryWithImage($this->fakePng(400, 800));

        $this->assertSame([250, 500], $this->storedImageSize($category->icon));
    }

    public function test_wide_and_tall_image_icon_is_capped_by_width_then_height(): void
    {
        Storage::fake('public');
        $this->actingAsAdmin();

        // 800×2000 → 400×1000 by width → still >= 700 tall → 200×500.
        $category = $this->storeCategoryWithImage($this->fakePng(800, 2000));

        $this->assertSame([200, 500], $this->storedImageSize($category->icon));
    }

    public function test_image_icon_is_compressed_to_at_most_50kb(): void
    {
        Storage::fake('public');
        $this->actingAsAdmin();

        $category = $this->storeCategoryWithImage($this->fakePng(400, 400, noise: true));

        $this->assertLessThanOrEqual(50 * 1024, Storage::disk('public')->size($category->icon));
        [$width] = $this->storedImageSize($category->icon);
        $this->assertLessThanOrEqual(400, $width);
    }

    public function test_image_icon_requires_a_file(): void
    {
        $this->actingAsAdmin();

        $this->post(route('categories.store'), [
            'name_ru' => 'Тест',
            'name_tk' => 'Test',
            'is_active' => true,
            'parent_id' => null,
            'icon_type' => 'image',
        ])->assertSessionHasErrors('icon_file');
    }

    public function test_image_icon_rejects_non_image_files(): void
    {
        Storage::fake('public');
        $this->actingAsAdmin();

        foreach ([$this->fakeSvg(), UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf')] as $file) {
            $this->post(route('categories.store'), [
                'name_ru' => 'Тест',
                'name_tk' => 'Test',
                'is_active' => true,
                'parent_id' => null,
                'icon_type' => 'image',
                'icon_file' => $file,
            ])->assertSessionHasErrors('icon_file');
        }

        $this->assertDatabaseMissing('categories', ['name_ru' => 'Тест']);
    }

    public function test_svg_tab_still_rejects_raster_images(): void
    {
        Storage::fake('service_icons');
        $this->actingAsAdmin();

        $this->post(route('categories.store'), [
            'name_ru' => 'Тест',
            'name_tk' => 'Test',
            'is_active' => true,
            'parent_id' => null,
            'icon_type' => 'custom',
            'icon_file' => $this->fakePng(50, 50),
        ])->assertSessionHasErrors('icon_file');
    }

    public function test_updating_without_new_image_keeps_existing_image(): void
    {
        Storage::fake('public');
        $this->actingAsAdmin();
        $category = $this->storeCategoryWithImage($this->fakePng(300, 300));
        $path = $category->icon;

        $this->put(route('categories.update', $category), [
            'name_ru' => 'Клининг 2',
            'name_tk' => 'Arassaçylyk 2',
            'is_active' => true,
            'parent_id' => null,
            'icon_type' => 'image',
            'icon' => null,
        ])->assertRedirect(route('categories.index'));

        $category->refresh();
        $this->assertSame('image', $category->icon_type->value);
        $this->assertSame($path, $category->icon);
        Storage::disk('public')->assertExists($path);
    }

    public function test_replacing_image_icon_deletes_the_old_file(): void
    {
        Storage::fake('public');
        $this->actingAsAdmin();
        $category = $this->storeCategoryWithImage($this->fakePng(300, 300));
        $oldPath = $category->icon;

        $this->put(route('categories.update', $category), [
            'name_ru' => $category->name_ru,
            'name_tk' => $category->name_tk,
            'is_active' => true,
            'parent_id' => null,
            'icon_type' => 'image',
            'icon_file' => $this->fakePng(200, 200, name: 'new.png'),
        ])->assertRedirect(route('categories.index'));

        $category->refresh();
        $this->assertNotSame($oldPath, $category->icon);
        Storage::disk('public')->assertExists($category->icon);
        Storage::disk('public')->assertMissing($oldPath);
    }

    public function test_switching_from_image_to_preset_deletes_the_image(): void
    {
        Storage::fake('public');
        $this->actingAsAdmin();
        $category = $this->storeCategoryWithImage($this->fakePng(300, 300));
        $path = $category->icon;

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
        Storage::disk('public')->assertMissing($path);
    }

    public function test_deleting_category_purges_its_image_icon(): void
    {
        Storage::fake('public');
        $this->actingAsAdmin();
        $category = $this->storeCategoryWithImage($this->fakePng(300, 300));
        $path = $category->icon;

        $this->delete(route('categories.destroy', $category))
            ->assertRedirect(route('categories.index'));

        Storage::disk('public')->assertMissing($path);
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
