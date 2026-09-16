<?php

namespace App\Services;

use App\Exceptions\OtpException;
use Illuminate\Support\Facades\Cache;

class OtpCodeVerifier
{
    private const MAX_ATTEMPTS = 5;

    /**
     * Compares the submitted OTP to the cached value using a constant-time
     * check. After MAX_ATTEMPTS failures the cached code is discarded so a
     * brute-force run cannot exhaust the full 6-digit space within the TTL.
     *
     * @throws OtpException
     */
    public function verify(string $cacheKey, string $code): void
    {
        $attemptsKey = "{$cacheKey}:attempts";
        $cached = Cache::get($cacheKey);

        if ($cached === null || ! hash_equals((string) $cached, (string) $code)) {
            $attempts = (int) Cache::get($attemptsKey, 0) + 1;
            $ttl = now()->addMinutes((int) config('services.otp.ttl_minutes', 3));

            if ($attempts >= self::MAX_ATTEMPTS) {
                Cache::forget($cacheKey);
                Cache::forget($attemptsKey);
            } else {
                Cache::put($attemptsKey, $attempts, $ttl);
            }

            throw OtpException::invalid();
        }

        Cache::forget($cacheKey);
        Cache::forget($attemptsKey);
    }
}
