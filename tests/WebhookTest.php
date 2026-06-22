<?php

declare(strict_types=1);

namespace Kodbee\Paydiver\Tests;

use Kodbee\Paydiver\Webhook;
use PHPUnit\Framework\TestCase;

final class WebhookTest extends TestCase
{
    private const SECRET = 'whsec_test_secret';

    /** Server-side signing algorithm, mirrored for the test. */
    private function sign(array $payload): string
    {
        return hash_hmac(
            'sha256',
            (string) json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            self::SECRET
        );
    }

    public function test_valid_signature_passes(): void
    {
        $payload = ['event' => 'payment.verified', 'invoice_id' => 'PAYD-ABC', 'amount' => 500.0];

        $this->assertTrue(Webhook::isValid($payload, $this->sign($payload), self::SECRET));
    }

    public function test_tampered_payload_fails(): void
    {
        $payload = ['event' => 'payment.verified', 'invoice_id' => 'PAYD-ABC', 'amount' => 500.0];
        $signature = $this->sign($payload);

        $payload['amount'] = 999.0;

        $this->assertFalse(Webhook::isValid($payload, $signature, self::SECRET));
    }

    public function test_empty_signature_or_secret_fails(): void
    {
        $payload = ['event' => 'payment.verified'];

        $this->assertFalse(Webhook::isValid($payload, '', self::SECRET));
        $this->assertFalse(Webhook::isValid($payload, $this->sign($payload), ''));
    }

    public function test_verify_returns_payload(): void
    {
        $payload = ['event' => 'payment.verified', 'invoice_id' => 'PAYD-XYZ'];
        $body = (string) json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $verified = Webhook::verify($body, $this->sign($payload), self::SECRET);

        $this->assertSame('PAYD-XYZ', $verified['invoice_id']);
    }

    public function test_verify_throws_on_bad_signature(): void
    {
        $this->expectException(\RuntimeException::class);

        Webhook::verify('{"event":"x"}', 'deadbeef', self::SECRET);
    }
}
