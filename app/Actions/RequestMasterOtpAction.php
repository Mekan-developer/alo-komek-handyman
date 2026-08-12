<?php

namespace App\Actions;

use App\Enums\OtpDeliveryChannel;
use App\Enums\OtpRecipientType;
use App\Exceptions\MasterDisabledException;
use App\Models\Master;

class RequestMasterOtpAction
{
    public function __construct(private readonly DispatchOtpAction $dispatcher) {}

    public function handle(Master $master): OtpDeliveryChannel
    {
        if (! $master->is_active) {
            throw MasterDisabledException::inactive();
        }

        if (! $master->hasActiveAccess()) {
            throw MasterDisabledException::accessExpired();
        }

        return $this->dispatcher->handle(
            $master->phone,
            OtpRecipientType::Master,
            $master->name,
        );
    }
}
