<?php

namespace Tests\Feature\Api\V1;

use App\Models\Master;
use App\Models\Order;
use App\Models\OrderTask;
use App\OrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterOrderCompleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_master_can_complete_order_once_tasks_are_priced(): void
    {
        $master = Master::factory()->create();
        $order = Order::factory()->forMaster($master)->inProgress()->create();
        OrderTask::factory()->for($order)->priced(500)->create();
        $token = $master->createToken('mobile')->plainTextToken;

        $this->withToken($token)
            ->postJson(route('api.v1.master.orders.complete', $order))
            ->assertOk();

        $this->assertEquals(OrderStatus::Completed->value, $order->fresh()->status->value);
    }

    public function test_master_cannot_complete_order_with_no_tasks(): void
    {
        $master = Master::factory()->create();
        $order = Order::factory()->forMaster($master)->inProgress()->create();
        $token = $master->createToken('mobile')->plainTextToken;

        $this->withToken($token)
            ->postJson(route('api.v1.master.orders.complete', $order))
            ->assertStatus(422);

        $this->assertEquals(OrderStatus::InProgress->value, $order->fresh()->status->value);
    }

    public function test_master_cannot_complete_order_with_unpriced_tasks(): void
    {
        $master = Master::factory()->create();
        $order = Order::factory()->forMaster($master)->inProgress()->create();
        OrderTask::factory()->for($order)->priced(500)->create();
        OrderTask::factory()->for($order)->create();
        $token = $master->createToken('mobile')->plainTextToken;

        $this->withToken($token)
            ->postJson(route('api.v1.master.orders.complete', $order))
            ->assertStatus(422);

        $this->assertEquals(OrderStatus::InProgress->value, $order->fresh()->status->value);
    }

    public function test_complete_ignores_final_price_if_sent(): void
    {
        $master = Master::factory()->create();
        $order = Order::factory()->forMaster($master)->inProgress()->create();
        OrderTask::factory()->for($order)->priced(500)->create();
        $token = $master->createToken('mobile')->plainTextToken;

        $this->withToken($token)
            ->postJson(route('api.v1.master.orders.complete', $order), ['final_price' => 999])
            ->assertOk();

        $this->assertEquals(OrderStatus::Completed->value, $order->fresh()->status->value);
        $this->assertEqualsWithDelta(500.0, (float) $order->fresh()->final_price, 0.01);
    }

    public function test_unauthenticated_master_cannot_complete_order(): void
    {
        $master = Master::factory()->create();
        $order = Order::factory()->forMaster($master)->inProgress()->create();

        $this->postJson(route('api.v1.master.orders.complete', $order))
            ->assertUnauthorized();
    }

    public function test_master_cannot_complete_another_masters_order(): void
    {
        $master = Master::factory()->create();
        $otherMaster = Master::factory()->create();
        $order = Order::factory()->forMaster($otherMaster)->inProgress()->create();
        $token = $master->createToken('mobile')->plainTextToken;

        $this->withToken($token)
            ->postJson(route('api.v1.master.orders.complete', $order))
            ->assertNotFound();
    }
}
