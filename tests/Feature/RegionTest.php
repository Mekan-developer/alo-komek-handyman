<?php

namespace Tests\Feature;

use App\Models\Oblast;
use App\Models\Region;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class RegionTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function actingAsAdmin(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        return $user;
    }

    // ── Index ─────────────────────────────────────────────────────────────────

    public function test_regions_index_requires_authentication(): void
    {
        $this->get(route('regions.index'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_regions_index(): void
    {
        $this->actingAsAdmin();
        Region::factory()->count(3)->create();

        $this->get(route('regions.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Regions/Index')->has('regions'));
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function test_admin_can_create_region(): void
    {
        $this->actingAsAdmin();
        $oblast = Oblast::factory()->create();

        $this->post(route('regions.store'), [
            'name' => 'Baharly',
            'oblast_id' => $oblast->id,
            'is_active' => true,
        ])->assertRedirect(route('regions.index'));

        $this->assertDatabaseHas('regions', [
            'name' => 'Baharly',
            'oblast_id' => $oblast->id,
            'is_active' => true,
        ]);
    }

    public function test_store_fails_when_name_is_missing(): void
    {
        $this->actingAsAdmin();
        $oblast = Oblast::factory()->create();

        $this->post(route('regions.store'), [
            'name' => '',
            'oblast_id' => $oblast->id,
            'is_active' => true,
        ])->assertSessionHasErrors('name');
    }

    public function test_store_fails_when_oblast_id_is_missing(): void
    {
        $this->actingAsAdmin();

        $this->post(route('regions.store'), [
            'name' => 'Kaka',
            'is_active' => true,
        ])->assertSessionHasErrors('oblast_id');
    }

    public function test_store_fails_when_oblast_does_not_exist(): void
    {
        $this->actingAsAdmin();

        $this->post(route('regions.store'), [
            'name' => 'Kaka',
            'oblast_id' => 999,
            'is_active' => true,
        ])->assertSessionHasErrors('oblast_id');
    }

    public function test_store_fails_when_is_active_is_missing(): void
    {
        $this->actingAsAdmin();
        $oblast = Oblast::factory()->create();

        $this->post(route('regions.store'), [
            'name' => 'Kaka',
            'oblast_id' => $oblast->id,
        ])->assertSessionHasErrors('is_active');
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function test_admin_can_update_region(): void
    {
        $this->actingAsAdmin();
        $region = Region::factory()->create(['name' => 'Old Name']);
        $newOblast = Oblast::factory()->create();

        $this->put(route('regions.update', $region), [
            'name' => 'New Name',
            'oblast_id' => $newOblast->id,
            'is_active' => false,
        ])->assertRedirect(route('regions.index'));

        $this->assertDatabaseHas('regions', [
            'id' => $region->id,
            'name' => 'New Name',
            'oblast_id' => $newOblast->id,
            'is_active' => false,
        ]);
    }

    public function test_update_fails_when_name_is_missing(): void
    {
        $this->actingAsAdmin();
        $region = Region::factory()->create();

        $this->put(route('regions.update', $region), [
            'name' => '',
            'oblast_id' => $region->oblast_id,
            'is_active' => true,
        ])->assertSessionHasErrors('name');
    }

    public function test_update_fails_on_nonexistent_region(): void
    {
        $this->actingAsAdmin();
        $oblast = Oblast::factory()->create();

        $this->put(route('regions.update', 999), [
            'name' => 'Test',
            'oblast_id' => $oblast->id,
            'is_active' => true,
        ])->assertNotFound();
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function test_admin_can_delete_region(): void
    {
        $this->actingAsAdmin();
        $region = Region::factory()->create();

        $this->delete(route('regions.destroy', $region))
            ->assertRedirect(route('regions.index'));

        $this->assertModelMissing($region);
    }

    public function test_deleting_nonexistent_region_returns_404(): void
    {
        $this->actingAsAdmin();

        $this->delete(route('regions.destroy', 999))->assertNotFound();
    }
}
