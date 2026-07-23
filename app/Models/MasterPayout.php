<?php

namespace App\Models;

use App\PaymentModel;
use Database\Factories\MasterPayoutFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MasterPayout extends Model
{
    /** @use HasFactory<MasterPayoutFactory> */
    use HasFactory;

    /** @var array<int, string> */
    protected $fillable = [
        'master_id',
        'master_name',
        'amount',
        'payment_model',
        'paid_by',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_model' => PaymentModel::class,
        ];
    }

    public function master(): BelongsTo
    {
        return $this->belongsTo(Master::class);
    }

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }
}
