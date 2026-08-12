<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Client;
use App\Models\Master;
use App\Models\Order;
use App\Models\OrderTask;
use App\Models\OrderTaskPhoto;
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

    private function validPayload(Category $category): array
    {
        return [
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

    public function test_show_includes_task_description(): void
    {
        $this->actingAsAdmin();
        $order = Order::factory()->inProgress()->create();
        OrderTask::factory()->create([
            'order_id' => $order->id,
            'title' => 'Замена смесителя',
            'description' => 'Старый смеситель протекает, поставить новый.',
        ]);

        $this->get(route('orders.show', $order))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('order.tasks.0.title', 'Замена смесителя')
                ->where('order.tasks.0.description', 'Старый смеситель протекает, поставить новый.'));
    }

    public function test_show_returns_404_for_unknown_order(): void
    {
        $this->actingAsAdmin();
        $this->get(route('orders.show', 999))->assertNotFound();
    }

    public function test_eligible_masters_excludes_current_master_and_stays_a_list(): void
    {
        $this->actingAsAdmin();
        $category = Category::factory()->create();

        $masters = Master::factory()->count(3)->create();
        $masters->each(fn (Master $m) => $m->categories()->sync([$category->id]));

        // Assigned master sits in the middle of the eligible set, so filtering it out
        // leaves non-sequential collection keys — must still serialize as a JSON array.
        $order = Order::factory()->forMaster($masters[1])->assigned()->create([
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
        $category = Category::factory()->create();

        $this->post(route('orders.store'), $this->validPayload($category))
            ->assertRedirect(route('orders.index'));

        $this->assertDatabaseHas('orders', [
            'client_name' => 'Aman Jumayev',
            'status' => 'pending',
        ]);
    }

    public function test_admin_can_create_order_for_existing_client(): void
    {
        $this->actingAsAdmin();
        $category = Category::factory()->create();
        $client = Client::factory()->create();

        $payload = array_merge($this->validPayload($category), [
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

    public function test_existing_client_makes_name_and_phone_optional(): void
    {
        $this->actingAsAdmin();
        $category = Category::factory()->create();
        $client = Client::factory()->create();

        $payload = array_merge($this->validPayload($category), [
            'client_id' => $client->id,
            'client_name' => '',
            'client_phone' => '',
        ]);

        $this->post(route('orders.store'), $payload)
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('orders.index'));

        $this->assertDatabaseHas('orders', [
            'client_id' => $client->id,
            'client_name' => $client->name,
            'client_phone' => $client->phone,
        ]);
    }

    public function test_order_for_nameless_existing_client_falls_back_to_phone(): void
    {
        $this->actingAsAdmin();
        $category = Category::factory()->create();
        $client = Client::factory()->create(['name' => null]);

        $payload = array_merge($this->validPayload($category), [
            'client_id' => $client->id,
            'client_name' => '',
            'client_phone' => '',
        ]);

        $this->post(route('orders.store'), $payload)
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('orders.index'));

        $this->assertDatabaseHas('orders', [
            'client_id' => $client->id,
            'client_name' => $client->phone,
        ]);
    }

    public function test_creating_order_for_unknown_phone_creates_client(): void
    {
        $this->actingAsAdmin();
        $category = Category::factory()->create();

        $this->post(route('orders.store'), $this->validPayload($category))
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
        $category = Category::factory()->create();

        $payload = array_merge($this->validPayload($category), [
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
            ->assertSessionHasErrors(['category_id', 'client_name', 'client_phone', 'description', 'client_lat', 'client_lng']);
    }

    public function test_store_rejects_more_than_4_photos(): void
    {
        Storage::fake('public');
        $this->actingAsAdmin();
        $category = Category::factory()->create();

        $payload = array_merge($this->validPayload($category), [
            'photos' => array_fill(0, 5, UploadedFile::fake()->image('p.jpg')),
        ]);

        $this->post(route('orders.store'), $payload)->assertSessionHasErrors('photos');
    }

    // ── Assign master ─────────────────────────────────────────────────────────

    public function test_admin_can_assign_eligible_master(): void
    {
        $this->actingAsAdmin();
        $category = Category::factory()->create();
        $master = Master::factory()->create();
        $master->categories()->sync([$category->id]);
        $order = Order::factory()->create(['category_id' => $category->id]);

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
        $category = Category::factory()->create();
        $firstMaster = Master::factory()->create();
        $secondMaster = Master::factory()->create();
        $firstMaster->categories()->sync([$category->id]);
        $secondMaster->categories()->sync([$category->id]);
        $order = Order::factory()->forMaster($firstMaster)->assigned()->create([
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
        $category = Category::factory()->create();
        $master = Master::factory()->create();
        $master->categories()->sync([$category->id]);
        $order = Order::factory()->create(['category_id' => $category->id]);

        $this->post(route('orders.assign', $order), [
            'master_id' => $master->id,
            'change_reason' => 'Не должно сохраниться',
        ])->assertRedirect(route('orders.show', $order));

        $this->assertNull($order->fresh()->master_change_reason);
    }

    public function test_assigning_inactive_master_fails(): void
    {
        $this->actingAsAdmin();
        $category = Category::factory()->create();
        $master = Master::factory()->inactive()->create();
        $master->categories()->sync([$category->id]);
        $order = Order::factory()->create(['category_id' => $category->id]);

        $this->post(route('orders.assign', $order), ['master_id' => $master->id])
            ->assertRedirect();

        $this->assertNull($order->fresh()->master_id);
    }

    /**
     * Regression: masters used to be filtered by the order's city. The service now
     * covers Ashgabat only, so category match is the sole eligibility rule.
     */
    public function test_eligible_masters_are_selected_by_category_only(): void
    {
        $this->actingAsAdmin();
        $orderCategory = Category::factory()->create();
        $otherCategory = Category::factory()->create();

        $matching = Master::factory()->create();
        $matching->categories()->sync([$orderCategory->id]);

        $nonMatching = Master::factory()->create();
        $nonMatching->categories()->sync([$otherCategory->id]);

        $order = Order::factory()->create(['category_id' => $orderCategory->id]);

        $this->get(route('orders.show', $order))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Orders/Show')
                ->has('eligibleMasters', 1)
                ->where('eligibleMasters.0.id', $matching->id)
            );
    }

    public function test_assigning_master_without_matching_category_fails(): void
    {
        $this->actingAsAdmin();
        $orderCategory = Category::factory()->create();
        $masterCategory = Category::factory()->create();
        $master = Master::factory()->create();
        $master->categories()->sync([$masterCategory->id]);
        $order = Order::factory()->create(['category_id' => $orderCategory->id]);

        $this->post(route('orders.assign', $order), ['master_id' => $master->id])
            ->assertRedirect();

        $this->assertNull($order->fresh()->master_id);
    }

    // ── Task prices ───────────────────────────────────────────────────────────

    private function taskPriceUrl(Order $order, OrderTask $task): string
    {
        return route('orders.tasks.set-price', ['order' => $order->id, 'task' => $task->id]);
    }

    public function test_admin_can_set_a_task_price(): void
    {
        $this->actingAsAdmin();
        $master = Master::factory()->create();
        $order = Order::factory()->forMaster($master)->inProgress()->create();
        $task = OrderTask::factory()->create(['order_id' => $order->id]);

        $this->post($this->taskPriceUrl($order, $task), ['price' => 350.50])
            ->assertRedirect(route('orders.show', $order));

        $this->assertEquals('350.50', $task->fresh()->price);
    }

    public function test_order_total_is_the_sum_of_its_task_prices(): void
    {
        $this->actingAsAdmin();
        $master = Master::factory()->create();
        $order = Order::factory()->forMaster($master)->inProgress()->create();
        $first = OrderTask::factory()->create(['order_id' => $order->id]);
        $second = OrderTask::factory()->create(['order_id' => $order->id]);

        $this->post($this->taskPriceUrl($order, $first), ['price' => 300]);
        $this->post($this->taskPriceUrl($order, $second), ['price' => 150.25]);

        $this->assertEquals('450.25', $order->fresh()->final_price);
    }

    public function test_unpriced_tasks_are_ignored_in_the_order_total(): void
    {
        $this->actingAsAdmin();
        $master = Master::factory()->create();
        $order = Order::factory()->forMaster($master)->inProgress()->create();
        $priced = OrderTask::factory()->create(['order_id' => $order->id]);
        OrderTask::factory()->create(['order_id' => $order->id]);

        $this->post($this->taskPriceUrl($order, $priced), ['price' => 200]);

        $this->assertEquals('200.00', $order->fresh()->final_price);
    }

    public function test_clearing_the_last_task_price_resets_the_order_total_to_null(): void
    {
        $this->actingAsAdmin();
        $master = Master::factory()->create();
        $order = Order::factory()->forMaster($master)->inProgress()->create();
        $task = OrderTask::factory()->priced(300)->create(['order_id' => $order->id]);

        $this->post($this->taskPriceUrl($order, $task), ['price' => null]);

        $this->assertNull($task->fresh()->price);
        $this->assertNull($order->fresh()->final_price);
    }

    public function test_deleting_a_priced_task_recalculates_the_order_total(): void
    {
        $master = Master::factory()->create();
        $order = Order::factory()->forMaster($master)->inProgress()->create();
        OrderTask::factory()->priced(300)->create(['order_id' => $order->id]);
        $removed = OrderTask::factory()->priced(150)->create(['order_id' => $order->id]);

        $removed->delete();

        $this->assertEquals('300.00', $order->fresh()->final_price);
    }

    public function test_setting_a_task_price_on_a_completed_order_fails(): void
    {
        $this->actingAsAdmin();
        $master = Master::factory()->create();
        $order = Order::factory()->forMaster($master)->completed()->create();
        $task = OrderTask::factory()->create(['order_id' => $order->id]);

        $this->post($this->taskPriceUrl($order, $task), ['price' => 100])
            ->assertRedirect();

        $this->assertNull($task->fresh()->price);
    }

    public function test_setting_a_task_price_without_a_master_fails(): void
    {
        $this->actingAsAdmin();
        $order = Order::factory()->create();
        $task = OrderTask::factory()->create(['order_id' => $order->id]);

        $this->post($this->taskPriceUrl($order, $task), ['price' => 100])
            ->assertRedirect();

        $this->assertNull($task->fresh()->price);
        $this->assertNull($order->fresh()->final_price);
    }

    // ── Order discount ────────────────────────────────────────────────────────

    private function discountUrl(Order $order): string
    {
        return route('orders.set-discount', ['order' => $order->id]);
    }

    public function test_admin_can_apply_a_percentage_discount_to_the_order_total(): void
    {
        $this->actingAsAdmin();
        $master = Master::factory()->create();
        $order = Order::factory()->forMaster($master)->inProgress()->create();
        OrderTask::factory()->priced(300)->create(['order_id' => $order->id]);
        OrderTask::factory()->priced(200)->create(['order_id' => $order->id]);

        $this->post($this->discountUrl($order), ['discount_percent' => 10])
            ->assertRedirect(route('orders.show', $order));

        $order->refresh();
        $this->assertEquals('10.00', $order->discount_percent);
        $this->assertEquals('450.00', $order->final_price);
    }

    public function test_discount_is_reapplied_when_a_task_price_changes(): void
    {
        $this->actingAsAdmin();
        $master = Master::factory()->create();
        $order = Order::factory()->forMaster($master)->inProgress()->create();
        $task = OrderTask::factory()->priced(300)->create(['order_id' => $order->id]);

        $this->post($this->discountUrl($order), ['discount_percent' => 25]);
        $this->post($this->taskPriceUrl($order, $task), ['price' => 400]);

        $this->assertEquals('300.00', $order->fresh()->final_price);
    }

    public function test_clearing_the_discount_restores_the_full_total(): void
    {
        $this->actingAsAdmin();
        $master = Master::factory()->create();
        $order = Order::factory()->forMaster($master)->inProgress()->create(['discount_percent' => 20]);
        OrderTask::factory()->priced(500)->create(['order_id' => $order->id]);

        $this->post($this->discountUrl($order), ['discount_percent' => null]);

        $order->refresh();
        $this->assertEquals('0.00', $order->discount_percent);
        $this->assertEquals('500.00', $order->final_price);
    }

    public function test_discount_leaves_the_total_null_when_no_task_is_priced(): void
    {
        $this->actingAsAdmin();
        $master = Master::factory()->create();
        $order = Order::factory()->forMaster($master)->inProgress()->create();
        OrderTask::factory()->create(['order_id' => $order->id]);

        $this->post($this->discountUrl($order), ['discount_percent' => 15]);

        $this->assertNull($order->fresh()->final_price);
    }

    public function test_setting_a_discount_on_a_completed_order_fails(): void
    {
        $this->actingAsAdmin();
        $master = Master::factory()->create();
        $order = Order::factory()->forMaster($master)->completed()->create();

        $this->post($this->discountUrl($order), ['discount_percent' => 10])
            ->assertRedirect();

        $this->assertEquals('0.00', $order->fresh()->discount_percent);
    }

    public function test_discount_must_be_between_zero_and_one_hundred(): void
    {
        $this->actingAsAdmin();
        $master = Master::factory()->create();
        $order = Order::factory()->forMaster($master)->inProgress()->create();

        $this->post($this->discountUrl($order), ['discount_percent' => 120])
            ->assertSessionHasErrors('discount_percent');

        $this->post($this->discountUrl($order), ['discount_percent' => -5])
            ->assertSessionHasErrors('discount_percent');

        $this->assertEquals('0.00', $order->fresh()->discount_percent);
    }

    public function test_master_is_credited_from_the_discounted_total(): void
    {
        $this->actingAsAdmin();
        $master = Master::factory()->create(['payment_value' => 50, 'balance' => 0]);
        $order = Order::factory()->forMaster($master)->inProgress()->create();
        OrderTask::factory()->priced(1000)->create(['order_id' => $order->id]);

        $this->post($this->discountUrl($order), ['discount_percent' => 20]);
        $this->post(route('orders.update-status', $order), ['status' => 'completed']);

        $this->assertEquals('800.00', $order->fresh()->final_price);
        $this->assertEqualsWithDelta(400.0, (float) $master->fresh()->balance, 0.01);
    }

    public function test_task_price_must_not_be_negative(): void
    {
        $this->actingAsAdmin();
        $master = Master::factory()->create();
        $order = Order::factory()->forMaster($master)->inProgress()->create();
        $task = OrderTask::factory()->create(['order_id' => $order->id]);

        $this->post($this->taskPriceUrl($order, $task), ['price' => -5])
            ->assertSessionHasErrors('price');

        $this->assertNull($task->fresh()->price);
    }

    public function test_setting_a_price_for_a_task_of_another_order_returns_404(): void
    {
        $this->actingAsAdmin();
        $master = Master::factory()->create();
        $order = Order::factory()->forMaster($master)->inProgress()->create();
        $foreignTask = OrderTask::factory()->create();

        $this->post($this->taskPriceUrl($order, $foreignTask), ['price' => 100])
            ->assertNotFound();
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
        $category = Category::factory()->create();
        $order = Order::factory()->create(['status' => 'pending']);

        $this->put(route('orders.update', $order), [
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
        ]);
    }

    public function test_update_fails_on_assigned_order(): void
    {
        $this->actingAsAdmin();
        $category = Category::factory()->create();
        $order = Order::factory()->assigned()->create();

        $this->put(route('orders.update', $order), [
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
            ->assertSessionHasErrors(['category_id', 'client_name', 'client_phone', 'description', 'client_lat', 'client_lng']);
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

    public function test_deleting_an_order_removes_the_task_photo_files(): void
    {
        Storage::fake('public');
        $this->actingAsAdmin();

        $order = Order::factory()->create();
        $task = OrderTask::factory()->create(['order_id' => $order->id]);
        $photo = OrderTaskPhoto::factory()->before()->create([
            'order_task_id' => $task->id,
            'path' => 'orders/1/tasks/1/before/one.webp',
        ]);

        Storage::disk('public')->put($photo->path, 'fake');

        $this->delete(route('orders.destroy', $order))
            ->assertRedirect(route('orders.index'));

        Storage::disk('public')->assertMissing($photo->path);
        $this->assertModelMissing($photo);
    }
}
