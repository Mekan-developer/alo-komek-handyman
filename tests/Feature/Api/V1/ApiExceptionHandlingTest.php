<?php

namespace Tests\Feature\Api\V1;

use App\Models\City;
use App\Models\Client;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiExceptionHandlingTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_unauthenticated_request_returns_localized_401(): void
    {
        $this->getJson(route('api.v1.client.me'))
            ->assertStatus(401)
            ->assertExactJson(['message' => __('api.unauthenticated')]);
    }

    public function test_complete_registration_requires_authentication(): void
    {
        // Regression: this used to 500 with a raw TypeError because the route
        // was not behind auth:sanctum, so $request->user() was null.
        $this->postJson(route('api.v1.client.auth.complete-registration'), [
            'name' => 'Мекан',
            'city_id' => City::factory()->create()->id,
        ])
            ->assertStatus(401)
            ->assertExactJson(['message' => __('api.unauthenticated')]);
    }

    public function test_authenticated_client_can_complete_registration(): void
    {
        $client = Client::factory()->create(['name' => null]);
        Sanctum::actingAs($client, ['*']);
        $city = City::factory()->create();

        $this->postJson(route('api.v1.client.auth.complete-registration'), [
            'name' => 'Мекан',
            'city_id' => $city->id,
        ])
            ->assertOk()
            ->assertJsonPath('client.name', 'Мекан')
            ->assertJsonPath('client.city_id', $city->id);

        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'name' => 'Мекан',
            'city_id' => $city->id,
        ]);
    }

    public function test_validation_error_returns_422_with_errors(): void
    {
        Sanctum::actingAs(Client::factory()->create(), ['*']);

        $this->postJson(route('api.v1.client.auth.complete-registration'), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'city_id']);
    }

    public function test_missing_record_returns_localized_404(): void
    {
        Sanctum::actingAs(Client::factory()->create(), ['*']);

        $this->getJson(route('api.v1.client.orders.show', 999999))
            ->assertStatus(404)
            ->assertExactJson(['message' => __('notifications.not_found')]);
    }

    public function test_unknown_api_route_returns_localized_404(): void
    {
        $this->getJson('/api/v1/this-route-does-not-exist')
            ->assertStatus(404)
            ->assertJsonPath('message', __('notifications.not_found'));
    }

    public function test_invalid_otp_returns_localized_422(): void
    {
        $phone = '+99312345678';
        Cache::put("client_otp:{$phone}", '111111', now()->addMinutes(5));

        $this->postJson(route('api.v1.client.auth.verify-otp'), [
            'phone' => $phone,
            'code' => '000000',
        ])
            ->assertStatus(422)
            ->assertExactJson(['message' => __('api.otp.invalid')]);
    }

    public function test_error_message_is_localized_by_header(): void
    {
        $this->withHeader('X-Locale', 'tk')
            ->getJson(route('api.v1.client.me'))
            ->assertStatus(401)
            ->assertExactJson(['message' => 'Awtorizasiýa talap edilýär']);
    }
}
