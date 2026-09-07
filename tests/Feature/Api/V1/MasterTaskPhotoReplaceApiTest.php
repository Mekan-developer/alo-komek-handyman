<?php

namespace Tests\Feature\Api\V1;

use App\Actions\UploadTaskPhotoAction;
use App\Jobs\ConvertTaskPhotoJob;
use App\Models\Master;
use App\Models\Order;
use App\Models\OrderTask;
use App\Models\OrderTaskPhoto;
use App\OrderStatus;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MasterTaskPhotoReplaceApiTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Queue::fake();
    }

    private function actingAsMaster(Master $master): string
    {
        return $master->createToken('mobile')->plainTextToken;
    }

    /** @param array<string, mixed> $attributes */
    private function makePhoto(Master $master, array $attributes = []): OrderTaskPhoto
    {
        $order = Order::factory()->forMaster($master)->inProgress()->create();
        $task = OrderTask::factory()->create(['order_id' => $order->id]);

        $photo = OrderTaskPhoto::factory()->before()->create([
            'order_task_id' => $task->id,
            'path' => "orders/{$order->id}/tasks/{$task->id}/before/old.webp",
            ...$attributes,
        ]);

        Storage::disk('public')->put($photo->path, 'old-content');

        return $photo;
    }

    private function replaceUrl(int $orderId, int $taskId, int $photoId): string
    {
        return route('api.v1.master.orders.tasks.photo.replace', [
            'order' => $orderId,
            'task' => $taskId,
            'photo' => $photoId,
        ]);
    }

    // ── Happy path ────────────────────────────────────────────────────────────

    public function test_master_can_replace_an_existing_photo(): void
    {
        $master = Master::factory()->create();
        $photo = $this->makePhoto($master);
        $oldPath = $photo->path;

        $this->withToken($this->actingAsMaster($master))
            ->postJson($this->replaceUrl($photo->task->order_id, $photo->order_task_id, $photo->id), [
                'photo' => UploadedFile::fake()->image('reshoot.jpg'),
            ])
            ->assertStatus(202)
            ->assertJsonCount(1, 'data.before_photos')
            ->assertJsonPath('data.before_photos.0.id', $photo->id)
            ->assertJsonPath('data.before_photos.0.status', OrderTaskPhoto::STATUS_PENDING);

        $photo->refresh();

        $this->assertNotSame($oldPath, $photo->path);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($photo->path);
    }

    public function test_replacing_resets_the_status_and_queues_a_conversion(): void
    {
        $master = Master::factory()->create();
        $photo = $this->makePhoto($master);

        $this->withToken($this->actingAsMaster($master))
            ->postJson($this->replaceUrl($photo->task->order_id, $photo->order_task_id, $photo->id), [
                'photo' => UploadedFile::fake()->image('reshoot.jpg'),
            ])
            ->assertStatus(202);

        $this->assertSame(OrderTaskPhoto::STATUS_PENDING, $photo->refresh()->status);

        Queue::assertPushed(
            ConvertTaskPhotoJob::class,
            fn (ConvertTaskPhotoJob $job) => $job->photoId === $photo->id
        );
    }

    public function test_replacing_keeps_the_slot_type_and_does_not_add_a_photo(): void
    {
        $master = Master::factory()->create();
        $photo = $this->makePhoto($master, ['type' => 'after']);

        $this->withToken($this->actingAsMaster($master))
            ->postJson($this->replaceUrl($photo->task->order_id, $photo->order_task_id, $photo->id), [
                'photo' => UploadedFile::fake()->image('reshoot.jpg'),
                'type' => 'before',
            ])
            ->assertStatus(202)
            ->assertJsonCount(0, 'data.before_photos')
            ->assertJsonCount(1, 'data.after_photos');

        $this->assertSame('after', $photo->refresh()->type);
        $this->assertSame(1, OrderTaskPhoto::where('order_task_id', $photo->order_task_id)->count());
    }

    public function test_replacing_frees_no_slot_so_the_per_type_limit_still_holds(): void
    {
        $master = Master::factory()->create();
        $photo = $this->makePhoto($master);
        $task = $photo->task;

        OrderTaskPhoto::factory()->before()->create([
            'order_task_id' => $task->id,
            'path' => "orders/{$task->order_id}/tasks/{$task->id}/before/second.webp",
        ]);

        $token = $this->actingAsMaster($master);

        $this->withToken($token)
            ->postJson($this->replaceUrl($task->order_id, $task->id, $photo->id), [
                'photo' => UploadedFile::fake()->image('reshoot.jpg'),
            ])
            ->assertStatus(202);

        $this->withToken($token)
            ->postJson(route('api.v1.master.orders.tasks.photo', ['order' => $task->order_id, 'task' => $task->id]), [
                'type' => 'before',
                'photo' => UploadedFile::fake()->image('third.jpg'),
            ])
            ->assertStatus(422);

        $this->assertSame(
            UploadTaskPhotoAction::MAX_PHOTOS_PER_TYPE,
            OrderTaskPhoto::where('order_task_id', $task->id)->where('type', 'before')->count()
        );
    }

    // ── Validation ────────────────────────────────────────────────────────────

    public function test_photo_file_is_required(): void
    {
        $master = Master::factory()->create();
        $photo = $this->makePhoto($master);

        $this->withToken($this->actingAsMaster($master))
            ->postJson($this->replaceUrl($photo->task->order_id, $photo->order_task_id, $photo->id), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('photo');
    }

    public function test_non_image_uploads_are_rejected(): void
    {
        $master = Master::factory()->create();
        $photo = $this->makePhoto($master);

        $this->withToken($this->actingAsMaster($master))
            ->postJson($this->replaceUrl($photo->task->order_id, $photo->order_task_id, $photo->id), [
                'photo' => UploadedFile::fake()->create('notes.pdf', 120, 'application/pdf'),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('photo');
    }

    // ── Business rules ────────────────────────────────────────────────────────

    public function test_photo_cannot_be_replaced_once_the_order_is_completed(): void
    {
        $master = Master::factory()->create();
        $photo = $this->makePhoto($master);
        $oldPath = $photo->path;

        $photo->task->order->update(['status' => OrderStatus::Completed]);

        $this->withToken($this->actingAsMaster($master))
            ->postJson($this->replaceUrl($photo->task->order_id, $photo->order_task_id, $photo->id), [
                'photo' => UploadedFile::fake()->image('reshoot.jpg'),
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', __('orders.errors.task_photo_not_editable'));

        $this->assertSame($oldPath, $photo->refresh()->path);
        Storage::disk('public')->assertExists($oldPath);
    }

    // ── Authorization ─────────────────────────────────────────────────────────

    public function test_another_master_cannot_replace_the_photo(): void
    {
        $master = Master::factory()->create();
        $photo = $this->makePhoto($master);
        $other = Master::factory()->create();

        $this->withToken($this->actingAsMaster($other))
            ->postJson($this->replaceUrl($photo->task->order_id, $photo->order_task_id, $photo->id), [
                'photo' => UploadedFile::fake()->image('reshoot.jpg'),
            ])
            ->assertStatus(404);

        Storage::disk('public')->assertExists($photo->path);
    }

    public function test_photo_belonging_to_another_task_is_not_reachable(): void
    {
        $master = Master::factory()->create();
        $photo = $this->makePhoto($master);

        $otherTask = OrderTask::factory()->create(['order_id' => $photo->task->order_id]);

        $this->withToken($this->actingAsMaster($master))
            ->postJson($this->replaceUrl($photo->task->order_id, $otherTask->id, $photo->id), [
                'photo' => UploadedFile::fake()->image('reshoot.jpg'),
            ])
            ->assertStatus(404);
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $master = Master::factory()->create();
        $photo = $this->makePhoto($master);

        $this->postJson($this->replaceUrl($photo->task->order_id, $photo->order_task_id, $photo->id), [
            'photo' => UploadedFile::fake()->image('reshoot.jpg'),
        ])->assertStatus(401);
    }
}
