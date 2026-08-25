<?php

namespace App\Http\Resources;

use App\Models\OrderReceipt;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Чек — неизменяемый снапшот, поэтому представление одинаково для админки,
 * приложения клиента и приложения мастера. Подписи локализуются на клиенте.
 *
 * @mixin OrderReceipt
 */
class OrderReceiptResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'number' => $this->number,

            'client_name' => $this->client_name,
            'client_phone' => $this->client_phone,
            'master_name' => $this->master_name,
            'master_phone' => $this->master_phone,
            'category_name' => $this->category_name,

            'subtotal' => (float) $this->subtotal,
            'discount_percent' => (float) $this->discount_percent,
            'discount_amount' => (float) $this->discount_amount,
            'total' => (float) $this->total,
            'currency' => 'TMT',

            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'title' => $item->title,
                'description' => $item->description,
                'price' => (float) $item->price,
            ])->values()),

            'issued_at' => $this->issued_at->toDateTimeString(),
            'issued_date' => $this->issued_at->format('d.m.y'),
            'issued_time' => $this->issued_at->format('H:i'),
        ];
    }
}
