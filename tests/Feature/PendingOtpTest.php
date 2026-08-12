<?php

namespace Tests\Feature;

use App\Events\PendingOtpCreated;
use App\Models\PendingOtp;
use App\Models\User;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class PendingOtpTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_sees_the_codes_page(): void
    {
        $otp = PendingOtp::factory()->create(['phone' => '+99362111222', 'code' => '123456']);

        $this->actingAs(User::factory()->create())
            ->get(route('pending-otps.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('PendingOtps/Index')
                ->has('codes', 1)
                ->where('codes.0.id', $otp->id)
                ->where('codes.0.code', '123456')
            );
    }

    public function test_polling_endpoint_returns_active_codes(): void
    {
        $otp = PendingOtp::factory()->create(['phone' => '+99362111222', 'code' => '123456']);

        $this->actingAs(User::factory()->create())
            ->getJson(route('pending-otps.data'))
            ->assertOk()
            ->assertJsonPath('data.0.id', $otp->id)
            ->assertJsonPath('data.0.phone', '+99362111222')
            ->assertJsonPath('data.0.code', '123456')
            ->assertJsonPath('data.0.recipient_type', 'client');
    }

    public function test_remaining_lifetime_is_returned_as_whole_seconds(): void
    {
        PendingOtp::factory()->create(['expires_at' => now()->addSeconds(120)->addMilliseconds(480)]);

        $response = $this->actingAs(User::factory()->create())
            ->getJson(route('pending-otps.data'))
            ->assertOk();

        $this->assertIsInt($response->json('data.0.expires_in_seconds'));
    }

    public function test_expired_codes_are_purged_and_hidden(): void
    {
        PendingOtp::factory()->expired()->create();

        $this->actingAs(User::factory()->create())
            ->getJson(route('pending-otps.data'))
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->assertDatabaseCount('pending_otps', 0);
    }

    public function test_administrator_can_dismiss_a_code(): void
    {
        $otp = PendingOtp::factory()->create();

        $this->actingAs(User::factory()->create())
            ->deleteJson(route('pending-otps.destroy', $otp->id))
            ->assertNoContent();

        $this->assertDatabaseMissing('pending_otps', ['id' => $otp->id]);
    }

    public function test_sidebar_badge_counts_only_active_codes(): void
    {
        PendingOtp::factory()->count(2)->create();
        PendingOtp::factory()->expired()->create();

        $this->actingAs(User::factory()->manager()->create())
            ->get(route('dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('pendingOtpCount', 2));
    }

    public function test_parked_code_is_broadcast_through_the_queue(): void
    {
        Event::fake([PendingOtpCreated::class]);
        Http::fake(['*/emit-otp' => Http::response(['message' => 'No gateway client connected'], 503)]);

        $phone = '+99362111222';

        $this->postJson(route('api.v1.client.auth.request-otp'), ['phone' => $phone])->assertOk();

        Event::assertDispatched(PendingOtpCreated::class, function (PendingOtpCreated $event) use ($phone) {
            return $event->payload['phone'] === $phone
                && $event->payload['code'] === Cache::get("client_otp:{$phone}")
                && $event->broadcastAs() === 'pending-otp.created'
                && $event->broadcastOn()[0]->name === 'private-admin.pending-otps';
        });

        // Queued, not ShouldBroadcastNow — the caller's login must not wait on Reverb.
        $this->assertInstanceOf(ShouldBroadcast::class, new PendingOtpCreated(PendingOtp::factory()->create()));
        $this->assertNotInstanceOf(ShouldBroadcastNow::class, new PendingOtpCreated(PendingOtp::factory()->create()));
    }

    public function test_successful_sms_delivery_broadcasts_nothing(): void
    {
        Event::fake([PendingOtpCreated::class]);
        Http::fake(['*/emit-otp' => Http::response(['message' => 'OTP event emitted'])]);

        $this->postJson(route('api.v1.client.auth.request-otp'), ['phone' => '+99362111222'])->assertOk();

        Event::assertNotDispatched(PendingOtpCreated::class);
    }

    public function test_only_non_operator_staff_may_subscribe_to_the_broadcast_channel(): void
    {
        // The test env uses the null broadcaster, which authorizes everything.
        // Swap in a real one and re-register the channels on it, so the
        // authorization callback actually decides.
        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.key' => 'test-key',
            'broadcasting.connections.reverb.secret' => 'test-secret',
            'broadcasting.connections.reverb.app_id' => 'test-app',
        ]);
        Broadcast::forgetDrivers();
        require base_path('routes/channels.php');

        $this->actingAs(User::factory()->manager()->create())
            ->postJson('/broadcasting/auth', ['channel_name' => 'private-admin.pending-otps', 'socket_id' => '123.456'])
            ->assertOk();

        $this->actingAs(User::factory()->operator()->create())
            ->postJson('/broadcasting/auth', ['channel_name' => 'private-admin.pending-otps', 'socket_id' => '123.456'])
            ->assertForbidden();
    }

    public function test_operator_cannot_see_codes(): void
    {
        PendingOtp::factory()->create();

        $this->actingAs(User::factory()->operator()->create())
            ->getJson(route('pending-otps.data'))
            ->assertForbidden();
    }

    public function test_guest_cannot_see_codes(): void
    {
        PendingOtp::factory()->create();

        $this->getJson(route('pending-otps.data'))->assertUnauthorized();
    }
}
