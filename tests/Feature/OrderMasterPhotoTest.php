<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Master;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderMasterPhotoTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_order_page_exposes_master_photo_url(): void
    {
        $this->actingAs(User::factory()->create());
        $master = Master::factory()->create(['photo' => 'masters/test.webp']);
        $order = Order::factory()->forMaster($master)->assigned()->create();

        $this->get(route('orders.show', $order))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('order.master.photo_url', '/storage/masters/test.webp'));
    }

    public function test_admin_order_page_exposes_null_photo_when_master_has_none(): void
    {
        $this->actingAs(User::factory()->create());
        $master = Master::factory()->create(['photo' => null]);
        $order = Order::factory()->forMaster($master)->assigned()->create();

        $this->get(route('orders.show', $order))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('order.master.photo_url', null));
    }

    public function test_client_order_api_exposes_master_photo_url(): void
    {
        $client = Client::factory()->create();
        Sanctum::actingAs($client, ['*']);

        $master = Master::factory()->create(['photo' => 'masters/client-view.webp']);
        $order = Order::factory()->forMaster($master)->assigned()->create([
            'client_id' => $client->id,
        ]);

        $this->getJson(route('api.v1.client.orders.show', $order))
            ->assertOk()
            ->assertJsonPath('data.master.photo_url', url('/storage/masters/client-view.webp'));
    }
}
