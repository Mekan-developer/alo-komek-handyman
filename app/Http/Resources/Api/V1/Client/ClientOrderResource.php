<?php

namespace App\Http\Resources\Api\V1\Client;

use App\OrderStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientOrderResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'description' => $this->description,
            'client_address' => $this->client_address,
            'client_lat' => $this->client_lat,
            'client_lng' => $this->client_lng,
            'final_price' => $this->final_price,

            'city' => $this->whenLoaded('city', fn () => [
                'id' => $this->city->id,
                'name' => $this->city->name,
            ]),
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ]),
            'master' => $this->whenLoaded('master', fn () => $this->master ? [
                'id' => $this->master->id,
                'name' => $this->master->name,
                'phone' => $this->master->phone,
                'location' => $this->masterLocationForClient(),
            ] : null),

            'photos' => $this->whenLoaded('photos', fn () => $this->photos->map(fn ($p) => [
                'id' => $p->id,
                'url' => asset('storage/'.$p->path),
                'status' => $p->status,
            ])),

            'tasks' => $this->whenLoaded('tasks', fn () => $this->tasks->map(fn ($t) => [
                'id' => $t->id,
                'title' => $t->title,
                'description' => $t->description,
                'before_photo_url' => $t->before_photo_path ? asset('storage/'.$t->before_photo_path) : null,
                'after_photo_url' => $t->after_photo_path ? asset('storage/'.$t->after_photo_path) : null,
                'before_status' => $t->before_status,
                'after_status' => $t->after_status,
            ])),

            'review' => $this->whenLoaded('review', fn () => $this->review ? [
                'id' => $this->review->id,
                'rating' => $this->review->rating,
                'comment' => $this->review->comment,
                'created_at' => $this->review->created_at->toDateTimeString(),
            ] : null),

            'assigned_at' => $this->assigned_at?->toDateTimeString(),
            'started_at' => $this->started_at?->toDateTimeString(),
            'completed_at' => $this->completed_at?->toDateTimeString(),
            'cancelled_at' => $this->cancelled_at?->toDateTimeString(),
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }

    /** @return array<string, mixed>|null */
    private function masterLocationForClient(): ?array
    {
        $trackableStatuses = [OrderStatus::Assigned, OrderStatus::InProgress];

        if (! in_array($this->status, $trackableStatuses, true)) {
            return null;
        }

        $location = $this->master->latestLocation ?? null;

        if ($location === null) {
            return null;
        }

        return [
            'lat' => (float) $location->latitude,
            'lng' => (float) $location->longitude,
            'recorded_at' => $location->recorded_at->toDateTimeString(),
        ];
    }
}
