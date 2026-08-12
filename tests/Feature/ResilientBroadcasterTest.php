<?php

namespace Tests\Feature;

use App\Actions\UpdateMasterLocationAction;
use App\Broadcasting\ResilientBroadcaster;
use App\Models\Master;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\Broadcaster;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Tests\TestCase;

class ResilientBroadcasterTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_broadcast_failure_is_logged_instead_of_thrown(): void
    {
        Log::spy();

        $broadcaster = new ResilientBroadcaster($this->failingBroadcaster(), 'reverb', 0);

        $broadcaster->broadcast([new Channel('masters-map')], 'master.location.updated', ['master_id' => 1]);

        Log::shouldHaveReceived('warning')->once()->withArgs(function (string $message, array $context): bool {
            return str_contains($message, 'не доставлено')
                && $context['event'] === 'master.location.updated'
                && $context['channels'] === ['masters-map'];
        });
    }

    public function test_failing_server_is_not_retried_during_cooldown(): void
    {
        Cache::forget('broadcast:unavailable:reverb');

        $wrapped = $this->countingFailingBroadcaster();
        $broadcaster = new ResilientBroadcaster($wrapped, 'reverb', 30);

        foreach (range(1, 5) as $attempt) {
            $broadcaster->broadcast([new Channel('masters-map')], 'master.location.updated');
        }

        $this->assertSame(1, $wrapped->attempts, 'Упавший сервер должен опрашиваться один раз за окно остывания.');
        $this->assertTrue(Cache::has('broadcast:unavailable:reverb'));
    }

    public function test_broadcasting_resumes_after_cooldown_expires(): void
    {
        Cache::forget('broadcast:unavailable:reverb');

        $wrapped = $this->countingFailingBroadcaster();
        $broadcaster = new ResilientBroadcaster($wrapped, 'reverb', 30);

        $broadcaster->broadcast([new Channel('masters-map')], 'master.location.updated');
        $this->travel(31)->seconds();
        $broadcaster->broadcast([new Channel('masters-map')], 'master.location.updated');

        $this->assertSame(2, $wrapped->attempts);
    }

    public function test_channel_authentication_errors_are_not_swallowed(): void
    {
        $broadcaster = new ResilientBroadcaster($this->failingBroadcaster());

        $this->expectException(RuntimeException::class);

        $broadcaster->auth(Request::create('/broadcasting/auth'));
    }

    public function test_unknown_methods_are_proxied_to_the_wrapped_broadcaster(): void
    {
        $broadcaster = new ResilientBroadcaster($this->failingBroadcaster());

        $this->assertSame('proxied', $broadcaster->channel('masters-map', fn () => true));
    }

    public function test_resilient_connection_wraps_the_configured_driver(): void
    {
        config()->set('broadcasting.connections.resilient.connection', 'log');

        $this->assertInstanceOf(ResilientBroadcaster::class, Broadcast::connection('resilient'));
    }

    public function test_action_still_persists_location_when_broadcast_server_is_down(): void
    {
        config()->set('broadcasting.default', 'resilient');
        Broadcast::purge('resilient');
        $this->app->instance('failing-broadcaster', $this->failingBroadcaster());
        Broadcast::extend('resilient', fn (): ResilientBroadcaster => new ResilientBroadcaster($this->app->make('failing-broadcaster')));

        $master = Master::factory()->create();

        $location = app(UpdateMasterLocationAction::class)->handle($master, [
            'latitude' => 37.95,
            'longitude' => 58.38,
        ]);

        $this->assertDatabaseHas('master_locations', [
            'id' => $location->id,
            'master_id' => $master->id,
        ]);
    }

    /**
     * То же, но со счётчиком реальных попыток вещания — для проверки предохранителя.
     */
    private function countingFailingBroadcaster(): Broadcaster
    {
        return new class implements Broadcaster
        {
            public int $attempts = 0;

            public function auth($request): mixed
            {
                return true;
            }

            public function validAuthenticationResponse($request, $result): mixed
            {
                return $result;
            }

            public function broadcast(array $channels, $event, array $payload = []): void
            {
                $this->attempts++;

                throw new BroadcastException('cURL error 7: Failed to connect to 127.0.0.1 port 8880');
            }
        };
    }

    /**
     * Broadcaster, который ведёт себя как выключенный Reverb: вещание падает,
     * остальное API отвечает нормально.
     */
    private function failingBroadcaster(): Broadcaster
    {
        return new class implements Broadcaster
        {
            public function auth($request): mixed
            {
                throw new RuntimeException('auth must bubble up');
            }

            public function validAuthenticationResponse($request, $result): mixed
            {
                return $result;
            }

            public function broadcast(array $channels, $event, array $payload = []): void
            {
                throw new BroadcastException('cURL error 7: Failed to connect to 127.0.0.1 port 8880');
            }

            public function channel(string $channel, callable $callback): string
            {
                return 'proxied';
            }
        };
    }
}
