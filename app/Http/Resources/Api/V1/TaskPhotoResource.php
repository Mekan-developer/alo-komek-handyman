<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Single `order_task_photos` row — shared by the master and the client APIs so both
 * apps read task photos through the same contract.
 */
class TaskPhotoResource extends JsonResource
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
            'url' => asset('storage/'.$this->path),
            'status' => $this->status,
        ];
    }
}
