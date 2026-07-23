<?php

namespace App\Actions;

use App\Services\OtpGatewayService;
use Illuminate\Support\Facades\Cache;

class RequestClientOtpAction
{
    public function __construct(private readonly OtpGatewayService $gateway) {}

    public function handle(string $phone): void
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $this->gateway->send($phone, $code);

        Cache::put("client_otp:{$phone}", $code, now()->addMinutes((int) config('services.otp.ttl_minutes')));
    }
}
