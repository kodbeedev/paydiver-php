<?php

declare(strict_types=1);

namespace Kodbee\Paydiver\Exceptions;

/** Thrown when the client is misconfigured (missing key, missing secret, etc.). */
final class ConfigurationException extends PaydiverException
{
}
