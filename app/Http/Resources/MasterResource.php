<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class MasterResource extends JsonResource
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
            'name' => $this->name,
            'phone' => $this->phone,
            'city_id' => $this->city_id,
            'city' => $this->whenLoaded('city', fn () => [
                'id' => $this->city->id,
                'name' => $this->city->name,
            ]),
            'payment_model' => $this->payment_model->value,
            'payment_value' => $this->payment_value,
            'monthly_salary' => $this->monthly_salary,
            'balance' => $this->balance,
            'access_expires_at' => $this->access_expires_at?->toDateTimeString(),
            'is_active' => $this->is_active,
            'is_available' => $this->is_available,
            'photo' => $this->photo,
            'photo_url' => $this->photo ? Storage::url($this->photo) : null,
            'reviews_avg_rating' => $this->reviews_avg_rating !== null ? round((float) $this->reviews_avg_rating, 1) : null,
            'reviews_count' => (int) ($this->reviews_count ?? 0),
            'categories' => $this->whenLoaded('categories', fn () => $this->categories->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])
            ),
            'category_ids' => $this->whenLoaded('categories', fn () => $this->categories->pluck('id')->values()
            ),
            'latest_location' => $this->whenLoaded('latestLocation', fn () => $this->latestLocation ? [
                'latitude' => $this->latestLocation->latitude,
                'longitude' => $this->latestLocation->longitude,
                'recorded_at' => $this->latestLocation->recorded_at,
            ] : null),
            'created_at' => $this->created_at->format('d.m.Y'),
        ];
    }
}
