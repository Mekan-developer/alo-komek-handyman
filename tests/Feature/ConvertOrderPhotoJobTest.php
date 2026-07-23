<?php

namespace Tests\Feature;

use App\Jobs\ConvertOrderPhotoJob;
use App\Models\Category;
use App\Models\City;
use App\Models\Order;
use App\Models\OrderPhoto;
use App\Models\User;
use App\OrderStatus;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ConvertOrderPhotoJobTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_job_is_dispatched_when_order_with_photos_is_created(): void
    {
        Queue::fake();
        Storage::fake('public');

        $user = User::factory()->create();
        $this->actingAs($user);

        $city = City::factory()->create();
        $category = Category::factory()->create();

        $this->post(route('orders.store'), [
            'city_id' => $city->id,
            'category_id' => $category->id,
            'client_name' => 'Test Client',
            'client_phone' => '+99361000001',
            'description' => 'Broken pipe',
            'client_address' => '123 Main St',
            'client_lat' => 37.95,
            'client_lng' => 58.38,
            'photos' => [UploadedFile::fake()->image('problem.jpg', 800, 600)],
        ]);

        Queue::assertPushed(ConvertOrderPhotoJob::class, 1);
    }

    public function test_job_sets_status_to_done_and_saves_webp_path(): void
    {
        Storage::fake('public');

        $image = imagecreatetruecolor(400, 300);
        $color = imagecolorallocate($image, 80, 120, 160);
        imagefilledrectangle($image, 0, 0, 399, 299, $color);

        $jpegPath = sys_get_temp_dir().'/test_job_photo.jpg';
        imagejpeg($image, $jpegPath, 90);
        imagedestroy($image);

        $relativePath = 'orders/1/problem/test_job_photo.jpg';
        Storage::disk('public')->put($relativePath, file_get_contents($jpegPath));
        unlink($jpegPath);

        $city = City::factory()->create();
        $order = Order::factory()->create([
            'city_id' => $city->id,
            'status' => OrderStatus::Pending,
        ]);

        $photo = OrderPhoto::factory()->create([
            'order_id' => $order->id,
            'path' => $relativePath,
            'status' => OrderPhoto::STATUS_PENDING,
        ]);

        (new ConvertOrderPhotoJob($photo->id))->handle();

        $photo->refresh();

        $this->assertEquals(OrderPhoto::STATUS_DONE, $photo->status);
        $this->assertStringEndsWith('.webp', $photo->path);
        Storage::disk('public')->assertExists($photo->path);
        Storage::disk('public')->assertMissing($relativePath);
    }

    public function test_job_skips_already_done_photo(): void
    {
        Storage::fake('public');

        $city = City::factory()->create();
        $order = Order::factory()->create([
            'city_id' => $city->id,
            'status' => OrderStatus::Pending,
        ]);

        $photo = OrderPhoto::factory()->create([
            'order_id' => $order->id,
            'path' => 'orders/1/done.webp',
            'status' => OrderPhoto::STATUS_DONE,
        ]);

        (new ConvertOrderPhotoJob($photo->id))->handle();

        $photo->refresh();
        $this->assertEquals(OrderPhoto::STATUS_DONE, $photo->status);
    }
}
