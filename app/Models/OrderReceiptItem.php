<?php

namespace App\Models;

use Database\Factories\OrderReceiptItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderReceiptItem extends Model
{
    /** @use HasFactory<OrderReceiptItemFactory> */
    use HasFactory;

    /** @var array<int, string> */
    protected $fillable = [
        'order_receipt_id',
        'order_task_id',
        'title',
        'description',
        'price',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(OrderReceipt::class, 'order_receipt_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(OrderTask::class, 'order_task_id');
    }
}
