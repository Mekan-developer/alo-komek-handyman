<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MasterProfileResource extends JsonResource
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
            'balance' => (float) $this->balance,
            'payment_model' => $this->payment_model->value,
            'payment_value' => (float) $this->payment_value,
            'is_active' => $this->is_active,
            'is_available' => $this->is_available,
            'access_expires_at' => $this->access_expires_at?->toDateString(),
            'city' => $this->whenLoaded('city', fn () => [
                'id' => $this->city->id,
                'name' => $this->city->name,
            ]),
            'categories' => $this->whenLoaded('categories', fn () => $this->categories->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
            ])),
        ];
    }
}
