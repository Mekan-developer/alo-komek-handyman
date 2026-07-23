<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role->value,
            'role_label' => $this->role->label(),
            'role_color' => $this->role->color(),
            'is_current' => $this->id === $request->user()?->id,
            'can_delete' => $request->user()?->can('delete', $this->resource) ?? false,
            'created_at' => $this->created_at->format('d.m.Y'),
        ];
    }
}
