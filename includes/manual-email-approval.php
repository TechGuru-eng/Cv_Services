<?php
// Campaign and reminder emails require a current authenticated admin action.
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || empty($_SESSION['admin'])) {
    http_response_code(403);
    header('Content-Type: application/json');
    exit(json_encode(['success'=>false,'message'=>'Envoi automatique désactivé. Approbation administrateur requise.']));
}
require_once __DIR__ . '/../admin/includes/csrf.php';
verify_csrf();
