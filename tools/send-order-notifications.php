<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../admin/config/db.php';
require_once __DIR__ . '/../includes/order-notifications.php';
send_order_notifications($pdo);
