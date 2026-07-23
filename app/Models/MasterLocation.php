<?php

namespace App\Models;

use Database\Factories\MasterLocationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MasterLocation extends Model
{
    /** @use HasFactory<MasterLocationFactory> */
    use HasFactory;

    public $timestamps = false;

    /** @var array<int, string> */
    protected $fillable = [
        'master_id',
        'order_id',
        'latitude',
        'longitude',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'recorded_at' => 'datetime',
        ];
    }

    public function master(): BelongsTo
    {
        return $this->belongsTo(Master::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
