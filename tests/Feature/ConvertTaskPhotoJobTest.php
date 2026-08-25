<?php

namespace Tests\Feature;

use App\Events\OrderTaskPhotoUpdated;
use App\Jobs\ConvertTaskPhotoJob;
use App\Models\OrderTaskPhoto;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ConvertTaskPhotoJobTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function storeFakeJpeg(string $relativePath): void
    {
        $absolutePath = Storage::disk('public')->path($relativePath);
        @mkdir(dirname($absolutePath), 0777, true);

        $image = imagecreatetruecolor(400, 300);
        $color = imagecolorallocate($image, 100, 150, 200);
        imagefilledrectangle($image, 0, 0, 399, 299, $color);
        imagejpeg($image, $absolutePath, 90);
        imagedestroy($image);
    }

    public function test_successful_conversion_broadcasts_order_task_photo_updated(): void
    {
        Storage::fake('public');
        Event::fake([OrderTaskPhotoUpdated::class]);

        $photo = OrderTaskPhoto::factory()->before()->pending()->create([
            'path' => 'orders/1/tasks/1/before/original.jpg',
        ]);
        $this->storeFakeJpeg($photo->path);

        (new ConvertTaskPhotoJob($photo->id))->handle();

        $photo->refresh();
        $this->assertSame(OrderTaskPhoto::STATUS_DONE, $photo->status);
        $this->assertStringEndsWith('.webp', $photo->path);

        Event::assertDispatched(OrderTaskPhotoUpdated::class, fn (OrderTaskPhotoUpdated $event) => $event->photo->id === $photo->id
            && $event->photo->status === OrderTaskPhoto::STATUS_DONE);
    }

    public function test_order_task_photo_updated_broadcasts_on_the_public_orders_channel(): void
    {
        $photo = OrderTaskPhoto::factory()->after()->create();

        $event = new OrderTaskPhotoUpdated($photo);

        $this->assertSame('order.task.photo.updated', $event->broadcastAs());
        $this->assertSame([
            'order_id' => $photo->task->order_id,
            'task_id' => $photo->order_task_id,
            'photo_id' => $photo->id,
            'type' => 'after',
            'status' => OrderTaskPhoto::STATUS_DONE,
        ], $event->broadcastWith());
        $this->assertContains('orders', collect($event->broadcastOn())->map->name->all());
    }

    public function test_failed_conversion_does_not_broadcast(): void
    {
        Storage::fake('public');
        Event::fake([OrderTaskPhotoUpdated::class]);

        $photo = OrderTaskPhoto::factory()->before()->pending()->create([
            'path' => 'orders/1/tasks/1/before/missing.jpg',
        ]);

        try {
            (new ConvertTaskPhotoJob($photo->id))->handle();
        } catch (\Throwable $e) {
            // Expected: the source file was never written to the fake disk.
        }

        $photo->refresh();
        $this->assertSame(OrderTaskPhoto::STATUS_FAILED, $photo->status);
        Event::assertNotDispatched(OrderTaskPhotoUpdated::class);
    }
}
