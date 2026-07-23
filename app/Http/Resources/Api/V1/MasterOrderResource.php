<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MasterOrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'client_name' => $this->client_name,
            'client_phone' => $this->client_phone,
            'address' => $this->client_address,
            'latitude' => $this->client_lat ? (float) $this->client_lat : null,
            'longitude' => $this->client_lng ? (float) $this->client_lng : null,
            'category' => $this->whenLoaded('category', fn () => $this->category->name),
            'description' => $this->description,
            'final_price' => $this->final_price ? (float) $this->final_price : null,
            'photos' => $this->whenLoaded('photos', fn () => $this->photos->map(fn ($p) => [
                'id' => $p->id,
                'url' => $p->path ? asset('storage/'.$p->path) : null,
                'status' => $p->status,
            ])),
            'tasks' => $this->whenLoaded('tasks', fn () => MasterTaskResource::collection($this->tasks)),
            'assigned_at' => $this->assigned_at?->toIso8601String(),
            'started_at' => $this->started_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
