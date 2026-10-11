<?php
require_once '../config/db.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data     = json_decode(file_get_contents('php://input'), true);
$memberId = (int)($data['memberId'] ?? 0);

if (!$memberId) {
    echo json_encode(['success' => false, 'message' => 'Missing member ID']);
    exit;
}

// Generate a readable 8-char password: 4 letters + 4 digits
$chars    = 'abcdefghjkmnpqrstuvwxyz'; // no confusing chars
$digits   = '23456789';
$newPass  = '';
for ($i = 0; $i < 4; $i++) $newPass .= $chars[random_int(0, strlen($chars) - 1)];
for ($i = 0; $i < 4; $i++) $newPass .= $digits[random_int(0, strlen($digits) - 1)];
// Shuffle it
$newPass = str_shuffle($newPass);

$hash = password_hash($newPass, PASSWORD_BCRYPT);

try {
    $stmt = $pdo->prepare("UPDATE club_members SET password_hash = ? WHERE id = ?");
    $stmt->execute([$hash, $memberId]);
    echo json_encode(['success' => true, 'password' => $newPass]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
