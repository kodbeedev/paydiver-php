<?php

require __DIR__ . '/../vendor/autoload.php';

use Kodbee\Paydiver\Webhook;

// Endpoint that Paydiver calls on payment.verified
try {
    $event = Webhook::verifyRequest(getenv('PAYDIVER_WEBHOOK_SECRET') ?: 'your_webhook_secret');
} catch (\RuntimeException $e) {
    http_response_code(400);
    exit('Invalid signature');
}

if (($event['event'] ?? null) === 'payment.verified') {
    // Mark the order paid using $event['invoice_id'], $event['trx_id'], $event['amount'] ...
    error_log("Paid: {$event['invoice_id']} / {$event['trx_id']} / {$event['amount']}");
}

http_response_code(200);
echo 'ok';
