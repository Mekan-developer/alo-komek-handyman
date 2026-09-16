<?php

namespace App\Actions;

use App\Exceptions\MasterDisabledException;
use App\Exceptions\OtpException;
use App\Models\Master;
use App\Services\OtpCodeVerifier;
use App\Services\OtpTestAccountService;
use Laravel\Sanctum\NewAccessToken;

class VerifyMasterOtpAction
{
    public function __construct(
        private readonly OtpTestAccountService $testAccounts,
        private readonly OtpCodeVerifier $verifier,
    ) {}

    /**
     * @throws MasterDisabledException
     * @throws OtpException
     */
    public function handle(Master $master, string $code): NewAccessToken
    {
        if (! $master->is_active) {
            throw MasterDisabledException::inactive();
        }

        if (! $master->hasActiveAccess()) {
            throw MasterDisabledException::accessExpired();
        }

        if (! $this->testAccounts->matches($master->phone, $code)) {
            $this->verifier->verify("master_otp:{$master->phone}", $code);
        }

        $master->tokens()->where('name', 'mobile')->delete();

        return $master->createToken('mobile');
    }
}
