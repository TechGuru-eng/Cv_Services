<?php
// HTTP cron invocations require a deployment secret; CLI jobs keep working.
if (PHP_SAPI !== 'cli') {
    $expected = getenv('MONCV_CRON_TOKEN') ?: '';
    $authorization = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    $received = preg_match('/^Bearer\s+(.+)$/i', $authorization, $match) ? $match[1] : '';
    if ($expected === '' || !hash_equals($expected, $received)) {
        http_response_code(401);
        header('Content-Type: application/json; charset=UTF-8');
        exit(json_encode(['success' => false, 'message' => 'Non autorisé.']));
    }
}
