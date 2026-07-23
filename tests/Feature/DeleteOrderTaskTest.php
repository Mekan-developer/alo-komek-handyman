<?php

namespace Tests\Feature;

use App\Models\Master;
use App\Models\Order;
use App\Models\OrderTask;
use App\Models\OrderTaskPhoto;
use App\OrderStatus;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DeleteOrderTaskTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function actingAsMaster(Master $master): string
    {
        return $master->createToken('mobile')->plainTextToken;
    }

    private function makeTask(Master $master): OrderTask
    {
        $order = Order::factory()->forMaster($master)->inProgress()->create();

        return OrderTask::factory()->create(['order_id' => $order->id]);
    }

    private function deleteUrl(int $orderId, int $taskId): string
    {
        return route('api.v1.master.orders.tasks.destroy', ['order' => $orderId, 'task' => $taskId]);
    }

    // ── Happy path ────────────────────────────────────────────────────────────

    public function test_master_can_delete_own_task(): void
    {
        $master = Master::factory()->create();
        $task = $this->makeTask($master);
        $token = $this->actingAsMaster($master);

        $this->withToken($token)
            ->deleteJson($this->deleteUrl($task->order_id, $task->id))
            ->assertStatus(204);

        $this->assertDatabaseMissing('order_tasks', ['id' => $task->id]);
    }

    public function test_deleting_task_removes_its_photos(): void
    {
        Storage::fake('public');

        $master = Master::factory()->create();
        $task = $this->makeTask($master);
        $token = $this->actingAsMaster($master);

        $photo = $task->photos()->create([
            'type' => 'before',
            'path' => "orders/{$task->order_id}/tasks/{$task->id}/before/photo.jpg",
            'status' => OrderTaskPhoto::STATUS_DONE,
        ]);
        Storage::disk('public')->put($photo->path, 'fake-content');

        $this->withToken($token)
            ->deleteJson($this->deleteUrl($task->order_id, $task->id))
            ->assertStatus(204);

        $this->assertDatabaseMissing('order_tasks', ['id' => $task->id]);
        $this->assertDatabaseMissing('order_task_photos', ['id' => $photo->id]);
        Storage::disk('public')->assertMissing($photo->path);
    }

    // ── Business rules ────────────────────────────────────────────────────────

    public function test_master_cannot_delete_task_once_order_is_completed(): void
    {
        $master = Master::factory()->create();
        $task = $this->makeTask($master);
        $token = $this->actingAsMaster($master);

        $task->order->update(['status' => OrderStatus::Completed]);

        $this->withToken($token)
            ->deleteJson($this->deleteUrl($task->order_id, $task->id))
            ->assertStatus(422)
            ->assertJsonPath('message', 'Tasks can only be deleted while the order is in progress.');

        $this->assertDatabaseHas('order_tasks', ['id' => $task->id]);
    }

    // ── Authorization ─────────────────────────────────────────────────────────

    public function test_another_master_cannot_delete_task(): void
    {
        $master = Master::factory()->create();
        $task = $this->makeTask($master);

        $other = Master::factory()->create();
        $token = $this->actingAsMaster($other);

        $this->withToken($token)
            ->deleteJson($this->deleteUrl($task->order_id, $task->id))
            ->assertStatus(404);

        $this->assertDatabaseHas('order_tasks', ['id' => $task->id]);
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $master = Master::factory()->create();
        $task = $this->makeTask($master);

        $this->deleteJson($this->deleteUrl($task->order_id, $task->id))
            ->assertStatus(401);
    }
}
