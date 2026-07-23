<?php

namespace App\Models;

use Database\Factories\OrderPhotoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderPhoto extends Model
{
    /** @use HasFactory<OrderPhotoFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_CONVERTING = 'converting';

    public const STATUS_DONE = 'done';

    public const STATUS_FAILED = 'failed';

    /** @var array<int, string> */
    protected $fillable = [
        'order_id',
        'path',
        'original_name',
        'status',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
