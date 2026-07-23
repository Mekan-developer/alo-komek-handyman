<?php

namespace App\Http\Resources\Api\V1\Client;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BannerResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'image_url' => $this->image_url,
            'url' => $this->url,
            'sort_order' => $this->sort_order,
        ];
    }
}
