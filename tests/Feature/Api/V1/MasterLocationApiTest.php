<?php

namespace Tests\Feature\Api\V1;

use App\Events\MasterLocationUpdated;
use App\Models\Master;
use App\Models\Order;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class MasterLocationApiTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_active_master_can_post_location(): void
    {
        Event::fake([MasterLocationUpdated::class]);
        $master = Master::factory()->create();

        $response = $this->postJson(route('api.v1.master.location.store', $master->id), [
            'latitude' => 37.952,
            'longitude' => 58.382,
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['data' => ['id', 'master_id', 'latitude', 'longitude', 'recorded_at']]);

        $this->assertDatabaseHas('master_locations', [
            'master_id' => $master->id,
            'latitude' => 37.9520000,
            'longitude' => 58.3820000,
        ]);

        Event::assertDispatched(MasterLocationUpdated::class);
    }

    public function test_inactive_master_cannot_post_location(): void
    {
        $master = Master::factory()->inactive()->create();

        $this->postJson(route('api.v1.master.location.store', $master->id), [
            'latitude' => 37.95,
            'longitude' => 58.38,
        ])->assertForbidden();
    }

    public function test_master_with_expired_access_cannot_post_location(): void
    {
        $master = Master::factory()->expired()->create();

        $this->postJson(route('api.v1.master.location.store', $master->id), [
            'latitude' => 37.95,
            'longitude' => 58.38,
        ])->assertForbidden();
    }

    public function test_unknown_master_returns_404(): void
    {
        $this->postJson(route('api.v1.master.location.store', 9999), [
            'latitude' => 37.95,
            'longitude' => 58.38,
        ])->assertNotFound();
    }

    public function test_location_post_validates_coordinates(): void
    {
        $master = Master::factory()->create();

        $this->postJson(route('api.v1.master.location.store', $master->id), [
            'latitude' => 999,
            'longitude' => 'not-a-number',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['latitude', 'longitude']);
    }

    public function test_location_can_be_attached_to_order(): void
    {
        $master = Master::factory()->create();
        $order = Order::factory()->create();

        $this->postJson(route('api.v1.master.location.store', $master->id), [
            'latitude' => 37.95,
            'longitude' => 58.38,
            'order_id' => $order->id,
        ])->assertCreated();

        $this->assertDatabaseHas('master_locations', [
            'master_id' => $master->id,
            'order_id' => $order->id,
        ]);
    }

    public function test_event_carries_correct_payload(): void
    {
        Event::fake([MasterLocationUpdated::class]);
        $master = Master::factory()->create();

        $this->postJson(route('api.v1.master.location.store', $master->id), [
            'latitude' => 37.95,
            'longitude' => 58.38,
        ])->assertCreated();

        Event::assertDispatched(MasterLocationUpdated::class, function ($event) use ($master) {
            return $event->location->master_id === $master->id
                && (float) $event->location->latitude === 37.95;
        });
    }

    public function test_event_broadcasts_on_correct_city_channel(): void
    {
        $master = Master::factory()->create();
        $location = $master->locations()->create([
            'latitude' => 37.95,
            'longitude' => 58.38,
            'recorded_at' => now(),
        ]);

        $event = new MasterLocationUpdated($location);
        $channels = $event->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertEquals('masters-map.'.$master->city_id, $channels[0]->name);
    }

    public function test_event_broadcast_payload_shape(): void
    {
        $master = Master::factory()->create();
        $location = $master->locations()->create([
            'latitude' => 37.95,
            'longitude' => 58.38,
            'recorded_at' => now(),
        ]);

        $payload = (new MasterLocationUpdated($location))->broadcastWith();

        $this->assertEquals([
            'master_id', 'order_id', 'latitude', 'longitude', 'recorded_at',
        ], array_keys($payload));
        $this->assertEquals('master.location.updated', (new MasterLocationUpdated($location))->broadcastAs());
    }
}
