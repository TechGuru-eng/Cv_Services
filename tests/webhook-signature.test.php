<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../includes/webhook-signature.php';
$body = '{"webhookEvent":"MESSAGE_RECEIVED","message":"test"}';
$secret = 'test-only-secret';
$signature = hash_hmac('sha256', $body, $secret);
if (!textbee_signature_valid($body, $signature, $secret) ||
    textbee_signature_valid($body . ' ', $signature, $secret) ||
    textbee_signature_valid($body, $signature, 'wrong-secret') ||
    textbee_signature_valid($body, '', $secret) ||
    textbee_signature_valid($body, $signature, '')) throw new RuntimeException('Webhook signature verification failed');
echo "5 webhook signature cases passed.\n";
