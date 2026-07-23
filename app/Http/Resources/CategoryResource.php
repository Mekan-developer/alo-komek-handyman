<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'name_ru' => $this->name_ru,
            'name_tk' => $this->name_tk,
            'is_active' => $this->is_active,
            'parent_id' => $this->parent_id,
            'icon_type' => $this->icon_type?->value,
            'icon' => $this->icon,
            'icon_url' => $this->icon_url,
            'parent' => $this->whenLoaded('parent', fn () => [
                'id' => $this->parent->id,
                'name' => $this->parent->name,
            ]),
            'created_at' => $this->created_at?->toDateTimeString(),
            'content' => $this->whenLoaded('content', fn () => $this->content
                ? new CategoryContentResource($this->content)
                : null
            ),
        ];
    }
}
