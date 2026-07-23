<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\City;
use App\Models\Client;
use App\Models\Master;
use App\Models\Order;
use App\Models\User;
use App\OrderStatus;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function actingAsAdmin(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        return $user;
    }

    private function validPayload(City $city, Category $category): array
    {
        return [
            'city_id' => $city->id,
            'category_id' => $category->id,
            'client_name' => 'Aman Jumayev',
            'client_phone' => '+99362111222',
            'description' => 'Кран течёт уже неделю, нужна срочная починка.',
            'client_address' => 'ул. Андалиб, 12',
            'client_lat' => 37.952321,
            'client_lng' => 58.382345,
        ];
    }

    // ── Index ─────────────────────────────────────────────────────────────────

    public function test_orders_index_requires_auth(): void
    {
        $this->get(route('orders.index'))->assertRedirect(route('login'));
    }

    public function test_admin_can_view_orders_index(): void
    {
        $this->actingAsAdmin();
        Order::factory()->count(2)->create();

        $this->get(route('orders.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Orders/Index')->has('orders'));
    }

    public function test_orders_index_can_be_filtered_by_status(): void
    {
        $this->actingAsAdmin();
        Order::factory()->create();
        Order::factory()->completed()->create();

        $this->get(route('orders.index', ['status' => OrderStatus::Completed->value]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('orders.data.0.status', 'completed'));
    }

    public function test_orders_index_can_be_searched_by_client_name(): void
    {
        $this->actingAsAdmin();
        Order::factory()->create(['client_name' => 'Aman Jumayev']);
        Order::factory()->create(['client_name' => 'Merdan Saparov']);

        $this->get(route('orders.index', ['search' => 'Jumayev']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('orders.data', fn ($orders) => count($orders) === 1)
                ->where('orders.data.0.client_name', 'Aman Jumayev'));
    }

    public function test_orders_index_can_be_searched_by_client_phone(): void
    {
        $this->actingAsAdmin();
        Order::factory()->create(['client_phone' => '+99362111222']);
        Order::factory()->create(['client_phone' => '+99365999888']);

        $this->get(route('orders.index', ['search' => '111222']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('orders.data', fn ($orders) => count($orders) === 1)
                ->where('orders.data.0.client_phone', '+99362111222'));
    }

    public function test_orders_index_can_be_filtered_by_date_range(): void
    {
        $this->actingAsAdmin();
        Order::factory()->create(['created_at' => '2026-01-10 12:00:00']);
        Order::factory()->create(['created_at' => '2026-03-20 12:00:00']);

        $this->get(route('orders.index', ['date_from' => '2026-03-01', 'date_to' => '2026-03-31']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('orders.data', fn ($orders) => count($orders) === 1));
    }

    // ── Show ──────────────────────────────────────────────────────────────────

    public function test_admin_can_view_order_details(): void
    {
        $this->actingAsAdmin();
        $order = Order::factory()->create();

        $this->get(route('orders.show', $order))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Orders/Show')
                ->where('order.id', $order->id)
                ->has('eligibleMasters'));
    }

    public function test_show_returns_404_for_unknown_order(): void
    {
        $this->actingAsAdmin();
        $this->get(route('orders.show', 999))->assertNotFound();
    }

    public function test_eligible_masters_excludes_current_master_and_stays_a_list(): void
    {
        $this->actingAsAdmin();
        $city = City::factory()->create();
        $category = Category::factory()->create();

        $masters = Master::factory()->count(3)->create(['city_id' => $city->id]);
        $masters->each(fn (Master $m) => $m->categories()->sync([$category->id]));

        // Assigned master sits in the middle of the eligible set, so filtering it out
        // leaves non-sequential collection keys — must still serialize as a JSON array.
        $order = Order::factory()->forMaster($masters[1])->assigned()->create([
            'city_id' => $city->id,
            'category_id' => $category->id,
        ]);

        $this->get(route('orders.show', $order))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('eligibleMasters', fn ($eligible) => count($eligible) === 2
                    // Keys must be sequential (0,1) — otherwise Inertia serializes the
                    // collection as a JSON object and the Vue Array prop reads as empty.
                    && array_is_list(collect($eligible)->all())
                    && collect($eligible)->pluck('id')->doesntContain($masters[1]->id))
            );
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function test_admin_can_create_order(): void
    {
        $this->actingAsAdmin();
        $city = City::factory()->create();
        $category = Category::factory()->create();

        $this->post(route('orders.store'), $this->validPayload($city, $category))
            ->assertRedirect(route('orders.index'));

        $this->assertDatabaseHas('orders', [
            'client_name' => 'Aman Jumayev',
            'status' => 'pending',
        ]);
    }

    public function test_admin_can_create_order_for_existing_client(): void
    {
        $this->actingAsAdmin();
        $city = City::factory()->create();
        $category = Category::factory()->create();
        $client = Client::factory()->create();

        $payload = array_merge($this->validPayload($city, $category), [
            'client_id' => $client->id,
        ]);

        $this->post(route('orders.store'), $payload)
            ->assertRedirect(route('orders.index'));

        $this->assertDatabaseHas('orders', [
            'client_id' => $client->id,
            'client_phone' => $client->phone,
            'status' => 'pending',
        ]);
    }

    public function test_creating_order_for_unknown_phone_creates_client(): void
    {
        $this->actingAsAdmin();
        $city = City::factory()->create();
        $category = Category::factory()->create();

        $this->post(route('orders.store'), $this->validPayload($city, $category))
            ->assertRedirect();

        $this->assertDatabaseHas('clients', [
            'phone' => '+99362111222',
            'name' => 'Aman Jumayev',
        ]);

        $client = Client::where('phone', '+99362111222')->firstOrFail();
        $this->assertDatabaseHas('orders', [
            'client_id' => $client->id,
            'client_name' => 'Aman Jumayev',
        ]);
    }

    public function test_creating_order_with_photos_stores_them(): void
    {
        Storage::fake('public');
        $this->actingAsAdmin();
        $city = City::factory()->create();
        $category = Category::factory()->create();

        $payload = array_merge($this->validPayload($city, $category), [
            'photos' => [
                UploadedFile::fake()->image('a.jpg'),
                UploadedFile::fake()->image('b.jpg'),
            ],
        ]);

        $this->post(route('orders.store'), $payload)->assertRedirect();

        $order = Order::where('client_name', 'Aman Jumayev')->firstOrFail();
        $this->assertCount(2, $order->photos);
    }

    public function test_store_fails_without_required_fields(): void
    {
        $this->actingAsAdmin();
        $this->post(route('orders.store'), [])
            ->assertSessionHasErrors(['city_id', 'category_id', 'client_name', 'client_phone', 'description', 'client_lat', 'client_lng']);
    }

    public function test_store_rejects_more_than_4_photos(): void
    {
        Storage::fake('public');
        $this->actingAsAdmin();
        $city = City::factory()->create();
        $category = Category::factory()->create();

        $payload = array_merge($this->validPayload($city, $category), [
            'photos' => array_fill(0, 5, UploadedFile::fake()->image('p.jpg')),
        ]);

        $this->post(route('orders.store'), $payload)->assertSessionHasErrors('photos');
    }

    // ── Assign master ─────────────────────────────────────────────────────────

    public function test_admin_can_assign_eligible_master(): void
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
            'status' => 'assigned',
        ]);
    }

    public function test_admin_can_reassign_a_different_master_with_a_reason(): void
    {
        $this->actingAsAdmin();
        $city = City::factory()->create();
        $category = Category::factory()->create();
        $firstMaster = Master::factory()->create(['city_id' => $city->id]);
        $secondMaster = Master::factory()->create(['city_id' => $city->id]);
        $firstMaster->categories()->sync([$category->id]);
        $secondMaster->categories()->sync([$category->id]);
        $order = Order::factory()->forMaster($firstMaster)->assigned()->create([
            'city_id' => $city->id,
            'category_id' => $category->id,
        ]);

        $this->post(route('orders.assign', $order), [
            'master_id' => $secondMaster->id,
            'change_reason' => 'Первый мастер недоступен',
        ])->assertRedirect(route('orders.show', $order));

        $fresh = $order->fresh();
        $this->assertEquals($secondMaster->id, $fresh->master_id);
        $this->assertEquals('Первый мастер недоступен', $fresh->master_change_reason);
    }

    public function test_first_time_assignment_ignores_change_reason(): void
    {
        $this->actingAsAdmin();
        $city = City::factory()->create();
        $category = Category::factory()->create();
        $master = Master::factory()->create(['city_id' => $city->id]);
        $master->categories()->sync([$category->id]);
        $order = Order::factory()->create(['city_id' => $city->id, 'category_id' => $category->id]);

        $this->post(route('orders.assign', $order), [
            'master_id' => $master->id,
            'change_reason' => 'Не должно сохраниться',
        ])->assertRedirect(route('orders.show', $order));

        $this->assertNull($order->fresh()->master_change_reason);
    }

    public function test_assigning_inactive_master_fails(): void
    {
        $this->actingAsAdmin();
        $city = City::factory()->create();
        $category = Category::factory()->create();
        $master = Master::factory()->inactive()->create(['city_id' => $city->id]);
        $master->categories()->sync([$category->id]);
        $order = Order::factory()->create(['city_id' => $city->id, 'category_id' => $category->id]);

        $this->post(route('orders.assign', $order), ['master_id' => $master->id])
            ->assertRedirect();

        $this->assertNull($order->fresh()->master_id);
    }

    public function test_assigning_master_from_different_city_fails(): void
    {
        $this->actingAsAdmin();
        $cityA = City::factory()->create();
        $cityB = City::factory()->create();
        $category = Category::factory()->create();
        $master = Master::factory()->create(['city_id' => $cityB->id]);
        $master->categories()->sync([$category->id]);
        $order = Order::factory()->create(['city_id' => $cityA->id, 'category_id' => $category->id]);

        $this->post(route('orders.assign', $order), ['master_id' => $master->id])
            ->assertRedirect();

        $this->assertNull($order->fresh()->master_id);
    }

    public function test_assigning_master_without_matching_category_fails(): void
    {
        $this->actingAsAdmin();
        $city = City::factory()->create();
        $orderCategory = Category::factory()->create();
        $masterCategory = Category::factory()->create();
        $master = Master::factory()->create(['city_id' => $city->id]);
        $master->categories()->sync([$masterCategory->id]);
        $order = Order::factory()->create(['city_id' => $city->id, 'category_id' => $orderCategory->id]);

        $this->post(route('orders.assign', $order), ['master_id' => $master->id])
            ->assertRedirect();

        $this->assertNull($order->fresh()->master_id);
    }

    // ── Set final price ───────────────────────────────────────────────────────

    public function test_admin_can_set_final_price(): void
    {
        $this->actingAsAdmin();
        $master = Master::factory()->create();
        $order = Order::factory()->forMaster($master)->assigned()->create();

        $this->post(route('orders.set-price', $order), ['final_price' => 350.50])
            ->assertRedirect(route('orders.show', $order));

        $this->assertEquals('350.50', $order->fresh()->final_price);
    }

    public function test_setting_price_on_completed_order_fails(): void
    {
        $this->actingAsAdmin();
        $order = Order::factory()->completed()->create();

        $this->post(route('orders.set-price', $order), ['final_price' => 100])
            ->assertRedirect();

        $this->assertEquals('completed', $order->fresh()->status->value);
    }

    public function test_setting_price_without_a_master_fails(): void
    {
        $this->actingAsAdmin();
        $order = Order::factory()->create();

        $this->post(route('orders.set-price', $order), ['final_price' => 100])
            ->assertRedirect();

        $this->assertNull($order->fresh()->final_price);
    }

    // ── Update status ─────────────────────────────────────────────────────────

    public function test_admin_can_transition_assigned_to_in_progress(): void
    {
        $this->actingAsAdmin();
        $order = Order::factory()->assigned()->create();

        $this->post(route('orders.update-status', $order), ['status' => 'in_progress'])
            ->assertRedirect();

        $this->assertEquals('in_progress', $order->fresh()->status->value);
        $this->assertNotNull($order->fresh()->started_at);
    }

    public function test_admin_can_complete_order(): void
    {
        $this->actingAsAdmin();
        $order = Order::factory()->inProgress()->create();

        $this->post(route('orders.update-status', $order), ['status' => 'completed'])
            ->assertRedirect();

        $this->assertEquals('completed', $order->fresh()->status->value);
        $this->assertNotNull($order->fresh()->completed_at);
    }

    public function test_completing_percentage_order_without_price_credits_zero_and_warns(): void
    {
        $this->actingAsAdmin();
        $master = Master::factory()->create(['payment_value' => 35, 'balance' => 0]);
        $order = Order::factory()->forMaster($master)->inProgress()->create(['final_price' => null]);

        $this->post(route('orders.update-status', $order), ['status' => 'completed'])
            ->assertRedirect()
            ->assertSessionHas('notification', fn ($notification) => $notification['type'] === 'warning');

        $this->assertEquals('completed', $order->fresh()->status->value);
        $this->assertEqualsWithDelta(0.0, (float) $master->fresh()->balance, 0.01);
    }

    public function test_completing_percentage_order_with_price_credits_master(): void
    {
        $this->actingAsAdmin();
        $master = Master::factory()->create(['payment_value' => 35, 'balance' => 0]);
        $order = Order::factory()->forMaster($master)->inProgress()->create(['final_price' => 1000]);

        $this->post(route('orders.update-status', $order), ['status' => 'completed'])
            ->assertRedirect()
            ->assertSessionHas('notification', fn ($notification) => $notification['type'] === 'success');

        $this->assertEqualsWithDelta(350.0, (float) $master->fresh()->balance, 0.01);
    }

    public function test_admin_can_cancel_order_with_reason(): void
    {
        $this->actingAsAdmin();
        $order = Order::factory()->create();

        $this->post(route('orders.update-status', $order), [
            'status' => 'cancelled',
            'cancel_reason' => 'Клиент передумал',
        ])->assertRedirect();

        $fresh = $order->fresh();
        $this->assertEquals('cancelled', $fresh->status->value);
        $this->assertEquals('Клиент передумал', $fresh->cancel_reason);
    }

    public function test_invalid_status_transition_is_rejected(): void
    {
        $this->actingAsAdmin();
        $order = Order::factory()->create();

        $this->post(route('orders.update-status', $order), ['status' => 'completed'])
            ->assertRedirect();

        $this->assertEquals('pending', $order->fresh()->status->value);
    }

    public function test_pending_order_cannot_be_manually_set_to_assigned_without_a_master(): void
    {
        $this->actingAsAdmin();
        $order = Order::factory()->create();

        $this->post(route('orders.update-status', $order), ['status' => 'assigned'])
            ->assertRedirect();

        $fresh = $order->fresh();
        $this->assertEquals('pending', $fresh->status->value);
        $this->assertNull($fresh->master_id);
    }

    // ── Update ───────────────────────────────────────────────────────────────

    public function test_admin_can_update_pending_order(): void
    {
        $this->actingAsAdmin();
        $city = City::factory()->create();
        $category = Category::factory()->create();
        $order = Order::factory()->create(['status' => 'pending']);

        $this->put(route('orders.update', $order), [
            'city_id' => $city->id,
            'category_id' => $category->id,
            'client_name' => 'Обновлённое имя',
            'client_phone' => '+99362999888',
            'description' => 'Новое описание проблемы',
            'client_address' => 'ул. Новая, 5',
            'client_lat' => 37.95,
            'client_lng' => 58.38,
        ])->assertRedirect(route('orders.show', $order));

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'client_name' => 'Обновлённое имя',
            'city_id' => $city->id,
        ]);
    }

    public function test_update_fails_on_assigned_order(): void
    {
        $this->actingAsAdmin();
        $city = City::factory()->create();
        $category = Category::factory()->create();
        $order = Order::factory()->assigned()->create();

        $this->put(route('orders.update', $order), [
            'city_id' => $city->id,
            'category_id' => $category->id,
            'client_name' => 'Test',
            'client_phone' => '+99362000000',
            'description' => 'Test',
            'client_lat' => 37.95,
            'client_lng' => 58.38,
        ])->assertRedirect();

        $this->assertNotEquals('Test', $order->fresh()->client_name);
    }

    public function test_update_fails_without_required_fields(): void
    {
        $this->actingAsAdmin();
        $order = Order::factory()->create(['status' => 'pending']);

        $this->put(route('orders.update', $order), [])
            ->assertSessionHasErrors(['city_id', 'category_id', 'client_name', 'client_phone', 'description', 'client_lat', 'client_lng']);
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function test_admin_can_delete_order(): void
    {
        $this->actingAsAdmin();
        $order = Order::factory()->create();

        $this->delete(route('orders.destroy', $order))
            ->assertRedirect(route('orders.index'));

        $this->assertModelMissing($order);
    }
}
