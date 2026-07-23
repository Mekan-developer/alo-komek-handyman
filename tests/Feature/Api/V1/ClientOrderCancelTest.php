<?php

namespace Tests\Feature\Api\V1;

use App\Models\Client;
use App\Models\Master;
use App\Models\Order;
use App\OrderStatus;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ClientOrderCancelTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function actingAsClient(): Client
    {
        $client = Client::factory()->create();
        Sanctum::actingAs($client, ['*']);

        return $client;
    }

    public function test_client_can_cancel_own_pending_order(): void
    {
        $client = $this->actingAsClient();
        $order = Order::factory()->create(['client_id' => $client->id]);

        $this->postJson(route('api.v1.client.orders.cancel', $order), [
            'reason' => 'Проблема решилась сама',
        ])
            ->assertOk()
            ->assertJsonPath('data.status', OrderStatus::Cancelled->value);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::Cancelled->value,
            'cancel_reason' => 'Проблема решилась сама',
        ]);
        $this->assertNotNull($order->fresh()->cancelled_at);
    }

    public function test_reason_is_optional(): void
    {
        $client = $this->actingAsClient();
        $order = Order::factory()->create(['client_id' => $client->id]);

        $this->postJson(route('api.v1.client.orders.cancel', $order))
            ->assertOk()
            ->assertJsonPath('data.status', OrderStatus::Cancelled->value);
    }

    public function test_client_cannot_cancel_order_with_assigned_master(): void
    {
        $client = $this->actingAsClient();
        $master = Master::factory()->create();
        $order = Order::factory()->assigned()->create([
            'client_id' => $client->id,
            'master_id' => $master->id,
        ]);

        $this->postJson(route('api.v1.client.orders.cancel', $order))
            ->assertStatus(422)
            ->assertJsonPath('message', __('orders.errors.cannot_cancel_assigned'));

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::Assigned->value,
        ]);
    }

    public function test_client_cannot_cancel_already_final_order(): void
    {
        $client = $this->actingAsClient();
        $order = Order::factory()->cancelled()->create(['client_id' => $client->id]);

        $this->postJson(route('api.v1.client.orders.cancel', $order))
            ->assertStatus(422)
            ->assertJsonPath('message', __('orders.errors.already_final'));
    }

    public function test_client_cannot_cancel_another_clients_order(): void
    {
        $this->actingAsClient();
        $order = Order::factory()->create(['client_id' => Client::factory()->create()->id]);

        $this->postJson(route('api.v1.client.orders.cancel', $order))
            ->assertNotFound();
    }

    public function test_cancel_requires_authentication(): void
    {
        $order = Order::factory()->create();

        $this->postJson(route('api.v1.client.orders.cancel', $order))
            ->assertUnauthorized();
    }
}
