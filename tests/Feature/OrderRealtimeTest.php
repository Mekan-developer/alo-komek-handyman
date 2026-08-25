<?php

namespace Tests\Feature;

use App\Events\OrderStatusChanged;
use App\Events\OrderTaskPriceUpdated;
use App\Models\Category;
use App\Models\Master;
use App\Models\Order;
use App\Models\OrderTask;
use App\Models\User;
use App\Notifications\OrderStatusChangedNotification;
use App\OrderStatus;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Каждое изменение статуса заявки должно долетать до админок:
 * broadcast на канал `orders` + запись в колокольчик.
 */
class OrderRealtimeTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function actingAsAdmin(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        return $user;
    }

    private function eligibleMaster(Category $category): Master
    {
        $master = Master::factory()->create();
        $master->categories()->sync([$category->id]);

        return $master;
    }

    // ── Broadcast ─────────────────────────────────────────────────────────────

    public function test_status_update_broadcasts_order_status_changed(): void
    {
        Event::fake([OrderStatusChanged::class]);
        $this->actingAsAdmin();
        $order = Order::factory()->assigned()->create();

        $this->post(route('orders.update-status', $order), ['status' => OrderStatus::InProgress->value]);

        Event::assertDispatched(OrderStatusChanged::class, fn (OrderStatusChanged $event) => $event->order->id === $order->id
            && $event->from === OrderStatus::Assigned
            && $event->to === OrderStatus::InProgress);
    }

    public function test_assigning_a_master_broadcasts_the_status_transition(): void
    {
        Event::fake([OrderStatusChanged::class]);
        $this->actingAsAdmin();
        $category = Category::factory()->create();
        $master = $this->eligibleMaster($category);
        $order = Order::factory()->create(['category_id' => $category->id]);

        $this->post(route('orders.assign', $order), ['master_id' => $master->id]);

        Event::assertDispatched(OrderStatusChanged::class, fn (OrderStatusChanged $event) => $event->order->id === $order->id
            && $event->from === OrderStatus::Pending
            && $event->to === OrderStatus::Assigned);
    }

    public function test_reassigning_a_master_does_not_broadcast_a_status_change(): void
    {
        Event::fake([OrderStatusChanged::class]);
        $this->actingAsAdmin();
        $category = Category::factory()->create();
        $first = $this->eligibleMaster($category);
        $second = $this->eligibleMaster($category);
        $order = Order::factory()->forMaster($first)->assigned()->create(['category_id' => $category->id]);

        $this->post(route('orders.assign', $order), [
            'master_id' => $second->id,
            'change_reason' => 'Мастер не выходит на связь',
        ]);

        // Статус остался Assigned — событию о смене статуса взяться неоткуда.
        Event::assertNotDispatched(OrderStatusChanged::class);
    }

    public function test_broadcast_payload_carries_the_new_status(): void
    {
        $order = Order::factory()->assigned()->create();
        $event = new OrderStatusChanged($order, OrderStatus::Assigned, OrderStatus::InProgress);

        $this->assertSame('order.status.changed', $event->broadcastAs());
        $this->assertSame([
            'order_id' => $order->id,
            'client_name' => $order->client_name,
            'from' => 'assigned',
            'to' => 'in_progress',
            'to_label' => OrderStatus::InProgress->label(),
        ], $event->broadcastWith());
        $this->assertContains('orders', collect($event->broadcastOn())->map->name->all());
    }

    public function test_setting_a_task_price_broadcasts_the_price_update(): void
    {
        Event::fake([OrderTaskPriceUpdated::class]);
        $this->actingAsAdmin();
        $master = Master::factory()->create();
        $order = Order::factory()->forMaster($master)->inProgress()->create();
        $task = OrderTask::factory()->create(['order_id' => $order->id]);

        $this->post(route('orders.tasks.set-price', ['order' => $order->id, 'task' => $task->id]), ['price' => 350.50]);

        Event::assertDispatched(OrderTaskPriceUpdated::class, fn (OrderTaskPriceUpdated $event) => $event->order->id === $order->id
            && $event->task->id === $task->id);
    }

    public function test_task_price_broadcast_payload_carries_the_new_price(): void
    {
        $master = Master::factory()->create();
        $order = Order::factory()->forMaster($master)->inProgress()->create(['final_price' => 350.50]);
        $task = OrderTask::factory()->priced(350.50)->create(['order_id' => $order->id]);
        $event = new OrderTaskPriceUpdated($order, $task);

        $this->assertSame('order.task.price.updated', $event->broadcastAs());
        $this->assertSame([
            'order_id' => $order->id,
            'task_id' => $task->id,
            'price' => 350.50,
            'final_price' => 350.50,
        ], $event->broadcastWith());

        $channelNames = collect($event->broadcastOn())->map->name->all();
        $this->assertContains('orders', $channelNames);
        $this->assertContains('private-master.'.$master->id, $channelNames);
    }

    // ── Колокольчик ───────────────────────────────────────────────────────────

    public function test_status_change_notifies_every_staff_account(): void
    {
        Notification::fake();
        $admin = $this->actingAsAdmin();
        $other = User::factory()->create();
        $order = Order::factory()->assigned()->create();

        $this->post(route('orders.update-status', $order), ['status' => OrderStatus::InProgress->value]);

        Notification::assertSentTo([$admin, $other], OrderStatusChangedNotification::class);
    }

    public function test_stored_notification_keeps_raw_status_values(): void
    {
        $this->actingAsAdmin();
        $order = Order::factory()->assigned()->create();

        $this->post(route('orders.update-status', $order), ['status' => OrderStatus::InProgress->value]);

        $notification = DB::table('notifications')
            ->where('type', OrderStatusChangedNotification::class)
            ->latest('created_at')
            ->first();

        $this->assertNotNull($notification);
        $this->assertSame([
            'type' => 'order.status.changed',
            'order_id' => $order->id,
            'client_name' => $order->client_name,
            'from' => 'assigned',
            'to' => 'in_progress',
        ], json_decode($notification->data, true));
    }

    public function test_unread_counter_is_shared_with_the_admin_pages(): void
    {
        $this->actingAsAdmin();
        $order = Order::factory()->assigned()->create();

        $this->post(route('orders.update-status', $order), ['status' => OrderStatus::InProgress->value]);

        $this->get(route('orders.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('unreadNotificationsCount', 1));
    }
}
