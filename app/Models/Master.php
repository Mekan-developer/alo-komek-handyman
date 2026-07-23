<?php

namespace App\Models;

use App\PaymentModel;
use Database\Factories\MasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Master extends Authenticatable
{
    /** @use HasFactory<MasterFactory> */
    use HasApiTokens, HasFactory;

    /** @var array<int, string> */
    protected $fillable = [
        'city_id',
        'name',
        'phone',
        'payment_model',
        'payment_value',
        'monthly_salary',
        'balance',
        'access_expires_at',
        'is_active',
        'is_available',
        'photo',
    ];

    protected function casts(): array
    {
        return [
            'payment_model' => PaymentModel::class,
            'payment_value' => 'decimal:2',
            'monthly_salary' => 'decimal:2',
            'balance' => 'decimal:2',
            'access_expires_at' => 'datetime',
            'is_active' => 'boolean',
            'is_available' => 'boolean',
        ];
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(MasterLocation::class);
    }

    public function latestLocation(): HasOne
    {
        return $this->hasOne(MasterLocation::class)->latestOfMany('recorded_at');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(OrderReview::class);
    }

    public function hasActiveAccess(): bool
    {
        return $this->access_expires_at === null || $this->access_expires_at->isFuture();
    }
}
