<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Queue\Events\Looping;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SystemStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Не уходить в реальную сеть при пинге Reverb.
        Http::fake();
    }

    public function test_queue_status_is_ok_when_heartbeat_is_fresh(): void
    {
        Cache::put('queue:worker_heartbeat', now()->timestamp, 180);

        $this->actingAs(User::factory()->create())
            ->getJson(route('system.status'))
            ->assertOk()
            ->assertJsonPath('queue', 'ok');
    }

    public function test_queue_status_is_error_when_heartbeat_is_missing(): void
    {
        Cache::forget('queue:worker_heartbeat');

        $this->actingAs(User::factory()->create())
            ->getJson(route('system.status'))
            ->assertOk()
            ->assertJsonPath('queue', 'error');
    }

    public function test_queue_status_is_error_when_heartbeat_is_stale(): void
    {
        Cache::put('queue:worker_heartbeat', now()->subSeconds(200)->timestamp, 180);

        $this->actingAs(User::factory()->create())
            ->getJson(route('system.status'))
            ->assertOk()
            ->assertJsonPath('queue', 'error');
    }

    public function test_worker_loop_event_writes_heartbeat(): void
    {
        Cache::forget('queue:worker_heartbeat');

        event(new Looping('database', 'default'));

        $this->assertNotNull(Cache::get('queue:worker_heartbeat'));
    }

    public function test_endpoint_requires_authentication(): void
    {
        $this->getJson(route('system.status'))->assertUnauthorized();
    }
}
