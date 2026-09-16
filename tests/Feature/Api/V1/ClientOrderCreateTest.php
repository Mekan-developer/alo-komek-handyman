<?php

namespace Tests\Feature\Api\V1;

use App\Models\Category;
use App\Models\Client;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ClientOrderCreateTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function actingAsClient(): Client
    {
        $client = Client::factory()->create();
        Sanctum::actingAs($client, ['*']);

        return $client;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(Category $category, array $overrides = []): array
    {
        return array_merge([
            'category_id' => $category->id,
            'description' => 'Кран в ванной течёт, нужна замена прокладки.',
            'client_phone' => '+99361138385',
            'client_address' => 'ул. Тестовая, 1',
            'client_lat' => 37.956783,
            'client_lng' => 58.4265174,
            'preferred_date' => now()->toDateString(),
            'time_slot' => '18-20',
            'is_urgent' => false,
        ], $overrides);
    }

    public function test_client_can_create_scheduled_order(): void
    {
        $client = $this->actingAsClient();
        $category = Category::factory()->create();

        $this->post(route('api.v1.client.orders.store'), $this->validPayload($category, [
            'is_urgent' => '0',
            'preferred_date' => now()->toDateString(),
            'time_slot' => '18-20',
        ]))
            ->assertCreated()
            ->assertJsonPath('data.is_urgent', false)
            ->assertJsonPath('data.preferred_date', now()->toDateString())
            ->assertJsonPath('data.time_slot', '18-20')
            ->assertJsonPath('data.urgency_fee', null);

        $this->assertDatabaseHas('orders', [
            'client_id' => $client->id,
            'category_id' => $category->id,
            'is_urgent' => false,
            'time_slot' => '18-20',
        ]);
    }

    public function test_client_can_create_urgent_order_from_multipart_string(): void
    {
        Setting::create(['key' => 'order_urgency_fee', 'value' => '20']);
        $client = $this->actingAsClient();
        $category = Category::factory()->create();

        $this->post(route('api.v1.client.orders.store'), $this->validPayload($category, [
            'is_urgent' => '1',
            'preferred_date' => now()->toDateString(),
            'time_slot' => '18-20',
        ]))
            ->assertCreated()
            ->assertJsonPath('data.is_urgent', true)
            ->assertJsonPath('data.preferred_date', null)
            ->assertJsonPath('data.time_slot', null)
            ->assertJsonPath('data.urgency_fee', 20);

        $order = Order::query()->where('client_id', $client->id)->firstOrFail();

        $this->assertTrue($order->is_urgent);
        $this->assertEquals(20.0, (float) $order->urgency_fee);
        $this->assertNull($order->preferred_date);
        $this->assertNull($order->time_slot);
    }

    public function test_urgent_order_does_not_require_preferred_date(): void
    {
        Setting::create(['key' => 'order_urgency_fee', 'value' => '25']);
        $this->actingAsClient();
        $category = Category::factory()->create();

        $payload = $this->validPayload($category, ['is_urgent' => true]);
        unset($payload['preferred_date'], $payload['time_slot']);

        $this->post(route('api.v1.client.orders.store'), $payload)
            ->assertCreated()
            ->assertJsonPath('data.is_urgent', true)
            ->assertJsonPath('data.urgency_fee', 25);
    }
}
