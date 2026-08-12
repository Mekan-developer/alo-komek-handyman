<?php

namespace Tests\Feature\Api\V1;

use App\Models\Client;
use App\Models\Master;
use App\Models\Order;
use App\Models\OrderTask;
use App\Repositories\OrderRepository;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderTaskPriceApiTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_master_order_exposes_task_prices_and_total(): void
    {
        $master = Master::factory()->create();
        $order = Order::factory()->forMaster($master)->inProgress()->create();
        OrderTask::factory()->priced(300)->create(['order_id' => $order->id, 'title' => 'Замена смесителя']);
        OrderTask::factory()->priced(150.25)->create(['order_id' => $order->id]);

        $token = $master->createToken('mobile')->plainTextToken;

        $this->withToken($token)
            ->getJson(route('api.v1.master.orders.show', $order))
            ->assertOk()
            ->assertJsonPath('data.tasks.0.title', 'Замена смесителя')
            ->assertJsonPath('data.tasks.0.price', 300)
            ->assertJsonPath('data.tasks.1.price', 150.25)
            ->assertJsonPath('data.final_price', 450.25);
    }

    public function test_master_order_exposes_the_discount_and_the_pre_discount_total(): void
    {
        $master = Master::factory()->create();
        $order = Order::factory()->forMaster($master)->inProgress()->create(['discount_percent' => 10]);
        OrderTask::factory()->priced(300)->create(['order_id' => $order->id]);
        OrderTask::factory()->priced(200)->create(['order_id' => $order->id]);
        $order->refresh()->load('tasks');

        app(OrderRepository::class)->syncFinalPriceFromTasks($order);

        $token = $master->createToken('mobile')->plainTextToken;

        $this->withToken($token)
            ->getJson(route('api.v1.master.orders.show', $order))
            ->assertOk()
            ->assertJsonPath('data.tasks_total', 500)
            ->assertJsonPath('data.discount_percent', 10)
            ->assertJsonPath('data.final_price', 450)
            ->assertJsonPath('data.tasks.0.price', 300)
            ->assertJsonPath('data.tasks.1.price', 200);
    }

    public function test_client_order_exposes_the_discount_breakdown(): void
    {
        $client = Client::factory()->create();
        Sanctum::actingAs($client, ['*']);

        $master = Master::factory()->create();
        $order = Order::factory()->forMaster($master)->inProgress()->create([
            'client_id' => $client->id,
            'discount_percent' => 20,
        ]);
        OrderTask::factory()->priced(1000)->create(['order_id' => $order->id]);

        app(OrderRepository::class)->syncFinalPriceFromTasks($order->refresh());

        $this->getJson(route('api.v1.client.orders.show', $order))
            ->assertOk()
            ->assertJsonPath('data.tasks.0.price', 1000)
            ->assertJsonPath('data.tasks_total', 1000)
            ->assertJsonPath('data.discount_percent', 20)
            ->assertJsonPath('data.discount_amount', 200)
            ->assertJsonPath('data.final_price', 800);
    }

    public function test_master_order_returns_null_price_for_unpriced_task(): void
    {
        $master = Master::factory()->create();
        $order = Order::factory()->forMaster($master)->inProgress()->create();
        OrderTask::factory()->create(['order_id' => $order->id]);

        $token = $master->createToken('mobile')->plainTextToken;

        $this->withToken($token)
            ->getJson(route('api.v1.master.orders.show', $order))
            ->assertOk()
            ->assertJsonPath('data.tasks.0.price', null)
            ->assertJsonPath('data.final_price', null);
    }

    public function test_client_order_exposes_task_prices(): void
    {
        $client = Client::factory()->create();
        Sanctum::actingAs($client, ['*']);

        $master = Master::factory()->create();
        $order = Order::factory()->forMaster($master)->inProgress()->create(['client_id' => $client->id]);
        OrderTask::factory()->priced(275.50)->create(['order_id' => $order->id]);

        $this->getJson(route('api.v1.client.orders.show', $order))
            ->assertOk()
            ->assertJsonPath('data.tasks.0.price', 275.50)
            ->assertJsonPath('data.final_price', 275.50);
    }

    public function test_client_order_returns_final_price_as_a_number(): void
    {
        $client = Client::factory()->create();
        Sanctum::actingAs($client, ['*']);

        $order = Order::factory()->create(['client_id' => $client->id]);
        OrderTask::factory()->priced(500)->create(['order_id' => $order->id]);

        app(OrderRepository::class)->syncFinalPriceFromTasks($order->refresh());

        $response = $this->getJson(route('api.v1.client.orders.show', $order))->assertOk();

        // Whole amounts serialise as `500`, not `500.0` — the guarantee is "number, never string".
        $this->assertIsNumeric($response->json('data.final_price'));
        $this->assertIsNotString($response->json('data.final_price'));
    }

    public function test_starting_an_order_returns_the_tasks_and_their_total(): void
    {
        $master = Master::factory()->create();
        $order = Order::factory()->forMaster($master)->assigned()->create();
        OrderTask::factory()->priced(300)->create(['order_id' => $order->id]);

        $token = $master->createToken('mobile')->plainTextToken;

        $this->withToken($token)
            ->postJson(route('api.v1.master.orders.start', $order))
            ->assertOk()
            ->assertJsonPath('data.tasks_total', 300)
            ->assertJsonPath('data.tasks.0.price', 300);
    }

    public function test_completing_an_order_returns_the_tasks_and_their_total(): void
    {
        $master = Master::factory()->create();
        $order = Order::factory()->forMaster($master)->inProgress()->create();
        OrderTask::factory()->priced(300)->create(['order_id' => $order->id]);

        $token = $master->createToken('mobile')->plainTextToken;

        $this->withToken($token)
            ->postJson(route('api.v1.master.orders.complete', $order))
            ->assertOk()
            ->assertJsonPath('data.tasks_total', 300)
            ->assertJsonPath('data.tasks.0.price', 300);
    }
}
