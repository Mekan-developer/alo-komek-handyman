<?php

namespace App\Http\Resources\Api\V1\Client;

use App\Http\Resources\Api\V1\TaskPhotoResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientTaskResource extends JsonResource
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
            'price' => $this->price !== null ? (float) $this->price : null,
            'before_photos' => TaskPhotoResource::collection($this->whenLoaded('beforePhotos')),
            'after_photos' => TaskPhotoResource::collection($this->whenLoaded('afterPhotos')),
        ];
    }
}
