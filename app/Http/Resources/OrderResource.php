<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_color' => $this->status->color(),

            'client_name' => $this->client_name,
            'client_phone' => $this->client_phone,
            'description' => $this->description,
            'client_address' => $this->client_address,
            'client_lat' => $this->client_lat,
            'client_lng' => $this->client_lng,

            'final_price' => $this->final_price,

            'city_id' => $this->city_id,
            'category_id' => $this->category_id,
            'master_id' => $this->master_id,

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
                'payment_model' => $this->master->payment_model->value,
                'latest_location' => $this->master->relationLoaded('latestLocation') && $this->master->latestLocation ? [
                    'latitude' => $this->master->latestLocation->latitude,
                    'longitude' => $this->master->latestLocation->longitude,
                    'recorded_at' => $this->master->latestLocation->recorded_at,
                ] : null,
            ] : null),

            'photos' => $this->whenLoaded('photos', fn () => $this->photos->map(fn ($p) => [
                'id' => $p->id,
                'path' => $p->path,
                'url' => asset('storage/'.$p->path),
                'status' => $p->status,
            ])),

            'tasks' => $this->whenLoaded('tasks', fn () => $this->tasks->map(fn ($t) => [
                'id' => $t->id,
                'title' => $t->title,
                'before_photos' => $t->relationLoaded('beforePhotos')
                    ? $t->beforePhotos->map(fn ($p) => ['id' => $p->id, 'url' => asset('storage/'.$p->path), 'status' => $p->status])
                    : [],
                'after_photos' => $t->relationLoaded('afterPhotos')
                    ? $t->afterPhotos->map(fn ($p) => ['id' => $p->id, 'url' => asset('storage/'.$p->path), 'status' => $p->status])
                    : [],
            ])),

            'assigned_at' => $this->assigned_at?->toDateTimeString(),
            'started_at' => $this->started_at?->toDateTimeString(),
            'completed_at' => $this->completed_at?->toDateTimeString(),
            'cancelled_at' => $this->cancelled_at?->toDateTimeString(),
            'cancel_reason' => $this->cancel_reason,
            'master_change_reason' => $this->master_change_reason,

            'created_at' => $this->created_at->format('d.m.Y H:i'),
        ];
    }
}
