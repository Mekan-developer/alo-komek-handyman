<?php

namespace Tests\Feature\Api\V1;

use App\Models\Master;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MasterAuthTest extends TestCase
{
    use LazilyRefreshDatabase;

    // ── request-otp ───────────────────────────────────────────────────────────

    public function test_active_master_can_request_otp(): void
    {
        Http::fake(['*/emit-otp' => Http::response(['message' => 'OTP event emitted'])]);

        $master = Master::factory()->create();

        $this->postJson(route('api.v1.master.auth.request-otp'), ['phone' => $master->phone])
            ->assertOk()
            ->assertJson(['message' => 'OTP sent.']);

        $this->assertNotNull(Cache::get("master_otp:{$master->phone}"));

        Http::assertSent(fn ($request) => $request->url() === config('services.sms_gateway.url').'/emit-otp'
            && $request['phone_number'] === substr($master->phone, 4));
    }

    public function test_otp_falls_back_to_manual_delivery_when_sms_gateway_is_unreachable(): void
    {
        Http::fake(['*/emit-otp' => Http::response(['message' => 'No gateway client connected'], 503)]);

        $master = Master::factory()->create();

        $this->postJson(route('api.v1.master.auth.request-otp'), ['phone' => $master->phone])
            ->assertOk()
            ->assertJsonPath('delivery', 'manual');

        $code = Cache::get("master_otp:{$master->phone}");

        $this->assertNotNull($code);
        $this->assertDatabaseHas('pending_otps', [
            'phone' => $master->phone,
            'code' => $code,
            'recipient_type' => 'master',
            'recipient_name' => $master->name,
        ]);
    }

    public function test_inactive_master_gets_no_parked_code(): void
    {
        Http::fake(['*/emit-otp' => Http::response(['message' => 'No gateway client connected'], 503)]);

        $master = Master::factory()->inactive()->create();

        $this->postJson(route('api.v1.master.auth.request-otp'), ['phone' => $master->phone])
            ->assertForbidden();

        $this->assertDatabaseCount('pending_otps', 0);
    }

    public function test_inactive_master_cannot_request_otp(): void
    {
        $master = Master::factory()->inactive()->create();

        $this->postJson(route('api.v1.master.auth.request-otp'), ['phone' => $master->phone])
            ->assertForbidden();

        $this->assertNull(Cache::get("master_otp:{$master->phone}"));
    }

    public function test_expired_master_cannot_request_otp(): void
    {
        $master = Master::factory()->expired()->create();

        $this->postJson(route('api.v1.master.auth.request-otp'), ['phone' => $master->phone])
            ->assertForbidden();
    }

    // ── verify-otp ────────────────────────────────────────────────────────────

    public function test_active_master_can_verify_otp_and_receive_token(): void
    {
        $master = Master::factory()->create();
        Cache::put("master_otp:{$master->phone}", '123456', now()->addMinutes(5));

        $response = $this->postJson(route('api.v1.master.auth.verify-otp'), [
            'phone' => $master->phone,
            'code' => '123456',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'master']);

        $this->assertNull(Cache::get("master_otp:{$master->phone}"));
    }

    public function test_inactive_master_cannot_verify_otp(): void
    {
        $master = Master::factory()->inactive()->create();
        Cache::put("master_otp:{$master->phone}", '123456', now()->addMinutes(5));

        $this->postJson(route('api.v1.master.auth.verify-otp'), [
            'phone' => $master->phone,
            'code' => '123456',
        ])->assertForbidden();
    }

    public function test_expired_master_cannot_verify_otp(): void
    {
        $master = Master::factory()->expired()->create();
        Cache::put("master_otp:{$master->phone}", '123456', now()->addMinutes(5));

        $this->postJson(route('api.v1.master.auth.verify-otp'), [
            'phone' => $master->phone,
            'code' => '123456',
        ])->assertForbidden();
    }

    public function test_invalid_otp_returns_422(): void
    {
        $master = Master::factory()->create();
        Cache::put("master_otp:{$master->phone}", '123456', now()->addMinutes(5));

        $this->postJson(route('api.v1.master.auth.verify-otp'), [
            'phone' => $master->phone,
            'code' => '999999',
        ])->assertUnprocessable();
    }

    // ── store-review account ──────────────────────────────────────────────────

    public function test_store_review_master_signs_in_with_the_fixed_code_and_no_sms(): void
    {
        Http::fake();
        $master = $this->storeReviewMaster();

        $this->postJson(route('api.v1.master.auth.request-otp'), ['phone' => $master->phone])
            ->assertOk()
            ->assertJsonPath('delivery', 'sms');

        Http::assertNothingSent();
        $this->assertNull(Cache::get("master_otp:{$master->phone}"));
        $this->assertDatabaseCount('pending_otps', 0);

        $this->postJson(route('api.v1.master.auth.verify-otp'), [
            'phone' => $master->phone,
            'code' => '010101',
        ])->assertOk()->assertJsonStructure(['token', 'master']);
    }

    public function test_store_review_master_cannot_sign_in_with_another_code(): void
    {
        $master = $this->storeReviewMaster();

        $this->postJson(route('api.v1.master.auth.verify-otp'), [
            'phone' => $master->phone,
            'code' => '999999',
        ])->assertUnprocessable();
    }

    public function test_fixed_code_does_not_work_for_a_regular_master(): void
    {
        $this->enableStoreReviewAccount();
        $master = Master::factory()->create(['phone' => '+99361234567']);

        $this->postJson(route('api.v1.master.auth.verify-otp'), [
            'phone' => $master->phone,
            'code' => '010101',
        ])->assertUnprocessable();
    }

    public function test_fixed_code_does_not_work_while_the_bypass_is_disabled(): void
    {
        config(['services.otp.test_phones' => [], 'services.otp.test_code' => '']);
        $master = Master::factory()->create(['phone' => '+99362222222']);

        $this->postJson(route('api.v1.master.auth.verify-otp'), [
            'phone' => $master->phone,
            'code' => '010101',
        ])->assertUnprocessable();
    }

    public function test_deactivated_store_review_master_cannot_sign_in(): void
    {
        $this->enableStoreReviewAccount();
        $master = Master::factory()->inactive()->create(['phone' => '+99362222222']);

        $this->postJson(route('api.v1.master.auth.verify-otp'), [
            'phone' => $master->phone,
            'code' => '010101',
        ])->assertForbidden();
    }

    private function storeReviewMaster(): Master
    {
        $this->enableStoreReviewAccount();

        return Master::factory()->create(['phone' => '+99362222222']);
    }

    private function enableStoreReviewAccount(): void
    {
        config([
            'services.otp.test_phones' => ['+99362222222'],
            'services.otp.test_code' => '010101',
        ]);
    }

    // ── ensure.master middleware ──────────────────────────────────────────────

    public function test_deactivated_master_existing_token_is_rejected(): void
    {
        $master = Master::factory()->create();
        $token = $master->createToken('mobile')->plainTextToken;

        // Bypass observer via raw query to simulate a token that survived deactivation (race condition)
        Master::where('id', $master->id)->update(['is_active' => false]);

        $this->withToken($token)
            ->getJson(route('api.v1.master.me'))
            ->assertForbidden();
    }

    public function test_deactivating_master_revokes_tokens(): void
    {
        $master = Master::factory()->create();
        $master->createToken('mobile');

        $this->assertCount(1, $master->tokens);

        $master->update(['is_active' => false]);

        $this->assertCount(0, $master->fresh()->tokens);
    }

    public function test_expired_master_existing_token_is_rejected(): void
    {
        $master = Master::factory()->create();
        $token = $master->createToken('mobile')->plainTextToken;

        $master->update(['access_expires_at' => now()->subDay()]);

        $this->withToken($token)
            ->getJson(route('api.v1.master.me'))
            ->assertForbidden();
    }
}
