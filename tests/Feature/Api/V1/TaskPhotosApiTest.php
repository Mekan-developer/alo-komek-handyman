<?php

namespace Tests\Feature\Api\V1;

use App\Models\Client;
use App\Models\Master;
use App\Models\Order;
use App\Models\OrderTask;
use App\Models\OrderTaskPhoto;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaskPhotosApiTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function orderWithPhotographedTask(Master $master, ?Client $client = null): OrderTask
    {
        $order = Order::factory()->forMaster($master)->inProgress()->create([
            'client_id' => $client?->id,
        ]);

        $task = OrderTask::factory()->priced(300)->create(['order_id' => $order->id]);

        OrderTaskPhoto::factory()->before()->create([
            'order_task_id' => $task->id,
            'path' => 'orders/1/tasks/1/before/one.webp',
        ]);
        OrderTaskPhoto::factory()->after()->pending()->create([
            'order_task_id' => $task->id,
            'path' => 'orders/1/tasks/1/after/one.webp',
        ]);

        return $task;
    }

    // ── Master API ────────────────────────────────────────────────────────────

    public function test_master_order_exposes_task_photos(): void
    {
        $master = Master::factory()->create();
        $task = $this->orderWithPhotographedTask($master);

        $this->withToken($master->createToken('mobile')->plainTextToken)
            ->getJson(route('api.v1.master.orders.show', $task->order_id))
            ->assertOk()
            ->assertJsonCount(1, 'data.tasks.0.before_photos')
            ->assertJsonCount(1, 'data.tasks.0.after_photos')
            ->assertJsonPath('data.tasks.0.before_photos.0.status', OrderTaskPhoto::STATUS_DONE)
            ->assertJsonPath(
                'data.tasks.0.before_photos.0.url',
                asset('storage/orders/1/tasks/1/before/one.webp')
            )
            ->assertJsonPath('data.tasks.0.after_photos.0.status', OrderTaskPhoto::STATUS_PENDING);
    }

    public function test_master_order_returns_empty_photo_arrays_for_a_task_without_photos(): void
    {
        $master = Master::factory()->create();
        $order = Order::factory()->forMaster($master)->inProgress()->create();
        OrderTask::factory()->create(['order_id' => $order->id]);

        $this->withToken($master->createToken('mobile')->plainTextToken)
            ->getJson(route('api.v1.master.orders.show', $order))
            ->assertOk()
            ->assertJsonPath('data.tasks.0.before_photos', [])
            ->assertJsonPath('data.tasks.0.after_photos', []);
    }

    // ── Client API ────────────────────────────────────────────────────────────

    public function test_client_order_exposes_the_same_task_photo_shape_as_the_master_api(): void
    {
        $client = Client::factory()->create();
        Sanctum::actingAs($client, ['*']);

        $master = Master::factory()->create();
        $task = $this->orderWithPhotographedTask($master, $client);

        $this->getJson(route('api.v1.client.orders.show', $task->order_id))
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'tasks' => [
                        ['id', 'title', 'description', 'price', 'before_photos', 'after_photos'],
                    ],
                ],
            ])
            ->assertJsonCount(1, 'data.tasks.0.before_photos')
            ->assertJsonCount(1, 'data.tasks.0.after_photos')
            ->assertJsonPath('data.tasks.0.price', 300)
            ->assertJsonPath(
                'data.tasks.0.before_photos.0.url',
                asset('storage/orders/1/tasks/1/before/one.webp')
            )
            ->assertJsonPath('data.tasks.0.after_photos.0.status', OrderTaskPhoto::STATUS_PENDING);
    }

    public function test_client_order_no_longer_exposes_the_legacy_single_photo_fields(): void
    {
        $client = Client::factory()->create();
        Sanctum::actingAs($client, ['*']);

        $master = Master::factory()->create();
        $task = $this->orderWithPhotographedTask($master, $client);

        $this->getJson(route('api.v1.client.orders.show', $task->order_id))
            ->assertOk()
            ->assertJsonMissingPath('data.tasks.0.before_photo_url')
            ->assertJsonMissingPath('data.tasks.0.after_photo_url')
            ->assertJsonMissingPath('data.tasks.0.before_status')
            ->assertJsonMissingPath('data.tasks.0.after_status');
    }

    public function test_client_order_returns_empty_photo_arrays_for_a_task_without_photos(): void
    {
        $client = Client::factory()->create();
        Sanctum::actingAs($client, ['*']);

        $order = Order::factory()->create(['client_id' => $client->id]);
        OrderTask::factory()->create(['order_id' => $order->id]);

        $this->getJson(route('api.v1.client.orders.show', $order))
            ->assertOk()
            ->assertJsonPath('data.tasks.0.before_photos', [])
            ->assertJsonPath('data.tasks.0.after_photos', []);
    }
}
