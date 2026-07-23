<?php

namespace App\Exceptions;

class PaymentException extends ApiException
{
    public static function nothingToPay(): self
    {
        return new self((string) __('payments.errors.nothing_to_pay'));
    }

    public static function exceedsBalance(): self
    {
        return new self((string) __('payments.errors.exceeds_balance'));
    }
}
