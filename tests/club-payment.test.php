<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../admin/config/db.php';
require __DIR__ . '/../includes/club-payment.php';
$pdo->beginTransaction();
try {
    $pdo->prepare("INSERT INTO club_members (full_name, whatsapp, email, password_hash, plan_id, cv_allowance, cv_used, status, expiry_date) VALUES ('Regression fixture', ?, 'fixture@example.invalid', 'fixture', 1, 4, 4, 'active', DATE_SUB(NOW(), INTERVAL 1 DAY))")->execute(['test-' . bin2hex(random_bytes(5))]);
    $memberId = (int)$pdo->lastInsertId();
    $pdo->prepare("INSERT INTO club_payments (member_id, amount, method, transaction_id, proof_file, status) VALUES (?, 5000, 'MTN', ?, '', 'pending')")->execute([$memberId, 'TEST-' . bin2hex(random_bytes(5))]);
    $paymentId = (int)$pdo->lastInsertId();
    try { approve_club_payment($pdo, $paymentId, $memberId + 1); throw new RuntimeException('Wrong member accepted'); }
    catch (InvalidArgumentException $e) {}
    approve_club_payment($pdo, $paymentId, $memberId);
    $member = $pdo->query('SELECT cv_used, expiry_date FROM club_members WHERE id = ' . $memberId)->fetch();
    if ((int)$member['cv_used'] !== 0 || strtotime($member['expiry_date']) <= time()) throw new RuntimeException('Renewal failed');
    try { approve_club_payment($pdo, $paymentId, $memberId); throw new RuntimeException('Duplicate approval accepted'); }
    catch (InvalidArgumentException $e) {}
    echo "Club payment ownership, renewal quota reset and duplicate approval checks passed (fixtures rolled back).\n";
} finally { $pdo->rollBack(); }
