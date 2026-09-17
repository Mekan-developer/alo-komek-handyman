<?php

namespace App\Exceptions;

class MasterNotFoundException extends ApiException
{
    public function statusCode(): int
    {
        return 404;
    }

    public static function forPhone(): self
    {
        return new self((string) __('api.master.not_found'));
    }
}
