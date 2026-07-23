<?php

namespace App\Exceptions;

class MasterDisabledException extends ApiException
{
    public function statusCode(): int
    {
        return 403;
    }

    public static function inactive(): self
    {
        return new self((string) __('api.master.disabled'));
    }

    public static function accessExpired(): self
    {
        return new self((string) __('api.master.access_expired'));
    }
}
