<?php

require __DIR__ . '/../vendor/autoload.php';

use Kodbee\Jomabee\Exceptions\ApiException;
use Kodbee\Jomabee\Jomabee;

$jomabee = new Jomabee(
    apiKey: getenv('JOMABEE_API_KEY') ?: 'your_api_key',
    secretKey: getenv('JOMABEE_SECRET_KEY') ?: 'your_secret_key',
    baseUrl: getenv('JOMABEE_BASE_URL') ?: 'https://pay.kodbee.com',
);

try {
    $payment = $jomabee->createPayment([
        'amount' => 500,
        'product_name' => 'Premium Plan',
        'customer_name' => 'Karim Mia',
        'customer_email' => 'karim@example.com',
        'redirect_url' => 'https://yoursite.com/thank-you',
        'callback_url' => 'https://yoursite.com/webhooks/jomabee',
    ]);

    echo "Invoice:  {$payment['invoice_id']}\n";
    echo "Pay here: {$payment['payment_url']}\n";
} catch (ApiException $e) {
    fwrite(STDERR, "API error [{$e->errorCode()}]: {$e->getMessage()}\n");
    exit(1);
}
