<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderTaskPhoto extends Model
{
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
