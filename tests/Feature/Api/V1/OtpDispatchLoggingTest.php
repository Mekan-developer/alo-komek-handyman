<?php

namespace Tests\Feature\Api\V1;

use App\Models\Master;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OtpDispatchLoggingTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_client_otp_dispatch_is_logged_once_without_code(): void
    {
        Event::fake([MessageLogged::class]);
        Http::fake(['*/emit-otp' => Http::response(['message' => 'OTP event emitted'])]);

        $phone = '+99362111222';

        $this->postJson(route('api.v1.client.auth.request-otp'), ['phone' => $phone])->assertOk();

        Event::assertDispatched(MessageLogged::class, fn (MessageLogged $log): bool => $log->level === 'info'
            && $log->message === 'OTP dispatched'
            && ($log->context['phone'] ?? null) === $phone
            && ($log->context['recipient_type'] ?? null) === 'client'
            && ($log->context['delivery'] ?? null) === 'sms'
            && isset($log->context['pending_otp_id'])
            && ! array_key_exists('code', $log->context));

        Event::assertNotDispatched(MessageLogged::class, fn (MessageLogged $log): bool => $log->message === 'SMS gateway accepted OTP'
            || $log->message === 'OTP dispatch started'
            || $log->message === 'OTP dispatch finished');
    }

    public function test_master_otp_dispatch_is_logged(): void
    {
        Event::fake([MessageLogged::class]);
        Http::fake(['*/emit-otp' => Http::response(['message' => 'OTP event emitted'])]);

        $master = Master::factory()->create();

        $this->postJson(route('api.v1.master.auth.request-otp'), ['phone' => $master->phone])->assertOk();

        Event::assertDispatched(MessageLogged::class, fn (MessageLogged $log): bool => $log->level === 'info'
            && $log->message === 'OTP dispatched'
            && ($log->context['phone'] ?? null) === $master->phone
            && ($log->context['recipient_type'] ?? null) === 'master'
            && ($log->context['recipient_name'] ?? null) === $master->name
            && ($log->context['delivery'] ?? null) === 'sms');
    }

    public function test_failed_sms_is_logged_with_manual_delivery_and_gateway_error(): void
    {
        Event::fake([MessageLogged::class]);
        Http::fake(['*/emit-otp' => Http::response(['message' => 'No gateway client connected'], 503)]);

        $phone = '+99362111222';

        $this->postJson(route('api.v1.client.auth.request-otp'), ['phone' => $phone])
            ->assertOk()
            ->assertJsonPath('delivery', 'manual');

        Event::assertDispatched(MessageLogged::class, fn (MessageLogged $log): bool => $log->level === 'error'
            && $log->message === 'SMS gateway rejected OTP'
            && ($log->context['phone'] ?? null) === $phone);

        Event::assertDispatched(MessageLogged::class, fn (MessageLogged $log): bool => $log->level === 'info'
            && $log->message === 'OTP dispatched'
            && ($log->context['delivery'] ?? null) === 'manual'
            && ! array_key_exists('code', $log->context));
    }
}
