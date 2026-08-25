<?php

namespace Tests\Feature;

use App\Events\OrderTaskCreated;
use App\Models\Master;
use App\Models\Order;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CreateOrderTaskTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function actingAsMaster(Master $master): string
    {
        return $master->createToken('mobile')->plainTextToken;
    }

    private function storeUrl(int $orderId): string
    {
        return route('api.v1.master.orders.tasks.store', ['order' => $orderId]);
    }

    // ── Happy path ────────────────────────────────────────────────────────────

    public function test_master_can_create_a_task_for_an_in_progress_order(): void
    {
        $master = Master::factory()->create();
        $order = Order::factory()->forMaster($master)->inProgress()->create();
        $token = $this->actingAsMaster($master);

        $this->withToken($token)
            ->postJson($this->storeUrl($order->id), ['title' => 'Замена крана'])
            ->assertStatus(201)
            ->assertJsonPath('data.title', 'Замена крана');

        $this->assertDatabaseHas('order_tasks', [
            'order_id' => $order->id,
            'title' => 'Замена крана',
        ]);
    }

    public function test_master_cannot_create_a_task_before_the_order_is_in_progress(): void
    {
        $master = Master::factory()->create();
        $order = Order::factory()->forMaster($master)->assigned()->create();
        $token = $this->actingAsMaster($master);

        $this->withToken($token)
            ->postJson($this->storeUrl($order->id), ['title' => 'Замена крана'])
            ->assertStatus(422);

        $this->assertDatabaseMissing('order_tasks', ['order_id' => $order->id]);
    }

    // ── Realtime ──────────────────────────────────────────────────────────────

    public function test_creating_a_task_broadcasts_order_task_created(): void
    {
        Event::fake([OrderTaskCreated::class]);
        $master = Master::factory()->create();
        $order = Order::factory()->forMaster($master)->inProgress()->create();
        $token = $this->actingAsMaster($master);

        $this->withToken($token)
            ->postJson($this->storeUrl($order->id), ['title' => 'Замена крана'])
            ->assertStatus(201);

        Event::assertDispatched(OrderTaskCreated::class, fn (OrderTaskCreated $event) => $event->task->order_id === $order->id
            && $event->task->title === 'Замена крана');
    }

    public function test_order_task_created_broadcasts_on_the_public_orders_channel(): void
    {
        $order = Order::factory()->inProgress()->create();
        $task = $order->tasks()->create(['title' => 'Замена крана']);

        $event = new OrderTaskCreated($task);

        $this->assertSame('order.task.created', $event->broadcastAs());
        $this->assertSame([
            'order_id' => $order->id,
            'task_id' => $task->id,
            'title' => 'Замена крана',
        ], $event->broadcastWith());
        $this->assertContains('orders', collect($event->broadcastOn())->map->name->all());
    }
}
