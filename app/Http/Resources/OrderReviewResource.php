<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class OrderReviewResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'rating' => $this->rating,
            'comment' => $this->comment,
            'created_at' => $this->created_at->format('d.m.Y H:i'),

            'master' => $this->whenLoaded('master', fn () => [
                'id' => $this->master->id,
                'name' => $this->master->name,
                'photo_url' => $this->master->photo ? Storage::url($this->master->photo) : null,
            ]),

            'client' => $this->whenLoaded('client', fn () => [
                'id' => $this->client->id,
                'name' => $this->client->name,
                'phone' => $this->client->phone,
            ]),

            'order' => $this->whenLoaded('order', fn () => [
                'id' => $this->order->id,
                'client_name' => $this->order->client_name,
                'final_price' => $this->order->final_price,
                'completed_at' => $this->order->completed_at?->format('d.m.Y H:i'),
                'category' => $this->order->relationLoaded('category') && $this->order->category
                    ? ['id' => $this->order->category->id, 'name' => $this->order->category->name]
                    : null,
            ]),
        ];
    }
}
