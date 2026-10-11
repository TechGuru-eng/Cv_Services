<?php
header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/../includes/environment.php';
require_once __DIR__ . '/../includes/chariow-webhook.php';
function chariow_response(int $status, array $body): void { http_response_code($status); echo json_encode($body); exit; }
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') chariow_response(405,['error'=>'POST required']);
$secret=getenv('CHARIOW_PULSE_SECRET') ?: '';
if ($secret === '') chariow_response(503,['error'=>'Pulse secret not configured']);
$raw=file_get_contents('php://input',false,null,0,262145);
if (strlen($raw)>262144) chariow_response(413,['error'=>'Payload too large']);
if (!chariow_signature_valid($raw,$_SERVER['HTTP_X_CHARIOW_SIGNATURE'] ?? '',$secret)) chariow_response(401,['error'=>'Invalid signature']);
try { $payload=json_decode($raw,true,512,JSON_THROW_ON_ERROR); }
catch (Throwable $e) { chariow_response(400,['error'=>'Invalid JSON']); }
if (!is_array($payload)) chariow_response(400,['error'=>'Invalid payload']);
$delivery=$_SERVER['HTTP_X_PULSE_DELIVERY_ID'] ?? '';
// Signed dashboard test pulses have no delivery ID and must not confirm orders.
if ($delivery === '' && isset($payload['note'])) chariow_response(200,['status'=>'test_received','payment_confirmed'=>false]);
if (!preg_match('/^[a-zA-Z0-9_-]{1,150}$/',$delivery)) chariow_response(400,['error'=>'Missing or invalid delivery ID']);
require_once __DIR__ . '/../admin/config/db.php';
try {
    $result=receive_chariow_sale($pdo,$raw,$delivery);
    // Flush the acknowledgement before draining the durable notification outbox.
    register_shutdown_function(function () use ($pdo) {
        if (function_exists('fastcgi_finish_request')) fastcgi_finish_request();
        send_order_notifications($pdo);
    });
    chariow_response(200,$result);
} catch (InvalidArgumentException $e) { chariow_response(400,['error'=>'Invalid delivery']); }
catch (Throwable $e) { error_log('Chariow webhook processing failed.'); chariow_response(503,['error'=>'Retry later']); }
