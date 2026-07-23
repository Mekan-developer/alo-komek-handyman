<?php

namespace Tests\Feature\Api\V1;

use App\Jobs\ConvertOrderPhotoJob;
use App\Models\Category;
use App\Models\City;
use App\Models\Client;
use App\Models\Master;
use App\Models\Order;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ClientOrderUpdateTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function actingAsClient(): Client
    {
        $client = Client::factory()->create();
        Sanctum::actingAs($client, ['*']);

        return $client;
    }

    public function test_client_can_update_own_pending_order(): void
    {
        $client = $this->actingAsClient();
        $order = Order::factory()->create(['client_id' => $client->id]);
        $city = City::factory()->create();
        $category = Category::factory()->create();

        $this->patchJson(route('api.v1.client.orders.update', $order), [
            'city_id' => $city->id,
            'category_id' => $category->id,
            'description' => 'Обновлённое описание проблемы с краном.',
            'client_phone' => '+99362000000',
            'client_address' => 'ул. Новая, д. 1',
            'client_lat' => 37.95,
            'client_lng' => 58.38,
        ])
            ->assertOk()
            ->assertJsonPath('data.id', $order->id)
            ->assertJsonPath('data.description', 'Обновлённое описание проблемы с краном.');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'city_id' => $city->id,
            'category_id' => $category->id,
            'description' => 'Обновлённое описание проблемы с краном.',
            'client_phone' => '+99362000000',
            'client_address' => 'ул. Новая, д. 1',
        ]);
    }

    public function test_update_accepts_partial_fields(): void
    {
        $client = $this->actingAsClient();
        $order = Order::factory()->create([
            'client_id' => $client->id,
            'description' => 'Исходное описание',
        ]);

        $this->patchJson(route('api.v1.client.orders.update', $order), [
            'description' => 'Только описание изменилось',
        ])
            ->assertOk()
            ->assertJsonPath('data.description', 'Только описание изменилось');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'city_id' => $order->city_id,
            'description' => 'Только описание изменилось',
        ]);
    }

    public function test_client_cannot_update_order_once_master_assigned(): void
    {
        $client = $this->actingAsClient();
        $master = Master::factory()->create();
        $order = Order::factory()->assigned()->create([
            'client_id' => $client->id,
            'master_id' => $master->id,
        ]);

        $this->patchJson(route('api.v1.client.orders.update', $order), [
            'description' => 'Попытка изменить назначенную заявку',
        ])
            ->assertStatus(422)
            ->assertJsonPath('message', __('orders.errors.not_editable'));

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'description' => $order->description,
        ]);
    }

    public function test_client_cannot_update_another_clients_order(): void
    {
        $this->actingAsClient();
        $order = Order::factory()->create(['client_id' => Client::factory()->create()->id]);

        $this->patchJson(route('api.v1.client.orders.update', $order), [
            'description' => 'Чужая заявка',
        ])
            ->assertNotFound();
    }

    public function test_update_validates_fields(): void
    {
        $client = $this->actingAsClient();
        $order = Order::factory()->create(['client_id' => $client->id]);

        $this->patchJson(route('api.v1.client.orders.update', $order), [
            'city_id' => 999999,
            'client_lat' => 200,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['city_id', 'client_lat']);
    }

    public function test_client_can_add_photos_when_updating_order(): void
    {
        Queue::fake();
        Storage::fake('public');

        $client = $this->actingAsClient();
        $order = Order::factory()->create(['client_id' => $client->id]);

        $this->patch(route('api.v1.client.orders.update', $order), [
            'photos' => [UploadedFile::fake()->image('problem.jpg', 800, 600)],
        ])
            ->assertOk()
            ->assertJsonCount(1, 'data.photos');

        $this->assertDatabaseCount('order_photos', 1);
        Queue::assertPushed(ConvertOrderPhotoJob::class, 1);
    }

    public function test_client_can_remove_existing_photo_when_updating_order(): void
    {
        Storage::fake('public');

        $client = $this->actingAsClient();
        $order = Order::factory()->create(['client_id' => $client->id]);
        $photo = $order->photos()->create([
            'path' => 'orders/1/problem/existing.jpg',
            'original_name' => 'existing.jpg',
            'status' => 'done',
        ]);
        Storage::disk('public')->put($photo->path, 'fake-content');

        $this->patchJson(route('api.v1.client.orders.update', $order), [
            'remove_photo_ids' => [$photo->id],
        ])
            ->assertOk()
            ->assertJsonCount(0, 'data.photos');

        $this->assertDatabaseMissing('order_photos', ['id' => $photo->id]);
        Storage::disk('public')->assertMissing($photo->path);
    }

    public function test_update_enforces_max_four_photos_total(): void
    {
        Queue::fake();
        Storage::fake('public');

        $client = $this->actingAsClient();
        $order = Order::factory()->create(['client_id' => $client->id]);

        foreach (range(1, 3) as $i) {
            $order->photos()->create([
                'path' => "orders/{$order->id}/problem/existing-{$i}.jpg",
                'original_name' => "existing-{$i}.jpg",
                'status' => 'done',
            ]);
        }

        $this->patch(route('api.v1.client.orders.update', $order), [
            'photos' => [
                UploadedFile::fake()->image('new1.jpg'),
                UploadedFile::fake()->image('new2.jpg'),
            ],
        ])
            ->assertStatus(422)
            ->assertJsonPath('message', __('orders.errors.too_many_photos'));

        $this->assertDatabaseCount('order_photos', 3);
    }

    public function test_remove_photo_ids_are_scoped_to_own_order(): void
    {
        Storage::fake('public');

        $client = $this->actingAsClient();
        $order = Order::factory()->create(['client_id' => $client->id]);
        $otherOrder = Order::factory()->create();
        $foreignPhoto = $otherOrder->photos()->create([
            'path' => 'orders/other/problem/foreign.jpg',
            'original_name' => 'foreign.jpg',
            'status' => 'done',
        ]);

        $this->patchJson(route('api.v1.client.orders.update', $order), [
            'remove_photo_ids' => [$foreignPhoto->id],
        ])->assertOk();

        $this->assertDatabaseHas('order_photos', ['id' => $foreignPhoto->id]);
    }

    public function test_update_requires_authentication(): void
    {
        $order = Order::factory()->create();

        $this->patchJson(route('api.v1.client.orders.update', $order), [
            'description' => 'Без авторизации',
        ])
            ->assertUnauthorized();
    }
}
