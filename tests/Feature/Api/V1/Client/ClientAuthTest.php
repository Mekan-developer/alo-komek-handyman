<?php

namespace Tests\Feature\Api\V1\Client;

use App\Events\ClientRegistered;
use App\Models\Client;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
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

    public function test_verifying_otp_for_a_new_phone_dispatches_client_registered_event(): void
    {
        Event::fake([ClientRegistered::class]);
        Http::fake(['*/emit-otp' => Http::response(['message' => 'No gateway client connected'], 503)]);

        $phone = '+99362111222';

        $this->postJson(route('api.v1.client.auth.request-otp'), ['phone' => $phone])->assertOk();

        $this->postJson(route('api.v1.client.auth.verify-otp'), [
            'phone' => $phone,
            'code' => Cache::get("client_otp:{$phone}"),
        ])->assertOk()->assertJsonPath('is_new', true);

        Event::assertDispatched(ClientRegistered::class, fn ($event) => $event->client->phone === $phone);
    }

    // ── store-review account ──────────────────────────────────────────────────

    public function test_store_review_client_signs_in_with_the_fixed_code_and_no_sms(): void
    {
        Http::fake();
        $this->enableStoreReviewAccount();

        $phone = '+99362222222';

        $this->postJson(route('api.v1.client.auth.request-otp'), ['phone' => $phone])
            ->assertOk()
            ->assertJsonPath('delivery', 'sms');

        Http::assertNothingSent();
        $this->assertNull(Cache::get("client_otp:{$phone}"));
        $this->assertDatabaseCount('pending_otps', 0);

        $this->postJson(route('api.v1.client.auth.verify-otp'), [
            'phone' => $phone,
            'code' => '010101',
        ])->assertOk()->assertJsonStructure(['token', 'client']);

        $this->assertDatabaseHas('clients', ['phone' => $phone]);
    }

    public function test_store_review_client_cannot_sign_in_with_another_code(): void
    {
        $this->enableStoreReviewAccount();

        $this->postJson(route('api.v1.client.auth.verify-otp'), [
            'phone' => '+99362222222',
            'code' => '999999',
        ])->assertUnprocessable();
    }

    public function test_fixed_code_does_not_work_for_a_regular_client_phone(): void
    {
        $this->enableStoreReviewAccount();

        $this->postJson(route('api.v1.client.auth.verify-otp'), [
            'phone' => '+99361234567',
            'code' => '010101',
        ])->assertUnprocessable();
    }

    public function test_fixed_code_does_not_work_while_the_bypass_is_disabled(): void
    {
        config(['services.otp.test_phones' => [], 'services.otp.test_code' => '']);

        $this->postJson(route('api.v1.client.auth.verify-otp'), [
            'phone' => '+99362222222',
            'code' => '010101',
        ])->assertUnprocessable();
    }

    private function enableStoreReviewAccount(): void
    {
        config([
            'services.otp.test_phones' => ['+99362222222'],
            'services.otp.test_code' => '010101',
        ]);
    }

    public function test_verifying_otp_for_an_existing_client_does_not_dispatch_client_registered_event(): void
    {
        Event::fake([ClientRegistered::class]);
        Http::fake(['*/emit-otp' => Http::response(['message' => 'No gateway client connected'], 503)]);

        $phone = '+99362111222';
        Client::factory()->create(['phone' => $phone]);

        $this->postJson(route('api.v1.client.auth.request-otp'), ['phone' => $phone])->assertOk();

        $this->postJson(route('api.v1.client.auth.verify-otp'), [
            'phone' => $phone,
            'code' => Cache::get("client_otp:{$phone}"),
        ])->assertOk()->assertJsonPath('is_new', false);

        Event::assertNotDispatched(ClientRegistered::class);
    }
}
