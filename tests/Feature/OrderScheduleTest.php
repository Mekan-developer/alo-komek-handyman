<?php

namespace Tests\Feature;

use App\Enums\OrderTimeSlot;
use App\Models\Category;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class OrderScheduleTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function actingAsAdmin(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        return $user;
    }

    /** @return array<string, mixed> */
    private function validPayload(Category $category, array $overrides = []): array
    {
        return array_merge([
            'category_id' => $category->id,
            'client_name' => 'Aman Jumayev',
            'client_phone' => '+99362111222',
            'description' => 'Кран течёт уже неделю, нужна срочная починка.',
            'preferred_date' => now()->toDateString(),
            'time_slot' => null,
            'is_urgent' => false,
            'client_address' => 'ул. Андалиб, 12',
            'client_lat' => 37.952321,
            'client_lng' => 58.382345,
        ], $overrides);
    }

    public function test_admin_can_create_order_with_time_slot(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-15 09:00:00'));
        $this->actingAsAdmin();
        $category = Category::factory()->create();

        $this->post(route('orders.store'), $this->validPayload($category, [
            'time_slot' => '10-12',
        ]))->assertRedirect(route('orders.index'));

        $order = Order::where('client_name', 'Aman Jumayev')->firstOrFail();

        $this->assertSame(now()->toDateString(), $order->preferred_date->toDateString());
        $this->assertSame('10-12', $order->time_slot->value);
        $this->assertFalse($order->is_urgent);
        $this->assertNull($order->urgency_fee);
    }

    public function test_create_rejects_past_preferred_date(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-15 18:37:00'));
        $this->actingAsAdmin();
        $category = Category::factory()->create();

        $this->post(route('orders.store'), $this->validPayload($category, [
            'preferred_date' => '2026-09-14',
            'time_slot' => '10-12',
        ]))->assertSessionHasErrors('preferred_date');
    }

    public function test_create_rejects_passed_time_slot_for_today(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-15 18:37:00'));
        $this->actingAsAdmin();
        $category = Category::factory()->create();

        $this->post(route('orders.store'), $this->validPayload($category, [
            'preferred_date' => '2026-09-15',
            'time_slot' => '16-18',
        ]))->assertSessionHasErrors('time_slot');

        $this->post(route('orders.store'), $this->validPayload($category, [
            'preferred_date' => '2026-09-15',
            'time_slot' => '18-20',
        ]))->assertSessionHasErrors('time_slot');
    }

    public function test_create_allows_remaining_slot_later_today(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-15 17:30:00'));
        $this->actingAsAdmin();
        $category = Category::factory()->create();

        $this->post(route('orders.store'), $this->validPayload($category, [
            'preferred_date' => '2026-09-15',
            'time_slot' => '18-20',
        ]))->assertSessionHasNoErrors()->assertRedirect(route('orders.index'));
    }

    public function test_time_slot_label_uses_colon_format(): void
    {
        $this->assertSame('08:00 - 10:00', OrderTimeSlot::Slot08010->label());
        $this->assertSame('18:00 - 20:00', OrderTimeSlot::Slot18020->label());
    }

    public function test_urgent_order_snapshots_urgency_fee_from_settings(): void
    {
        Setting::create(['key' => 'order_urgency_fee', 'value' => '20']);
        $this->actingAsAdmin();
        $category = Category::factory()->create();

        $this->post(route('orders.store'), $this->validPayload($category, [
            'is_urgent' => true,
            'preferred_date' => now()->toDateString(),
            'time_slot' => '08-10',
        ]))->assertRedirect(route('orders.index'));

        $order = Order::where('client_name', 'Aman Jumayev')->firstOrFail();

        $this->assertTrue($order->is_urgent);
        $this->assertEquals(20.0, (float) $order->urgency_fee);
        $this->assertNull($order->preferred_date);
        $this->assertNull($order->time_slot);
    }

    public function test_urgent_order_does_not_require_preferred_date(): void
    {
        Setting::create(['key' => 'order_urgency_fee', 'value' => '20']);
        $this->actingAsAdmin();
        $category = Category::factory()->create();

        $payload = $this->validPayload($category, ['is_urgent' => true]);
        unset($payload['preferred_date'], $payload['time_slot']);

        $this->post(route('orders.store'), $payload)
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('orders.index'));
    }

    public function test_create_rejects_invalid_time_slot(): void
    {
        $this->actingAsAdmin();
        $category = Category::factory()->create();

        $this->post(route('orders.store'), $this->validPayload($category, [
            'preferred_date' => now()->addDay()->toDateString(),
            'time_slot' => '09-11',
        ]))->assertSessionHasErrors('time_slot');
    }

    public function test_create_requires_preferred_date(): void
    {
        $this->actingAsAdmin();
        $category = Category::factory()->create();

        $payload = $this->validPayload($category);
        unset($payload['preferred_date']);

        $this->post(route('orders.store'), $payload)
            ->assertSessionHasErrors('preferred_date');
    }

    public function test_cancelling_urgent_order_stores_cancel_and_urgency_fees(): void
    {
        Setting::create(['key' => 'order_urgency_fee', 'value' => '20']);
        Setting::create(['key' => 'order_cancel_fee', 'value' => '15']);

        $this->actingAsAdmin();
        $order = Order::factory()->urgent()->create([
            'urgency_fee' => 20,
        ]);

        $this->post(route('orders.update-status', $order), [
            'status' => 'cancelled',
            'cancel_reason' => 'Клиент передумал',
        ])->assertRedirect();

        $order->refresh();

        $this->assertSame('cancelled', $order->status->value);
        $this->assertTrue($order->is_urgent);
        $this->assertEquals(20.0, (float) $order->urgency_fee);
        $this->assertEquals(15.0, (float) $order->cancel_fee);
        $this->assertSame('Клиент передумал', $order->cancel_reason);
    }

    public function test_admin_can_change_schedule_on_assigned_order(): void
    {
        Setting::create(['key' => 'order_urgency_fee', 'value' => '20']);
        $this->actingAsAdmin();

        $order = Order::factory()->assigned()->create([
            'preferred_date' => now()->toDateString(),
            'time_slot' => '08-10',
            'is_urgent' => false,
        ]);

        $this->put(route('orders.update-schedule', $order), [
            'preferred_date' => now()->addDay()->toDateString(),
            'time_slot' => '14-16',
            'is_urgent' => true,
        ])->assertRedirect(route('orders.show', $order));

        $order->refresh();

        $this->assertNull($order->preferred_date);
        $this->assertNull($order->time_slot);
        $this->assertTrue($order->is_urgent);
        $this->assertEquals(20.0, (float) $order->urgency_fee);
    }

    public function test_cannot_change_schedule_on_completed_order(): void
    {
        $this->actingAsAdmin();
        $order = Order::factory()->completed()->create([
            'preferred_date' => now()->toDateString(),
            'time_slot' => '08-10',
        ]);

        $this->put(route('orders.update-schedule', $order), [
            'preferred_date' => now()->addDay()->toDateString(),
            'time_slot' => '10-12',
            'is_urgent' => true,
        ])->assertRedirect(route('orders.show', $order));

        $order->refresh();
        $this->assertSame(now()->toDateString(), $order->preferred_date->toDateString());
        $this->assertSame('08-10', $order->time_slot->value);
        $this->assertFalse($order->is_urgent);
    }

    public function test_administrator_can_update_order_fee_settings(): void
    {
        $admin = User::factory()->administrator()->create();

        $this->actingAs($admin)
            ->put(route('settings.update'), [
                'order_urgency_fee' => '25',
                'order_cancel_fee' => '10',
            ])
            ->assertRedirect(route('settings.index'));

        $this->assertDatabaseHas('settings', ['key' => 'order_urgency_fee', 'value' => '25']);
        $this->assertDatabaseHas('settings', ['key' => 'order_cancel_fee', 'value' => '10']);
    }

    public function test_orders_index_includes_urgency_flag(): void
    {
        $this->actingAsAdmin();
        Order::factory()->urgent()->create();
        Order::factory()->create(['is_urgent' => false]);

        $this->get(route('orders.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Orders/Index')
                ->has('orders.data', 2)
                ->where('orders.data', fn ($orders) => collect($orders)->contains(
                    fn ($order) => ($order['is_urgent'] ?? false) === true
                )));
    }
}
