<?php

namespace App\Models;

use Database\Factories\OrderReceiptFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Неизменяемый снапшот заказа на момент завершения.
 *
 * Цены задач и скидку можно править и после — чек остаётся тем, что выдали клиенту.
 */
class OrderReceipt extends Model
{
    /** @use HasFactory<OrderReceiptFactory> */
    use HasFactory;

    /** @var array<int, string> */
    protected $fillable = [
        'order_id',
        'number',
        'client_name',
        'client_phone',
        'master_name',
        'master_phone',
        'category_name',
        'subtotal',
        'discount_percent',
        'discount_amount',
        'total',
        'issued_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount_percent' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'issued_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderReceiptItem::class);
    }
}
