<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MasterLocationResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'master_id' => $this->master_id,
            'order_id' => $this->order_id,
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
            'recorded_at' => $this->recorded_at->toIso8601String(),
        ];
    }
}
