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

    public function test_client_otp_dispatch_is_logged(): void
    {
        Event::fake([MessageLogged::class]);
        Http::fake(['*/emit-otp' => Http::response(['message' => 'OTP event emitted'])]);

        $phone = '+99362111222';

        $this->postJson(route('api.v1.client.auth.request-otp'), ['phone' => $phone])->assertOk();

        Event::assertDispatched(MessageLogged::class, fn (MessageLogged $log): bool => $log->level === 'info'
            && $log->message === 'OTP dispatch started'
            && ($log->context['phone'] ?? null) === $phone
            && ($log->context['recipient_type'] ?? null) === 'client'
            && isset($log->context['code']));

        Event::assertDispatched(MessageLogged::class, fn (MessageLogged $log): bool => $log->level === 'info'
            && $log->message === 'SMS gateway accepted OTP'
            && ($log->context['local_phone'] ?? null) === '62111222');

        Event::assertDispatched(MessageLogged::class, fn (MessageLogged $log): bool => $log->level === 'info'
            && $log->message === 'OTP dispatch finished'
            && ($log->context['delivery'] ?? null) === 'sms');
    }

    public function test_master_otp_dispatch_is_logged(): void
    {
        Event::fake([MessageLogged::class]);
        Http::fake(['*/emit-otp' => Http::response(['message' => 'OTP event emitted'])]);

        $master = Master::factory()->create();

        $this->postJson(route('api.v1.master.auth.request-otp'), ['phone' => $master->phone])->assertOk();

        Event::assertDispatched(MessageLogged::class, fn (MessageLogged $log): bool => $log->level === 'info'
            && $log->message === 'OTP dispatch started'
            && ($log->context['phone'] ?? null) === $master->phone
            && ($log->context['recipient_type'] ?? null) === 'master'
            && ($log->context['recipient_name'] ?? null) === $master->name);

        Event::assertDispatched(MessageLogged::class, fn (MessageLogged $log): bool => $log->level === 'info'
            && $log->message === 'OTP dispatch finished'
            && ($log->context['recipient_type'] ?? null) === 'master'
            && ($log->context['delivery'] ?? null) === 'sms');
    }

    public function test_failed_sms_dispatch_is_logged_as_manual(): void
    {
        Event::fake([MessageLogged::class]);
        Http::fake(['*/emit-otp' => Http::response(['message' => 'No gateway client connected'], 503)]);

        $phone = '+99362111222';

        $this->postJson(route('api.v1.client.auth.request-otp'), ['phone' => $phone])
            ->assertOk()
            ->assertJsonPath('delivery', 'manual');

        Event::assertDispatched(MessageLogged::class, fn (MessageLogged $log): bool => $log->level === 'warning'
            && $log->message === 'OTP SMS failed, parked for operator'
            && ($log->context['phone'] ?? null) === $phone);

        Event::assertDispatched(MessageLogged::class, fn (MessageLogged $log): bool => $log->level === 'info'
            && $log->message === 'OTP dispatch finished'
            && ($log->context['delivery'] ?? null) === 'manual');
    }
}
