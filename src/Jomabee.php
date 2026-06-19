<?php

declare(strict_types=1);

namespace Kodbee\Jomabee;

use Kodbee\Jomabee\Exceptions\ApiException;
use Kodbee\Jomabee\Exceptions\ConfigurationException;
use Kodbee\Jomabee\Exceptions\NetworkException;

/**
 * Official PHP client for the Jomabee payment API by Kodbee.
 *
 * @see https://kodbee.com
 */
final class Jomabee
{
    public const VERSION = '1.0.0';

    private string $apiKey;
    private ?string $secretKey;
    private string $baseUrl;
    private int $timeout;

    /**
     * @param string      $apiKey    Merchant API key (X-API-Key).
     * @param string|null $secretKey Merchant secret key (X-Secret-Key) — required for create/verify.
     * @param string      $baseUrl   API base URL, e.g. https://pay.kodbee.com
     * @param int         $timeout   Request timeout in seconds.
     */
    public function __construct(
        string $apiKey,
        ?string $secretKey = null,
        string $baseUrl = 'https://pay.kodbee.com',
        int $timeout = 30
    ) {
        if ($apiKey === '') {
            throw new ConfigurationException('API key must not be empty.');
        }

        $this->apiKey = $apiKey;
        $this->secretKey = $secretKey !== '' ? $secretKey : null;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = $timeout;
    }

    /**
     * Create a payment invoice and get a hosted payment URL + QR.
     *
     * @param array<string,mixed> $params amount (required), product_name (required),
     *                                     customer_name, customer_email, redirect_url,
     *                                     callback_url, gateway, expiry_minutes
     * @return array<string,mixed> invoice_id, payment_url, qr_code, amount, expires_at
     */
    public function createPayment(array $params): array
    {
        $this->requireSecret('createPayment');

        return $this->request('POST', '/api/v1/payment/create', $params, true);
    }

    /**
     * Verify a payment by transaction ID against an invoice.
     *
     * @return array<string,mixed> status, invoice_id, trx_id
     */
    public function verifyPayment(string $invoiceId, string $trxId, ?string $gateway = null): array
    {
        $this->requireSecret('verifyPayment');

        $body = ['invoice_id' => $invoiceId, 'trx_id' => $trxId];
        if ($gateway !== null) {
            $body['gateway'] = $gateway;
        }

        return $this->request('POST', '/api/v1/payment/verify', $body, true);
    }

    /**
     * Get the current status of an invoice.
     *
     * @return array<string,mixed> invoice_id, status, amount, trx_id, gateway, verified_at
     */
    public function paymentStatus(string $invoiceId): array
    {
        return $this->request('GET', '/api/v1/payment/status/' . rawurlencode($invoiceId));
    }

    /**
     * List transactions.
     *
     * @param array<string,mixed> $query status, from, to, per_page, page
     * @return array<string,mixed> transactions[], pagination
     */
    public function transactions(array $query = []): array
    {
        $path = '/api/v1/transactions';
        if ($query !== []) {
            $path .= '?' . http_build_query($query);
        }

        return $this->request('GET', $path);
    }

    /**
     * Get the merchant balance.
     *
     * @return array<string,mixed> currency, verified_total, available
     */
    public function balance(): array
    {
        return $this->request('GET', '/api/v1/balance');
    }

    private function requireSecret(string $method): void
    {
        if ($this->secretKey === null) {
            throw new ConfigurationException(
                "Jomabee::{$method}() requires a secret key. Pass it as the second constructor argument."
            );
        }
    }

    /**
     * @param array<string,mixed> $body
     * @return array<string,mixed>
     */
    private function request(string $method, string $path, array $body = [], bool $withSecret = false): array
    {
        $headers = [
            'Accept: application/json',
            'X-API-Key: ' . $this->apiKey,
            'User-Agent: jomabee-php/' . self::VERSION,
        ];

        if ($withSecret && $this->secretKey !== null) {
            $headers[] = 'X-Secret-Key: ' . $this->secretKey;
        }

        $ch = curl_init($this->baseUrl . $path);
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_HTTPHEADER => $headers,
        ];

        if ($method !== 'GET' && $body !== []) {
            $options[CURLOPT_POSTFIELDS] = json_encode($body, JSON_THROW_ON_ERROR);
            $options[CURLOPT_HTTPHEADER][] = 'Content-Type: application/json';
        }

        curl_setopt_array($ch, $options);

        $raw = curl_exec($ch);

        if ($raw === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new NetworkException('HTTP request failed: ' . $error);
        }

        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode((string) $raw, true);

        if (! is_array($decoded)) {
            throw new ApiException('Unexpected non-JSON response from API.', 'invalid_response', $status);
        }

        if ($status >= 400 || (($decoded['success'] ?? false) === false)) {
            $code = $decoded['error']['code'] ?? 'error';
            $message = $decoded['error']['message'] ?? 'Request failed.';
            throw new ApiException($message, $code, $status);
        }

        /** @var array<string,mixed> $data */
        $data = $decoded['data'] ?? [];

        return $data;
    }
}
