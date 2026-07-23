<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CityResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'oblast_id' => $this->oblast_id,
            'oblast' => $this->whenLoaded('oblast', fn () => [
                'id' => $this->oblast->id,
                'name' => $this->oblast->name,
            ]),
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
