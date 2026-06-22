<?php

declare(strict_types=1);

namespace Kodbee\Paydiver\Exceptions;

/** Thrown when the HTTP transport fails (DNS, timeout, TLS, etc.). */
final class NetworkException extends PaydiverException
{
}
