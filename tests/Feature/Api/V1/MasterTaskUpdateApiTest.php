<?php

namespace Tests\Feature\Api\V1;

use App\Events\OrderTaskUpdated;
use App\Models\Master;
use App\Models\Order;
use App\Models\OrderTask;
use App\OrderStatus;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class MasterTaskUpdateApiTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function actingAsMaster(Master $master): string
    {
        return $master->createToken('mobile')->plainTextToken;
    }

    /** @param array<string, mixed> $attributes */
    private function makeTask(Master $master, array $attributes = []): OrderTask
    {
        $order = Order::factory()->forMaster($master)->inProgress()->create();

        return OrderTask::factory()->create([
            'order_id' => $order->id,
            'title' => 'Замена прокладки',
            'description' => 'Старая прокладка потекла',
            ...$attributes,
        ]);
    }

    private function updateUrl(int $orderId, int $taskId): string
    {
        return route('api.v1.master.orders.tasks.update', ['order' => $orderId, 'task' => $taskId]);
    }

    // ── Happy path ────────────────────────────────────────────────────────────

    public function test_master_can_update_own_task(): void
    {
        $master = Master::factory()->create();
        $task = $this->makeTask($master);

        $this->withToken($this->actingAsMaster($master))
            ->patchJson($this->updateUrl($task->order_id, $task->id), [
                'title' => 'Замена смесителя',
                'description' => 'Прокладка не помогла, поменял смеситель целиком',
            ])
            ->assertOk()
            ->assertJsonPath('data.id', $task->id)
            ->assertJsonPath('data.title', 'Замена смесителя')
            ->assertJsonPath('data.description', 'Прокладка не помогла, поменял смеситель целиком')
            ->assertJsonStructure(['data' => ['id', 'title', 'description', 'price', 'before_photos', 'after_photos']]);

        $this->assertDatabaseHas('order_tasks', [
            'id' => $task->id,
            'title' => 'Замена смесителя',
        ]);
    }

    public function test_update_is_partial_and_leaves_absent_fields_untouched(): void
    {
        $master = Master::factory()->create();
        $task = $this->makeTask($master);

        $this->withToken($this->actingAsMaster($master))
            ->patchJson($this->updateUrl($task->order_id, $task->id), ['title' => 'Только заголовок'])
            ->assertOk()
            ->assertJsonPath('data.title', 'Только заголовок')
            ->assertJsonPath('data.description', 'Старая прокладка потекла');
    }

    public function test_description_can_be_cleared_with_null(): void
    {
        $master = Master::factory()->create();
        $task = $this->makeTask($master);

        $this->withToken($this->actingAsMaster($master))
            ->patchJson($this->updateUrl($task->order_id, $task->id), ['description' => null])
            ->assertOk()
            ->assertJsonPath('data.description', null);

        $this->assertDatabaseHas('order_tasks', ['id' => $task->id, 'description' => null]);
    }

    public function test_update_does_not_touch_the_admin_set_price(): void
    {
        $master = Master::factory()->create();
        $task = $this->makeTask($master, ['price' => 300]);

        $this->withToken($this->actingAsMaster($master))
            ->patchJson($this->updateUrl($task->order_id, $task->id), [
                'title' => 'Новое название',
                'price' => 999,
            ])
            ->assertOk()
            ->assertJsonPath('data.price', 300);

        $this->assertEquals(300, (float) $task->fresh()->price);
    }

    public function test_update_broadcasts_the_task_updated_event(): void
    {
        Event::fake([OrderTaskUpdated::class]);

        $master = Master::factory()->create();
        $task = $this->makeTask($master);

        $this->withToken($this->actingAsMaster($master))
            ->patchJson($this->updateUrl($task->order_id, $task->id), ['title' => 'Новое название'])
            ->assertOk();

        Event::assertDispatched(
            OrderTaskUpdated::class,
            fn (OrderTaskUpdated $event) => $event->task->id === $task->id
                && $event->task->title === 'Новое название'
        );
    }

    public function test_no_event_is_broadcast_when_nothing_changed(): void
    {
        Event::fake([OrderTaskUpdated::class]);

        $master = Master::factory()->create();
        $task = $this->makeTask($master);

        $this->withToken($this->actingAsMaster($master))
            ->patchJson($this->updateUrl($task->order_id, $task->id), ['title' => $task->title])
            ->assertOk();

        Event::assertNotDispatched(OrderTaskUpdated::class);
    }

    // ── Validation ────────────────────────────────────────────────────────────

    public function test_title_cannot_be_blank_when_present(): void
    {
        $master = Master::factory()->create();
        $task = $this->makeTask($master);

        $this->withToken($this->actingAsMaster($master))
            ->patchJson($this->updateUrl($task->order_id, $task->id), ['title' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors('title');
    }

    public function test_title_is_limited_to_255_characters(): void
    {
        $master = Master::factory()->create();
        $task = $this->makeTask($master);

        $this->withToken($this->actingAsMaster($master))
            ->patchJson($this->updateUrl($task->order_id, $task->id), ['title' => str_repeat('a', 256)])
            ->assertStatus(422)
            ->assertJsonValidationErrors('title');
    }

    // ── Business rules ────────────────────────────────────────────────────────

    public function test_master_cannot_update_task_once_order_is_completed(): void
    {
        $master = Master::factory()->create();
        $task = $this->makeTask($master);

        $task->order->update(['status' => OrderStatus::Completed]);

        $this->withToken($this->actingAsMaster($master))
            ->patchJson($this->updateUrl($task->order_id, $task->id), ['title' => 'Поздняя правка'])
            ->assertStatus(422)
            ->assertJsonPath('message', __('orders.errors.task_not_editable'));

        $this->assertDatabaseHas('order_tasks', ['id' => $task->id, 'title' => 'Замена прокладки']);
    }

    // ── Authorization ─────────────────────────────────────────────────────────

    public function test_another_master_cannot_update_the_task(): void
    {
        $master = Master::factory()->create();
        $task = $this->makeTask($master);

        $other = Master::factory()->create();

        $this->withToken($this->actingAsMaster($other))
            ->patchJson($this->updateUrl($task->order_id, $task->id), ['title' => 'Чужая правка'])
            ->assertStatus(404);

        $this->assertDatabaseHas('order_tasks', ['id' => $task->id, 'title' => 'Замена прокладки']);
    }

    public function test_task_from_another_order_is_not_reachable(): void
    {
        $master = Master::factory()->create();
        $task = $this->makeTask($master);
        $otherOrder = Order::factory()->forMaster($master)->inProgress()->create();

        $this->withToken($this->actingAsMaster($master))
            ->patchJson($this->updateUrl($otherOrder->id, $task->id), ['title' => 'Не туда'])
            ->assertStatus(404);
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $master = Master::factory()->create();
        $task = $this->makeTask($master);

        $this->patchJson($this->updateUrl($task->order_id, $task->id), ['title' => 'Аноним'])
            ->assertStatus(401);
    }
}
