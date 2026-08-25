<?php

namespace Tests\Feature\Api\V1;

use App\Actions\UpdateOrderStatusAction;
use App\Models\Client;
use App\Models\Master;
use App\Models\Order;
use App\Models\OrderTask;
use App\OrderStatus;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderReceiptApiTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * Завершённый заказ клиента с двумя оплаченными задачами: 30 + 70 = 100.
     */
    private function completedOrder(?Client $client = null, ?Master $master = null): Order
    {
        $master ??= Master::factory()->create(['name' => 'Gurban Azadow']);

        $order = Order::factory()
            ->forMaster($master)
            ->inProgress()
            ->create([
                'client_id' => $client?->id,
                'client_name' => 'Emil',
            ]);

        OrderTask::factory()->priced(30)->create(['order_id' => $order->id, 'title' => 'Замена смесителя']);
        OrderTask::factory()->priced(70)->create(['order_id' => $order->id, 'title' => 'Прочистка сифона']);

        return app(UpdateOrderStatusAction::class)->handle($order->fresh(), OrderStatus::Completed);
    }

    // ── Клиент ────────────────────────────────────────────────────────────────

    public function test_client_can_fetch_the_receipt_of_a_completed_order(): void
    {
        $client = Client::factory()->create();
        Sanctum::actingAs($client, ['*']);
        $order = $this->completedOrder($client);

        $this->getJson(route('api.v1.client.orders.receipt', $order))
            ->assertOk()
            ->assertJsonPath('data.order_id', $order->id)
            ->assertJsonPath('data.client_name', 'Emil')
            ->assertJsonPath('data.master_name', 'Gurban Azadow')
            ->assertJsonPath('data.total', 100)
            ->assertJsonPath('data.currency', 'TMT')
            ->assertJsonCount(2, 'data.items');
    }

    public function test_client_receipt_returns_404_while_the_order_is_not_completed(): void
    {
        $client = Client::factory()->create();
        Sanctum::actingAs($client, ['*']);
        $order = Order::factory()->inProgress()->create(['client_id' => $client->id]);

        $this->getJson(route('api.v1.client.orders.receipt', $order))->assertNotFound();
    }

    public function test_client_cannot_fetch_another_clients_receipt(): void
    {
        Sanctum::actingAs(Client::factory()->create(), ['*']);
        $order = $this->completedOrder(Client::factory()->create());

        $this->getJson(route('api.v1.client.orders.receipt', $order))->assertNotFound();
    }

    public function test_unauthenticated_client_cannot_fetch_a_receipt(): void
    {
        $order = $this->completedOrder(Client::factory()->create());

        $this->getJson(route('api.v1.client.orders.receipt', $order))->assertUnauthorized();
    }

    // ── Мастер ────────────────────────────────────────────────────────────────

    public function test_master_can_fetch_the_receipt_of_his_completed_order(): void
    {
        $master = Master::factory()->create(['name' => 'Gurban Azadow']);
        $order = $this->completedOrder(master: $master);
        $token = $master->createToken('mobile')->plainTextToken;

        $this->withToken($token)
            ->getJson(route('api.v1.master.orders.receipt', $order))
            ->assertOk()
            ->assertJsonPath('data.master_name', 'Gurban Azadow')
            ->assertJsonPath('data.total', 100)
            ->assertJsonCount(2, 'data.items');
    }

    public function test_master_cannot_fetch_another_masters_receipt(): void
    {
        $master = Master::factory()->create();
        $order = $this->completedOrder(master: Master::factory()->create());
        $token = $master->createToken('mobile')->plainTextToken;

        $this->withToken($token)
            ->getJson(route('api.v1.master.orders.receipt', $order))
            ->assertNotFound();
    }

    public function test_unauthenticated_master_cannot_fetch_a_receipt(): void
    {
        $order = $this->completedOrder();

        $this->getJson(route('api.v1.master.orders.receipt', $order))->assertUnauthorized();
    }
}
