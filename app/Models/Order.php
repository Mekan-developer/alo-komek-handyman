<?php

namespace App\Models;

use App\OrderStatus;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    /** @var array<int, string> */
    protected $fillable = [
        'city_id',
        'category_id',
        'master_id',
        'client_id',
        'status',
        'client_name',
        'client_phone',
        'description',
        'client_address',
        'client_lat',
        'client_lng',
        'final_price',
        'assigned_at',
        'started_at',
        'completed_at',
        'cancelled_at',
        'cancel_reason',
        'master_change_reason',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'client_lat' => 'decimal:7',
            'client_lng' => 'decimal:7',
            'final_price' => 'decimal:2',
            'assigned_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function master(): BelongsTo
    {
        return $this->belongsTo(Master::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(OrderPhoto::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(OrderTask::class);
    }

    public function masterLocations(): HasMany
    {
        return $this->hasMany(MasterLocation::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(OrderReview::class);
    }
}
