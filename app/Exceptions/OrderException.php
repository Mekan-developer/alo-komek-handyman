<?php

namespace App\Exceptions;

class OrderException extends ApiException
{
    public static function masterAccessExpired(): self
    {
        return new self((string) __('orders.errors.master_inactive'));
    }

    public static function masterUnavailable(): self
    {
        return new self((string) __('orders.errors.master_unavailable'));
    }

    public static function masterNotAssigned(): self
    {
        return new self((string) __('orders.errors.master_not_assigned'));
    }

    public static function cityMismatch(): self
    {
        return new self((string) __('orders.errors.city_mismatch'));
    }

    public static function categoryMismatch(): self
    {
        return new self((string) __('orders.errors.category_mismatch'));
    }

    public static function alreadyFinal(): self
    {
        return new self((string) __('orders.errors.already_final'));
    }

    public static function invalidTransition(string $from, string $to): self
    {
        return new self((string) __('orders.errors.invalid_transition', ['from' => $from, 'to' => $to]));
    }

    public static function notEditable(): self
    {
        return new self((string) __('orders.errors.not_editable'));
    }

    public static function cannotCancelAssignedOrder(): self
    {
        return new self((string) __('orders.errors.cannot_cancel_assigned'));
    }

    public static function tooManyPhotos(): self
    {
        return new self((string) __('orders.errors.too_many_photos'));
    }

    public static function notCompletedYet(): self
    {
        return new self((string) __('orders.errors.not_completed_yet'));
    }

    public static function alreadyReviewed(): self
    {
        return new self((string) __('orders.errors.already_reviewed'));
    }
}
