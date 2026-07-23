<?php

namespace App\Actions;

use App\Exceptions\MasterDisabledException;
use App\Models\Master;
use App\Services\OtpGatewayService;
use Illuminate\Support\Facades\Cache;

class RequestMasterOtpAction
{
    public function __construct(private readonly OtpGatewayService $gateway) {}

    public function handle(Master $master): void
    {
        if (! $master->is_active) {
            throw MasterDisabledException::inactive();
        }

        if (! $master->hasActiveAccess()) {
            throw MasterDisabledException::accessExpired();
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $this->gateway->send($master->phone, $code);

        Cache::put("master_otp:{$master->phone}", $code, now()->addMinutes((int) config('services.otp.ttl_minutes')));
    }
}
