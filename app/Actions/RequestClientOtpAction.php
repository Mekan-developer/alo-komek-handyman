<?php

namespace App\Actions;

use App\Enums\OtpDeliveryChannel;
use App\Enums\OtpRecipientType;
use App\Repositories\ClientRepository;

class RequestClientOtpAction
{
    public function __construct(
        private readonly DispatchOtpAction $dispatcher,
        private readonly ClientRepository $clients,
    ) {}

    public function handle(string $phone): OtpDeliveryChannel
    {
        return $this->dispatcher->handle(
            $phone,
            OtpRecipientType::Client,
            $this->clients->findByPhone($phone)?->name,
        );
    }
}
