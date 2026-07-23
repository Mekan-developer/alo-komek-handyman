<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BannerTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function actingAsAdmin(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        return $user;
    }

    // ── Index ─────────────────────────────────────────────────────────────────

    public function test_banners_index_requires_authentication(): void
    {
        $this->get(route('banners.index'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_banners_index(): void
    {
        $this->actingAsAdmin();
        Banner::factory()->count(2)->create();

        $this->get(route('banners.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Banners/Index')->has('banners'));
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function test_user_can_create_a_banner(): void
    {
        Storage::fake('public');
        $this->actingAsAdmin();

        $file = UploadedFile::fake()->image('banner.jpg', 1200, 400);

        $this->post(route('banners.store'), [
            'image' => $file,
            'url' => 'https://example.com',
            'is_active' => true,
            'sort_order' => 0,
        ])->assertRedirect(route('banners.index'));

        $this->assertDatabaseHas('banners', ['url' => 'https://example.com', 'is_active' => true]);
    }

    public function test_banner_store_requires_image(): void
    {
        $this->actingAsAdmin();

        $this->post(route('banners.store'), [
            'url' => 'https://example.com',
            'is_active' => true,
        ])->assertSessionHasErrors('image');
    }

    public function test_banner_store_accepts_null_url(): void
    {
        Storage::fake('public');
        $this->actingAsAdmin();

        $file = UploadedFile::fake()->image('banner.jpg', 1200, 400);

        $this->post(route('banners.store'), [
            'image' => $file,
            'url' => null,
            'is_active' => true,
        ])->assertRedirect(route('banners.index'));

        $this->assertDatabaseHas('banners', ['url' => null]);
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function test_user_can_update_a_banner_without_changing_image(): void
    {
        Storage::fake('public');
        $this->actingAsAdmin();
        $banner = Banner::factory()->create(['url' => 'https://old.com', 'is_active' => true]);

        $this->post(route('banners.update', $banner), [
            'url' => 'https://new.com',
            'is_active' => false,
        ])->assertRedirect(route('banners.index'));

        $this->assertDatabaseHas('banners', ['id' => $banner->id, 'url' => 'https://new.com', 'is_active' => false]);
    }

    public function test_user_can_update_a_banner_with_new_image(): void
    {
        Storage::fake('public');
        $this->actingAsAdmin();
        $banner = Banner::factory()->create(['image_path' => 'banners/old.jpg']);

        $newFile = UploadedFile::fake()->image('new.jpg', 1200, 400);

        $this->post(route('banners.update', $banner), [
            'image' => $newFile,
            'is_active' => true,
        ])->assertRedirect(route('banners.index'));

        $banner->refresh();
        $this->assertNotEquals('banners/old.jpg', $banner->image_path);
    }

    // ── Toggle ────────────────────────────────────────────────────────────────

    public function test_user_can_toggle_banner_status(): void
    {
        Storage::fake('public');
        $this->actingAsAdmin();
        $banner = Banner::factory()->create(['is_active' => true]);

        $this->post(route('banners.toggle', $banner))->assertRedirect(route('banners.index'));

        $this->assertDatabaseHas('banners', ['id' => $banner->id, 'is_active' => false]);
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function test_user_can_delete_a_banner(): void
    {
        Storage::fake('public');
        $this->actingAsAdmin();
        $banner = Banner::factory()->create();

        $this->delete(route('banners.destroy', $banner))->assertRedirect(route('banners.index'));

        $this->assertDatabaseMissing('banners', ['id' => $banner->id]);
    }
}
