<?php

namespace App\Actions;

use App\Enums\OtpDeliveryChannel;
use App\Enums\OtpRecipientType;
use App\Exceptions\MasterDisabledException;
use App\Exceptions\MasterNotFoundException;
use App\Repositories\MasterRepository;

class RequestMasterOtpAction
{
    public function __construct(
        private readonly DispatchOtpAction $dispatcher,
        private readonly MasterRepository $masters,
    ) {}

    /**
     * @throws MasterNotFoundException
     * @throws MasterDisabledException
     */
    public function handle(string $phone): OtpDeliveryChannel
    {
        $master = $this->masters->findByPhone($phone);

        if ($master === null) {
            throw MasterNotFoundException::forPhone();
        }

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
