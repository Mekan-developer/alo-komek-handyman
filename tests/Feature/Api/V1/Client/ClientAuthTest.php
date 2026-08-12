<?php

namespace Tests\Feature\Api\V1\Client;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ClientAuthTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_client_can_request_otp(): void
    {
        Http::fake(['*/emit-otp' => Http::response(['message' => 'OTP event emitted'])]);

        $phone = '+99362111222';

        $this->postJson(route('api.v1.client.auth.request-otp'), ['phone' => $phone])
            ->assertOk()
            ->assertJson(['message' => 'OTP sent.']);

        $this->assertNotNull(Cache::get("client_otp:{$phone}"));

        Http::assertSent(fn ($request) => $request->url() === config('services.sms_gateway.url').'/emit-otp'
            && $request['phone_number'] === '62111222');
    }

    public function test_otp_falls_back_to_manual_delivery_when_sms_gateway_is_unreachable(): void
    {
        Http::fake(['*/emit-otp' => Http::response(['message' => 'No gateway client connected'], 503)]);

        $phone = '+99362111222';

        $this->postJson(route('api.v1.client.auth.request-otp'), ['phone' => $phone])
            ->assertOk()
            ->assertJsonPath('delivery', 'manual');

        $code = Cache::get("client_otp:{$phone}");

        $this->assertNotNull($code);
        $this->assertDatabaseHas('pending_otps', [
            'phone' => $phone,
            'code' => $code,
            'recipient_type' => 'client',
        ]);
    }

    public function test_manual_fallback_keeps_only_the_latest_code_per_phone(): void
    {
        Http::fake(['*/emit-otp' => Http::response(['message' => 'No gateway client connected'], 503)]);

        $phone = '+99362111222';

        $this->postJson(route('api.v1.client.auth.request-otp'), ['phone' => $phone])->assertOk();
        $this->postJson(route('api.v1.client.auth.request-otp'), ['phone' => $phone])->assertOk();

        $this->assertDatabaseCount('pending_otps', 1);
        $this->assertDatabaseHas('pending_otps', ['code' => Cache::get("client_otp:{$phone}")]);
    }

    public function test_client_can_verify_a_manually_delivered_code(): void
    {
        Http::fake(['*/emit-otp' => Http::response(['message' => 'No gateway client connected'], 503)]);

        $phone = '+99362111222';

        $this->postJson(route('api.v1.client.auth.request-otp'), ['phone' => $phone])->assertOk();

        $this->postJson(route('api.v1.client.auth.verify-otp'), [
            'phone' => $phone,
            'code' => Cache::get("client_otp:{$phone}"),
        ])->assertOk()->assertJsonStructure(['token', 'client']);
    }

    public function test_sms_delivery_does_not_park_a_code_for_operators(): void
    {
        Http::fake(['*/emit-otp' => Http::response(['message' => 'OTP event emitted'])]);

        $this->postJson(route('api.v1.client.auth.request-otp'), ['phone' => '+99362111222'])
            ->assertOk()
            ->assertJsonPath('delivery', 'sms');

        $this->assertDatabaseCount('pending_otps', 0);
    }
}
