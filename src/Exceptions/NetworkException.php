<?php

declare(strict_types=1);

namespace Kodbee\Jomabee\Exceptions;

/** Thrown when the HTTP transport fails (DNS, timeout, TLS, etc.). */
final class NetworkException extends JomabeeException
{
}
