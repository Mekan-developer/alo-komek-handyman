<?php

namespace Tests\Feature\Api\V1;

use App\Models\Client;
use App\Models\Master;
use App\Models\Order;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ClientOrderReviewTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function actingAsClient(): Client
    {
        $client = Client::factory()->create();
        Sanctum::actingAs($client, ['*']);

        return $client;
    }

    private function completedOrder(Client $client): Order
    {
        $master = Master::factory()->create();

        return Order::factory()->completed()->create([
            'client_id' => $client->id,
            'master_id' => $master->id,
        ]);
    }

    public function test_client_can_review_completed_order(): void
    {
        $client = $this->actingAsClient();
        $order = $this->completedOrder($client);

        $this->postJson(route('api.v1.client.orders.review', $order), [
            'rating' => 5,
            'comment' => 'Отличный мастер, всё быстро и качественно!',
        ])
            ->assertCreated()
            ->assertJsonPath('data.rating', 5)
            ->assertJsonPath('data.comment', 'Отличный мастер, всё быстро и качественно!');

        $this->assertDatabaseHas('order_reviews', [
            'order_id' => $order->id,
            'master_id' => $order->master_id,
            'client_id' => $client->id,
            'rating' => 5,
        ]);
    }

    public function test_comment_is_optional(): void
    {
        $client = $this->actingAsClient();
        $order = $this->completedOrder($client);

        $this->postJson(route('api.v1.client.orders.review', $order), [
            'rating' => 4,
        ])
            ->assertCreated()
            ->assertJsonPath('data.rating', 4)
            ->assertJsonPath('data.comment', null);
    }

    public function test_rating_is_required(): void
    {
        $client = $this->actingAsClient();
        $order = $this->completedOrder($client);

        $this->postJson(route('api.v1.client.orders.review', $order), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['rating']);
    }

    public function test_rating_must_be_between_1_and_5(): void
    {
        $client = $this->actingAsClient();
        $order = $this->completedOrder($client);

        $this->postJson(route('api.v1.client.orders.review', $order), ['rating' => 6])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['rating']);
    }

    public function test_client_cannot_review_order_that_is_not_completed(): void
    {
        $client = $this->actingAsClient();
        $master = Master::factory()->create();
        $order = Order::factory()->assigned()->create([
            'client_id' => $client->id,
            'master_id' => $master->id,
        ]);

        $this->postJson(route('api.v1.client.orders.review', $order), ['rating' => 5])
            ->assertStatus(422)
            ->assertJsonPath('message', __('orders.errors.not_completed_yet'));

        $this->assertDatabaseCount('order_reviews', 0);
    }

    public function test_client_cannot_review_same_order_twice(): void
    {
        $client = $this->actingAsClient();
        $order = $this->completedOrder($client);

        $this->postJson(route('api.v1.client.orders.review', $order), ['rating' => 5])
            ->assertCreated();

        $this->postJson(route('api.v1.client.orders.review', $order), ['rating' => 3])
            ->assertStatus(422)
            ->assertJsonPath('message', __('orders.errors.already_reviewed'));

        $this->assertDatabaseCount('order_reviews', 1);
    }

    public function test_client_cannot_review_another_clients_order(): void
    {
        $this->actingAsClient();
        $otherClient = Client::factory()->create();
        $order = $this->completedOrder($otherClient);

        $this->postJson(route('api.v1.client.orders.review', $order), ['rating' => 5])
            ->assertNotFound();
    }

    public function test_review_requires_authentication(): void
    {
        $client = Client::factory()->create();
        $order = $this->completedOrder($client);

        $this->postJson(route('api.v1.client.orders.review', $order), ['rating' => 5])
            ->assertUnauthorized();
    }

    public function test_completed_order_shows_review_in_response_once_submitted(): void
    {
        $client = $this->actingAsClient();
        $order = $this->completedOrder($client);

        $this->postJson(route('api.v1.client.orders.review', $order), [
            'rating' => 5,
            'comment' => 'Приехал вовремя, сделал на отлично.',
        ])->assertCreated();

        $this->getJson(route('api.v1.client.orders.show', $order))
            ->assertOk()
            ->assertJsonPath('data.review.rating', 5)
            ->assertJsonPath('data.review.comment', 'Приехал вовремя, сделал на отлично.');
    }
}
