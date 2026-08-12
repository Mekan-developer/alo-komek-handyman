<?php

namespace App\Models;

use Database\Factories\OrderTaskFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderTask extends Model
{
    /** @use HasFactory<OrderTaskFactory> */
    use HasFactory;

    /** @var array<int, string> */
    protected $fillable = [
        'order_id',
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

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(OrderTaskPhoto::class);
    }

    public function beforePhotos(): HasMany
    {
        return $this->hasMany(OrderTaskPhoto::class)->where('type', 'before');
    }

    public function afterPhotos(): HasMany
    {
        return $this->hasMany(OrderTaskPhoto::class)->where('type', 'after');
    }
}
