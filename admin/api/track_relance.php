<?php
session_start();
if (!isset($_SESSION['admin'])) { http_response_code(401); exit('Unauthorized'); }
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/prospect_helper.php';
require_once __DIR__ . '/../includes/activity_helper.php';
prepareProspectTracking($pdo);
$id = (int)($_GET['id'] ?? 0);
$url = $_GET['url'] ?? '';
$parts = parse_url($url);
if (!$id || !$parts || ($parts['scheme'] ?? '') !== 'https'
    || !in_array(strtolower($parts['host'] ?? ''), ['wa.me', 'api.whatsapp.com'])) {
    http_response_code(400); exit('Invalid WhatsApp request.');
}
if (!preg_match('/^L(\d{1,2})$/', $_GET['type'] ?? '', $match)) {
    http_response_code(400); exit('Invalid level.');
}
$level = (int)$match[1];
$expected = filter_var($_GET['expected_stage'] ?? null, FILTER_VALIDATE_INT);
if ($level < 1 || $level > 11 || $expected === false || $expected === null) {
    http_response_code(400); exit('Refresh the prospects page before opening a follow-up.');
}
// Repeated clicks cannot count twice; only the next level advances the admin counter.
if ($level === $expected + 1) {
    $stmt = $pdo->prepare("UPDATE prospects SET manual_followup_stage = ?, last_followup = NOW() WHERE id = ? AND manual_followup_stage = ? AND status IN ('pending', 'lead_audit')");
    $stmt->execute([$level, $id, $expected]);
    if ($stmt->rowCount()) log_activity('Relance WhatsApp', "Prospect #$id : niveau L$level ouvert par l'administrateur.");
}
header('Location: ' . $url);
exit;
