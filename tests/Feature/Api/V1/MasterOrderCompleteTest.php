<?php

namespace Tests\Feature\Api\V1;

use App\Models\Master;
use App\Models\Order;
use App\OrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterOrderCompleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_master_can_complete_order_without_final_price(): void
    {
        $master = Master::factory()->create();
        $order = Order::factory()->forMaster($master)->inProgress()->create();
        $token = $master->createToken('mobile')->plainTextToken;

        $this->withToken($token)
            ->postJson(route('api.v1.master.orders.complete', $order))
            ->assertOk();

        $this->assertEquals(OrderStatus::Completed->value, $order->fresh()->status->value);
    }

    public function test_complete_ignores_final_price_if_sent(): void
    {
        $master = Master::factory()->create();
        $order = Order::factory()->forMaster($master)->inProgress()->create(['final_price' => null]);
        $token = $master->createToken('mobile')->plainTextToken;

        $this->withToken($token)
            ->postJson(route('api.v1.master.orders.complete', $order), ['final_price' => 999])
            ->assertOk();

        $this->assertEquals(OrderStatus::Completed->value, $order->fresh()->status->value);
        $this->assertNull($order->fresh()->final_price);
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
