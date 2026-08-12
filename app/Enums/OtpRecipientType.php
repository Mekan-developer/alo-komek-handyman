<?php

namespace App\Enums;

enum OtpRecipientType: string
{
    case Client = 'client';
    case Master = 'master';

    /** Cache key prefix the verify actions read the code back from. */
    public function cacheKey(string $phone): string
    {
        return match ($this) {
            self::Client => "client_otp:{$phone}",
            self::Master => "master_otp:{$phone}",
        };
    }
}
