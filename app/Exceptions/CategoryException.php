<?php

namespace App\Exceptions;

class CategoryException extends ApiException
{
    public static function hasOrders(int $count): self
    {
        return new self((string) __('categories.errors.has_orders', ['count' => $count]));
    }
}
