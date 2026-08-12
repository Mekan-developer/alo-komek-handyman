<?php

namespace App\Models;

use Database\Factories\OrderTaskPhotoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderTaskPhoto extends Model
{
    /** @use HasFactory<OrderTaskPhotoFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_CONVERTING = 'converting';

    public const STATUS_DONE = 'done';

    public const STATUS_FAILED = 'failed';

    /** @var array<int, string> */
    protected $fillable = [
        'order_task_id',
        'type',
        'path',
        'status',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(OrderTask::class, 'order_task_id');
    }
}
