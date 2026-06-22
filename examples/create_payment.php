<?php

require __DIR__ . '/../vendor/autoload.php';

use Kodbee\Paydiver\Exceptions\ApiException;
use Kodbee\Paydiver\Paydiver;

$paydiver = new Paydiver(
    apiKey: getenv('PAYDIVER_API_KEY') ?: 'your_api_key',
    secretKey: getenv('PAYDIVER_SECRET_KEY') ?: 'your_secret_key',
    baseUrl: getenv('PAYDIVER_BASE_URL') ?: 'https://pay.kodbee.com',
);

try {
    $payment = $paydiver->createPayment([
        'amount' => 500,
        'product_name' => 'Premium Plan',
        'customer_name' => 'Karim Mia',
        'customer_email' => 'karim@example.com',
        'redirect_url' => 'https://yoursite.com/thank-you',
        'callback_url' => 'https://yoursite.com/webhooks/paydiver',
    ]);

    echo "Invoice:  {$payment['invoice_id']}\n";
    echo "Pay here: {$payment['payment_url']}\n";
} catch (ApiException $e) {
    fwrite(STDERR, "API error [{$e->errorCode()}]: {$e->getMessage()}\n");
    exit(1);
}
