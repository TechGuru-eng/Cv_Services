<?php
function textbee_signature_valid(string $body, string $signature, string $secret): bool {
    return $secret !== '' && preg_match('/^[a-f0-9]{64}$/', $signature) === 1 &&
        hash_equals(hash_hmac('sha256', $body, $secret), $signature);
}
