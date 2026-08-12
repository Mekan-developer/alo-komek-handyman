<?php

namespace App\Events;

use App\Http\Resources\PendingOtpResource;
use App\Models\PendingOtp;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A code the SMS gateway could not deliver has been parked for an operator.
 *
 * Queued (`ShouldBroadcast`, not `...Now`) so the client's login request is not
 * held up by Reverb — the worker pushes it to the open admin panels a moment
 * later. The payload is flattened at dispatch time, so a code that expires or
 * gets dismissed before the worker runs cannot fail the job.
 */
class PendingOtpCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    /** @var array<string, mixed> */
    public array $payload;

    public function __construct(PendingOtp $pendingOtp)
    {
        $this->payload = (new PendingOtpResource($pendingOtp))->resolve();
    }

    /** @return array<int, PrivateChannel> */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin.pending-otps'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'pending-otp.created';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
