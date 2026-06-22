<?php

declare(strict_types=1);

namespace Kodbee\Paydiver\Exceptions;

/** Thrown when the API returns an error response (4xx/5xx or success=false). */
final class ApiException extends PaydiverException
{
    public function __construct(
        string $message,
        private string $errorCode = 'error',
        private int $statusCode = 0
    ) {
        parent::__construct($message);
    }

    /** Machine-readable error code from the API (e.g. invalid_api_key, not_found). */
    public function errorCode(): string
    {
        return $this->errorCode;
    }

    /** HTTP status code of the response. */
    public function statusCode(): int
    {
        return $this->statusCode;
    }
}
