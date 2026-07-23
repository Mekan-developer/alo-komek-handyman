<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MasterTaskResource extends JsonResource
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
            'title' => $this->title,
            'description' => $this->description,
            'before_photos' => $this->whenLoaded('beforePhotos', fn () => $this->beforePhotos->map(fn ($p) => [
                'id' => $p->id,
                'url' => asset('storage/'.$p->path),
                'status' => $p->status,
            ])),
            'after_photos' => $this->whenLoaded('afterPhotos', fn () => $this->afterPhotos->map(fn ($p) => [
                'id' => $p->id,
                'url' => asset('storage/'.$p->path),
                'status' => $p->status,
            ])),
        ];
    }
}
