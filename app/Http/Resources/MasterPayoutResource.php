<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MasterPayoutResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'master_id' => $this->master_id,
            'master_name' => $this->master_name,
            'amount' => $this->amount,
            'payment_model' => $this->payment_model->value,
            'payment_model_label' => $this->payment_model->label(),
            'paid_by' => $this->whenLoaded('paidBy', fn () => $this->paidBy?->name),
            'note' => $this->note,
            'created_at' => $this->created_at->format('d.m.Y H:i'),
        ];
    }
}
