# Jomabee PHP

Official PHP client for the [Jomabee](https://kodbee.com) payment API by **Kodbee**.
Zero dependencies (just cURL + JSON) — works in any PHP 8.1+ project.

- Create payments and get a hosted payment URL + QR
- Verify transactions by TrxID
- Check invoice status, list transactions, read balance
- Validate incoming webhooks (HMAC-SHA256)

## Install

```bash
composer require kodbee/jomabee-php
```

## Quick start

```php
use Kodbee\Jomabee\Jomabee;

$jomabee = new Jomabee(
    apiKey: 'your_api_key',
    secretKey: 'your_secret_key',      // required for create/verify
    baseUrl: 'https://pay.kodbee.com'  // your Jomabee instance
);

// Create a payment
$payment = $jomabee->createPayment([
    'amount' => 500,
    'product_name' => 'Premium Plan',
    'customer_name' => 'Karim Mia',
    'customer_email' => 'karim@example.com',
    'redirect_url' => 'https://yoursite.com/thank-you',
    'callback_url' => 'https://yoursite.com/webhooks/jomabee',
    // 'gateway' => 'bkash',          // optional: lock to one gateway
    // 'expiry_minutes' => 30,
]);

header('Location: ' . $payment['payment_url']); // send customer to pay
```

## Verify & status

```php
// Verify a payment with a customer-supplied TrxID
$result = $jomabee->verifyPayment('JOMB-XXXXXX', 'ABCDE12345', 'bkash');
// $result['status'] => verified | pending | failed | expired | duplicate

// Poll invoice status
$status = $jomabee->paymentStatus('JOMB-XXXXXX');

// List transactions
$txns = $jomabee->transactions(['status' => 'verified', 'per_page' => 50]);

// Balance
$balance = $jomabee->balance(); // ['currency' => 'BDT', 'verified_total' => ..., 'available' => ...]
```

## Webhooks

Jomabee signs the JSON payload with HMAC-SHA256 and sends the digest in the
`X-Jomabee-Signature` header.

```php
use Kodbee\Jomabee\Webhook;

try {
    $event = Webhook::verifyRequest('your_webhook_secret');
    // $event['event'] === 'payment.verified'
    // $event['invoice_id'], $event['trx_id'], $event['amount'], $event['gateway'] ...
} catch (\RuntimeException $e) {
    http_response_code(400);
    exit('Invalid signature');
}
```

You can also verify a raw body or decoded array directly:

```php
Webhook::verify($rawJsonBody, $signature, $secret);   // returns payload, throws on failure
Webhook::isValid($payloadArray, $signature, $secret); // returns bool
```

## Error handling

```php
use Kodbee\Jomabee\Exceptions\ApiException;
use Kodbee\Jomabee\Exceptions\NetworkException;
use Kodbee\Jomabee\Exceptions\ConfigurationException;

try {
    $jomabee->createPayment([...]);
} catch (ApiException $e) {
    echo $e->errorCode();   // e.g. invalid_api_key, not_found
    echo $e->statusCode();  // HTTP status
    echo $e->getMessage();
} catch (NetworkException $e) {
    // transport failure (timeout/DNS/TLS)
} catch (ConfigurationException $e) {
    // missing key/secret
}
```

All exceptions extend `Kodbee\Jomabee\Exceptions\JomabeeException`.

## License

MIT © [Kodbee](https://kodbee.com)
