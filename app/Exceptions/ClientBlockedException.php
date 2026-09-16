<?php

namespace App\Exceptions;

class ClientBlockedException extends ApiException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public function statusCode(): int
    {
        return 403;
    }

    public static function blocked(): self
    {
        return new self((string) __('api.client.blocked'));
    }
}
