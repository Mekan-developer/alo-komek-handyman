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

    public function test_otp_request_fails_when_sms_gateway_is_unreachable(): void
    {
        Http::fake(['*/emit-otp' => Http::response(['message' => 'No gateway client connected'], 503)]);

        $phone = '+99362111222';

        $this->postJson(route('api.v1.client.auth.request-otp'), ['phone' => $phone])
            ->assertStatus(503);

        $this->assertNull(Cache::get("client_otp:{$phone}"));
    }
}
