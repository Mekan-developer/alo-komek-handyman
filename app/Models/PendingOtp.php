<?php

namespace App\Models;

use App\Enums\OtpRecipientType;
use Database\Factories\PendingOtpFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * An OTP code the SMS gateway could not deliver. Administrators read it from
 * the dashboard and dictate it to the caller by phone.
 */
class PendingOtp extends Model
{
    /** @use HasFactory<PendingOtpFactory> */
    use HasFactory;

    /** @var array<int, string> */
    protected $fillable = [
        'phone',
        'code',
        'recipient_type',
        'recipient_name',
        'expires_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'recipient_type' => OtpRecipientType::class,
            'expires_at' => 'datetime',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
