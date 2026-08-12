<?php

namespace App\Broadcasting;

use Illuminate\Contracts\Broadcasting\Broadcaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Декоратор поверх реального broadcaster-а: недоступный WebSocket-сервер
 * (Reverb не запущен, закрыт порт, таймаут соединения) не должен ронять
 * HTTP-запрос или очередную задачу.
 *
 * Realtime здесь — вспомогательный канал доставки: заказ, статус и локация уже
 * сохранены в БД до отправки события, поэтому провал публикации логируется
 * и проглатывается.
 *
 * Аутентификация приватных каналов (`auth`) намеренно не оборачивается —
 * её ошибки обязаны доходить до клиента, иначе подписка пройдёт без проверки.
 *
 * @mixin \Illuminate\Broadcasting\Broadcasters\Broadcaster
 */
class ResilientBroadcaster implements Broadcaster
{
    /**
     * @param  string  $name  имя обёрнутого соединения — ключ предохранителя в кэше
     * @param  int  $cooldownSeconds  сколько секунд не трогать упавший сервер (0 — пытаться всегда)
     */
    public function __construct(
        private Broadcaster $broadcaster,
        private string $name = 'default',
        private int $cooldownSeconds = 30,
    ) {}

    /**
     * @param  Request  $request
     */
    public function auth($request): mixed
    {
        return $this->broadcaster->auth($request);
    }

    /**
     * @param  Request  $request
     */
    public function validAuthenticationResponse($request, $result): mixed
    {
        return $this->broadcaster->validAuthenticationResponse($request, $result);
    }

    /**
     * @param  array<int, mixed>  $channels
     * @param  array<string, mixed>  $payload
     */
    public function broadcast(array $channels, $event, array $payload = []): void
    {
        if ($this->isCoolingDown()) {
            return;
        }

        try {
            $this->broadcaster->broadcast($channels, $event, $payload);
        } catch (Throwable $exception) {
            $this->startCooldown();

            Log::warning('Realtime-событие не доставлено: сервер вещания недоступен.', [
                'event' => $event,
                'channels' => array_map('strval', $channels),
                'reason' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Предохранитель: после первого провала остальные запросы не ждут
     * connect timeout впустую (мастера отправляют локацию каждые несколько секунд).
     */
    private function isCoolingDown(): bool
    {
        return $this->cooldownSeconds > 0 && Cache::has($this->cooldownKey());
    }

    private function startCooldown(): void
    {
        if ($this->cooldownSeconds > 0) {
            Cache::put($this->cooldownKey(), true, $this->cooldownSeconds);
        }
    }

    private function cooldownKey(): string
    {
        return "broadcast:unavailable:{$this->name}";
    }

    /**
     * Проксирует остальное API broadcaster-а (`channel()`, `resolveAuthenticatedUser()` и пр.),
     * которое `Broadcast` вызывает напрямую через `__call` менеджера.
     *
     * @param  array<int, mixed>  $parameters
     */
    public function __call(string $method, array $parameters): mixed
    {
        return $this->broadcaster->{$method}(...$parameters);
    }
}
