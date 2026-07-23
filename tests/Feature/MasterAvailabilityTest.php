<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\City;
use App\Models\Master;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class MasterAvailabilityTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function actingAsMaster(Master $master): string
    {
        return $master->createToken('mobile')->plainTextToken;
    }

    private function actingAsAdmin(): void
    {
        $this->actingAs(User::factory()->create());
    }

    // ── PATCH /api/v1/master/availability ─────────────────────────────────────

    public function test_master_can_set_unavailable(): void
    {
        $master = Master::factory()->create();
        $token = $this->actingAsMaster($master);

        $this->withToken($token)
            ->patchJson(route('api.v1.master.availability.update'), ['is_available' => false])
            ->assertOk()
            ->assertJsonPath('data.is_available', false);

        $this->assertDatabaseHas('masters', ['id' => $master->id, 'is_available' => false]);
    }

    public function test_master_can_set_available_again(): void
    {
        $master = Master::factory()->unavailable()->create();
        $token = $this->actingAsMaster($master);

        $this->withToken($token)
            ->patchJson(route('api.v1.master.availability.update'), ['is_available' => true])
            ->assertOk()
            ->assertJsonPath('data.is_available', true);

        $this->assertDatabaseHas('masters', ['id' => $master->id, 'is_available' => true]);
    }

    public function test_availability_requires_boolean_field(): void
    {
        $master = Master::factory()->create();
        $token = $this->actingAsMaster($master);

        $this->withToken($token)
            ->patchJson(route('api.v1.master.availability.update'), [])
            ->assertUnprocessable();
    }

    public function test_unauthenticated_master_cannot_set_availability(): void
    {
        $this->patchJson(route('api.v1.master.availability.update'), ['is_available' => false])
            ->assertUnauthorized();
    }

    // ── AssignMasterAction blocks unavailable master ──────────────────────────

    public function test_admin_cannot_assign_unavailable_master(): void
    {
        $this->actingAsAdmin();
        $city = City::factory()->create();
        $category = Category::factory()->create();

        $master = Master::factory()->unavailable()->create(['city_id' => $city->id]);
        $master->categories()->sync([$category->id]);

        $order = Order::factory()->create(['city_id' => $city->id, 'category_id' => $category->id]);

        $this->post(route('orders.assign', $order), ['master_id' => $master->id])
            ->assertRedirect();

        $this->assertNull($order->fresh()->master_id);
    }

    public function test_admin_can_assign_available_master(): void
    {
        $this->actingAsAdmin();
        $city = City::factory()->create();
        $category = Category::factory()->create();

        $master = Master::factory()->create(['city_id' => $city->id]);
        $master->categories()->sync([$category->id]);

        $order = Order::factory()->create(['city_id' => $city->id, 'category_id' => $category->id]);

        $this->post(route('orders.assign', $order), ['master_id' => $master->id])
            ->assertRedirect(route('orders.show', $order));

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'master_id' => $master->id,
        ]);
    }

    // ── GET /api/v1/master/me includes is_available ───────────────────────────

    public function test_profile_response_includes_is_available(): void
    {
        $master = Master::factory()->unavailable()->create();
        $token = $this->actingAsMaster($master);

        $this->withToken($token)
            ->getJson(route('api.v1.master.me'))
            ->assertOk()
            ->assertJsonPath('data.is_available', false);
    }
}
