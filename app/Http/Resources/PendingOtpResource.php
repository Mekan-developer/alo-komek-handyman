<?php

namespace App\Http\Resources;

use App\Models\PendingOtp;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PendingOtp
 */
class PendingOtpResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'phone' => $this->phone,
            'code' => $this->code,
            // Label is resolved client-side: this payload is also broadcast from a
            // queue worker, which has no notion of the viewer's locale.
            'recipient_type' => $this->recipient_type->value,
            'recipient_name' => $this->recipient_name,
            // Carbon returns a float here; cast to int so the client renders whole seconds.
            'expires_in_seconds' => (int) max(0, now()->diffInSeconds($this->expires_at, false)),
            'created_at' => $this->created_at->format('H:i'),
        ];
    }
}
