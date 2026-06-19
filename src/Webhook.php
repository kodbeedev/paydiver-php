<?php

declare(strict_types=1);

namespace Kodbee\Jomabee;

/**
 * Verify and parse incoming Jomabee webhook requests.
 *
 * Jomabee signs the JSON payload with HMAC-SHA256 and sends the hex digest in
 * the `X-Jomabee-Signature` header. The signature is computed over the JSON
 * encoded with unescaped slashes and unicode, so verification re-encodes the
 * decoded payload the same way before comparing.
 */
final class Webhook
{
    public const SIGNATURE_HEADER = 'X-Jomabee-Signature';

    private const JSON_FLAGS = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

    /**
     * Verify a signature against a decoded payload array.
     *
     * @param array<string,mixed> $payload
     */
    public static function isValid(array $payload, string $signature, string $secret): bool
    {
        if ($signature === '' || $secret === '') {
            return false;
        }

        $expected = hash_hmac('sha256', (string) json_encode($payload, self::JSON_FLAGS), $secret);

        return hash_equals($expected, $signature);
    }

    /**
     * Verify a raw JSON request body against a signature.
     *
     * @return array<string,mixed> The decoded, verified payload.
     *
     * @throws \RuntimeException When the body is not valid JSON or the signature is invalid.
     */
    public static function verify(string $rawBody, string $signature, string $secret): array
    {
        $payload = json_decode($rawBody, true);

        if (! is_array($payload)) {
            throw new \RuntimeException('Webhook body is not valid JSON.');
        }

        if (! self::isValid($payload, $signature, $secret)) {
            throw new \RuntimeException('Webhook signature verification failed.');
        }

        return $payload;
    }

    /**
     * Convenience: verify the current PHP request (php://input + header).
     *
     * @return array<string,mixed> The decoded, verified payload.
     *
     * @throws \RuntimeException When verification fails.
     */
    public static function verifyRequest(string $secret): array
    {
        $body = (string) file_get_contents('php://input');
        $signature = $_SERVER['HTTP_X_JOMABEE_SIGNATURE'] ?? '';

        return self::verify($body, (string) $signature, $secret);
    }
}
