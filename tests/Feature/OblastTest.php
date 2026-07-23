<?php

namespace Tests\Feature;

use App\Models\Oblast;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class OblastTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function actingAsAdmin(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        return $user;
    }

    // ── Index ─────────────────────────────────────────────────────────────────

    public function test_oblasts_index_requires_authentication(): void
    {
        $this->get(route('oblasts.index'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_oblasts_index(): void
    {
        $this->actingAsAdmin();
        Oblast::factory()->count(3)->create();

        $this->get(route('oblasts.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Oblasts/Index')->has('oblasts'));
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function test_admin_can_create_oblast(): void
    {
        $this->actingAsAdmin();

        $this->post(route('oblasts.store'), ['name' => 'Ahal', 'is_active' => true])
            ->assertRedirect(route('oblasts.index'));

        $this->assertDatabaseHas('oblasts', ['name' => 'Ahal', 'is_active' => true]);
    }

    public function test_store_fails_when_name_is_missing(): void
    {
        $this->actingAsAdmin();

        $this->post(route('oblasts.store'), ['name' => '', 'is_active' => true])
            ->assertSessionHasErrors('name');
    }

    public function test_store_fails_when_name_is_duplicate(): void
    {
        $this->actingAsAdmin();
        Oblast::factory()->create(['name' => 'Mary']);

        $this->post(route('oblasts.store'), ['name' => 'Mary', 'is_active' => true])
            ->assertSessionHasErrors('name');
    }

    public function test_store_fails_when_is_active_is_missing(): void
    {
        $this->actingAsAdmin();

        $this->post(route('oblasts.store'), ['name' => 'Balkan'])
            ->assertSessionHasErrors('is_active');
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function test_admin_can_update_oblast(): void
    {
        $this->actingAsAdmin();
        $oblast = Oblast::factory()->create(['name' => 'Old Name']);

        $this->put(route('oblasts.update', $oblast), ['name' => 'New Name', 'is_active' => false])
            ->assertRedirect(route('oblasts.index'));

        $this->assertDatabaseHas('oblasts', ['id' => $oblast->id, 'name' => 'New Name', 'is_active' => false]);
    }

    public function test_update_allows_same_name_for_same_oblast(): void
    {
        $this->actingAsAdmin();
        $oblast = Oblast::factory()->create(['name' => 'Lebap']);

        $this->put(route('oblasts.update', $oblast), ['name' => 'Lebap', 'is_active' => true])
            ->assertRedirect(route('oblasts.index'));
    }

    public function test_update_fails_when_name_belongs_to_another_oblast(): void
    {
        $this->actingAsAdmin();
        Oblast::factory()->create(['name' => 'Daşoguz']);
        $oblast = Oblast::factory()->create(['name' => 'Ahal']);

        $this->put(route('oblasts.update', $oblast), ['name' => 'Daşoguz', 'is_active' => true])
            ->assertSessionHasErrors('name');
    }

    public function test_update_fails_on_nonexistent_oblast(): void
    {
        $this->actingAsAdmin();

        $this->put(route('oblasts.update', 999), ['name' => 'Test', 'is_active' => true])
            ->assertNotFound();
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function test_admin_can_delete_oblast(): void
    {
        $this->actingAsAdmin();
        $oblast = Oblast::factory()->create();

        $this->delete(route('oblasts.destroy', $oblast))
            ->assertRedirect(route('oblasts.index'));

        $this->assertModelMissing($oblast);
    }

    public function test_deleting_nonexistent_oblast_returns_404(): void
    {
        $this->actingAsAdmin();

        $this->delete(route('oblasts.destroy', 999))->assertNotFound();
    }
}
