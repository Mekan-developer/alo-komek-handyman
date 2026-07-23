<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class CityTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function actingAsAdmin(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        return $user;
    }

    // ── Index ─────────────────────────────────────────────────────────────────

    public function test_cities_index_requires_authentication(): void
    {
        $this->get(route('cities.index'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_cities_index(): void
    {
        $this->actingAsAdmin();
        City::factory()->count(3)->create();

        $this->get(route('cities.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Cities/Index')->has('cities'));
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function test_user_can_create_a_city(): void
    {
        $this->actingAsAdmin();

        $this->post(route('cities.store'), ['name' => 'Ашхабад', 'is_active' => true])
            ->assertRedirect(route('oblasts.index'));

        $this->assertDatabaseHas('cities', ['name' => 'Ашхабад', 'is_active' => true]);
    }

    public function test_store_fails_when_name_is_missing(): void
    {
        $this->actingAsAdmin();

        $this->post(route('cities.store'), ['name' => '', 'is_active' => true])
            ->assertSessionHasErrors('name');
    }

    public function test_store_fails_when_name_is_duplicate(): void
    {
        $this->actingAsAdmin();
        City::factory()->create(['name' => 'Мары']);

        $this->post(route('cities.store'), ['name' => 'Мары', 'is_active' => true])
            ->assertSessionHasErrors('name');
    }

    public function test_store_fails_when_is_active_is_missing(): void
    {
        $this->actingAsAdmin();

        $this->post(route('cities.store'), ['name' => 'Балканабат'])
            ->assertSessionHasErrors('is_active');
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function test_user_can_update_a_city(): void
    {
        $this->actingAsAdmin();
        $city = City::factory()->create(['name' => 'Старое название']);

        $this->put(route('cities.update', $city), ['name' => 'Новое название', 'is_active' => false])
            ->assertRedirect(route('oblasts.index'));

        $this->assertDatabaseHas('cities', ['id' => $city->id, 'name' => 'Новое название', 'is_active' => false]);
    }

    public function test_update_fails_when_name_is_missing(): void
    {
        $this->actingAsAdmin();
        $city = City::factory()->create();

        $this->put(route('cities.update', $city), ['name' => '', 'is_active' => true])
            ->assertSessionHasErrors('name');
    }

    public function test_update_allows_same_name_for_same_city(): void
    {
        $this->actingAsAdmin();
        $city = City::factory()->create(['name' => 'Дашогуз']);

        $this->put(route('cities.update', $city), ['name' => 'Дашогуз', 'is_active' => true])
            ->assertRedirect(route('oblasts.index'));
    }

    public function test_update_fails_when_name_belongs_to_another_city(): void
    {
        $this->actingAsAdmin();
        City::factory()->create(['name' => 'Туркменабат']);
        $city = City::factory()->create(['name' => 'Мары']);

        $this->put(route('cities.update', $city), ['name' => 'Туркменабат', 'is_active' => true])
            ->assertSessionHasErrors('name');
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function test_user_can_delete_a_city(): void
    {
        $this->actingAsAdmin();
        $city = City::factory()->create();

        $this->delete(route('cities.destroy', $city))
            ->assertRedirect(route('oblasts.index'));

        $this->assertModelMissing($city);
    }

    public function test_deleting_nonexistent_city_returns_404(): void
    {
        $this->actingAsAdmin();

        $this->delete(route('cities.destroy', 999))->assertNotFound();
    }
}
