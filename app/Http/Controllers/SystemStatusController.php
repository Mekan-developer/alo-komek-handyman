<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class SystemStatusController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'queue' => $this->checkQueue(),
            'websocket' => $this->checkWebSocket(),
            'otp_gateway' => $this->checkOtpGateway(),
        ]);
    }

    private function checkQueue(): string
    {
        $heartbeat = Cache::get('queue:worker_heartbeat');

        if (! $heartbeat || now()->timestamp - $heartbeat > 120) {
            return 'error';
        }

        return 'ok';
    }

    private function checkWebSocket(): string
    {
        $connection = $this->websocketConnectionName();

        try {
            $scheme = config("broadcasting.connections.{$connection}.options.scheme", 'http');
            $host = config("broadcasting.connections.{$connection}.options.host", '127.0.0.1');
            $port = config("broadcasting.connections.{$connection}.options.port", 8081);

            Http::timeout(2)->get("{$scheme}://{$host}:{$port}/");

            return 'ok';
        } catch (\Exception) {
            return 'error';
        }
    }

    /**
     * Имя соединения, у которого лежат host/port WebSocket-сервера.
     *
     * Дефолтный драйвер `resilient` — обёртка (App\Broadcasting\ResilientBroadcaster),
     * поэтому пингуем то соединение, которое он проксирует.
     */
    private function websocketConnectionName(): string
    {
        $connection = (string) config('broadcasting.default');

        if (config("broadcasting.connections.{$connection}.driver") === 'resilient') {
            return (string) config("broadcasting.connections.{$connection}.connection", 'reverb');
        }

        return $connection;
    }

    /** @return array{status: string, clients: int, last_sent: string|null} */
    private function checkOtpGateway(): array
    {
        $lastSent = Cache::get('otp_gateway:last_sent');

        try {
            $response = Http::timeout(2)->get(
                rtrim((string) config('services.sms_gateway.url'), '/').'/health'
            );

            if ($response->successful()) {
                return [
                    'status' => 'ok',
                    'clients' => (int) ($response->json('clients') ?? 0),
                    'last_sent' => $lastSent,
                ];
            }
        } catch (\Exception) {
        }

        return ['status' => 'error', 'clients' => 0, 'last_sent' => $lastSent];
    }
}
