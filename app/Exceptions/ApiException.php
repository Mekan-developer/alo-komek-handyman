<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Base class for expected, business-rule violations surfaced to API clients.
 *
 * The message must already be localized (resolved via __()) at construction
 * time, and {@see statusCode()} dictates the HTTP status. The global API
 * exception handler in bootstrap/app.php renders these as clean JSON.
 */
abstract class ApiException extends RuntimeException
{
    /**
     * HTTP status code returned for this domain error.
     */
    public function statusCode(): int
    {
        return 422;
    }
}
