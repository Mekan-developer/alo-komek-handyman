<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class CategoryContentResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'title_ru' => $this->title_ru,
            'title_tk' => $this->title_tk,
            'description' => $this->description,
            'description_ru' => $this->description_ru,
            'description_tk' => $this->description_tk,
            'price' => $this->price,
            'images' => $this->images->map(fn ($img) => [
                'id' => $img->id,
                'url' => Storage::disk('public')->url($img->path),
            ])->values(),
        ];
    }
}
