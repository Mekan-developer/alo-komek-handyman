<?php

namespace Tests\Feature;

use App\Actions\UploadTaskPhotoAction;
use App\Jobs\ConvertTaskPhotoJob;
use App\Models\Master;
use App\Models\Order;
use App\Models\OrderTask;
use App\Models\OrderTaskPhoto;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UploadTaskPhotoTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function actingAsMaster(Master $master): string
    {
        return $master->createToken('mobile')->plainTextToken;
    }

    private function makeTask(Master $master): OrderTask
    {
        $order = Order::factory()->create(['master_id' => $master->id]);

        return OrderTask::factory()->create(['order_id' => $order->id]);
    }

    private function uploadUrl(int $orderId, int $taskId): string
    {
        return route('api.v1.master.orders.tasks.photo', ['order' => $orderId, 'task' => $taskId]);
    }

    // ── Happy path ────────────────────────────────────────────────────────────

    public function test_master_can_upload_before_photo(): void
    {
        Queue::fake();
        Storage::fake('public');

        $master = Master::factory()->create();
        $task = $this->makeTask($master);
        $token = $this->actingAsMaster($master);

        $response = $this->withToken($token)
            ->postJson($this->uploadUrl($task->order_id, $task->id), [
                'type' => 'before',
                'photo' => UploadedFile::fake()->image('before.jpg'),
            ]);

        $response->assertStatus(202)
            ->assertJsonStructure(['data' => ['id', 'before_photos', 'after_photos']])
            ->assertJsonCount(1, 'data.before_photos')
            ->assertJsonCount(0, 'data.after_photos')
            ->assertJsonPath('data.before_photos.0.status', OrderTaskPhoto::STATUS_PENDING);

        $this->assertDatabaseCount('order_task_photos', 1);
        Queue::assertPushed(ConvertTaskPhotoJob::class, 1);
    }

    public function test_master_can_upload_second_before_photo(): void
    {
        Queue::fake();
        Storage::fake('public');

        $master = Master::factory()->create();
        $task = $this->makeTask($master);
        $token = $this->actingAsMaster($master);

        foreach (range(1, 2) as $i) {
            $this->withToken($token)
                ->postJson($this->uploadUrl($task->order_id, $task->id), [
                    'type' => 'before',
                    'photo' => UploadedFile::fake()->image("before{$i}.jpg"),
                ])->assertStatus(202);
        }

        $this->assertDatabaseCount('order_task_photos', 2);
        Queue::assertPushed(ConvertTaskPhotoJob::class, 2);
    }

    public function test_master_can_upload_two_photos_per_type(): void
    {
        Queue::fake();
        Storage::fake('public');

        $master = Master::factory()->create();
        $task = $this->makeTask($master);
        $token = $this->actingAsMaster($master);

        foreach (['before', 'after'] as $type) {
            foreach (range(1, 2) as $i) {
                $this->withToken($token)
                    ->postJson($this->uploadUrl($task->order_id, $task->id), [
                        'type' => $type,
                        'photo' => UploadedFile::fake()->image("{$type}{$i}.jpg"),
                    ])->assertStatus(202);
            }
        }

        $this->assertDatabaseCount('order_task_photos', 4);
    }

    // ── Limit enforcement ─────────────────────────────────────────────────────

    public function test_third_before_photo_is_rejected(): void
    {
        Queue::fake();
        Storage::fake('public');

        $master = Master::factory()->create();
        $task = $this->makeTask($master);
        $token = $this->actingAsMaster($master);

        foreach (range(1, UploadTaskPhotoAction::MAX_PHOTOS_PER_TYPE) as $i) {
            $this->withToken($token)
                ->postJson($this->uploadUrl($task->order_id, $task->id), [
                    'type' => 'before',
                    'photo' => UploadedFile::fake()->image("before{$i}.jpg"),
                ])->assertStatus(202);
        }

        $this->withToken($token)
            ->postJson($this->uploadUrl($task->order_id, $task->id), [
                'type' => 'before',
                'photo' => UploadedFile::fake()->image('extra.jpg'),
            ])->assertStatus(422)
            ->assertJsonPath('message', 'Maximum 2 before photos allowed per task.');

        $this->assertDatabaseCount('order_task_photos', 2);
    }

    public function test_after_limit_is_independent_from_before(): void
    {
        Queue::fake();
        Storage::fake('public');

        $master = Master::factory()->create();
        $task = $this->makeTask($master);
        $token = $this->actingAsMaster($master);

        foreach (range(1, UploadTaskPhotoAction::MAX_PHOTOS_PER_TYPE) as $i) {
            $this->withToken($token)
                ->postJson($this->uploadUrl($task->order_id, $task->id), [
                    'type' => 'before',
                    'photo' => UploadedFile::fake()->image("before{$i}.jpg"),
                ])->assertStatus(202);
        }

        $this->withToken($token)
            ->postJson($this->uploadUrl($task->order_id, $task->id), [
                'type' => 'after',
                'photo' => UploadedFile::fake()->image('after1.jpg'),
            ])->assertStatus(202);

        $this->assertDatabaseCount('order_task_photos', 3);
    }

    // ── Authorization ─────────────────────────────────────────────────────────

    public function test_another_master_cannot_upload_photo(): void
    {
        Queue::fake();
        Storage::fake('public');

        $master = Master::factory()->create();
        $task = $this->makeTask($master);

        $other = Master::factory()->create();
        $token = $this->actingAsMaster($other);

        $this->withToken($token)
            ->postJson($this->uploadUrl($task->order_id, $task->id), [
                'type' => 'before',
                'photo' => UploadedFile::fake()->image('photo.jpg'),
            ])->assertStatus(404);

        $this->assertDatabaseCount('order_task_photos', 0);
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $master = Master::factory()->create();
        $task = $this->makeTask($master);

        $this->postJson($this->uploadUrl($task->order_id, $task->id), [
            'type' => 'before',
            'photo' => UploadedFile::fake()->image('photo.jpg'),
        ])->assertStatus(401);
    }
}
