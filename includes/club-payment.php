<?php
function approve_club_payment(PDO $pdo, int $paymentId, int $memberId): void {
    if (!$pdo->inTransaction()) throw new LogicException('Payment approval requires a transaction');
    $stmt = $pdo->prepare('SELECT member_id, status FROM club_payments WHERE id = ? FOR UPDATE');
    $stmt->execute([$paymentId]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$payment || (int)$payment['member_id'] !== $memberId || $payment['status'] !== 'pending') {
        throw new InvalidArgumentException('Paiement introuvable, déjà traité ou associé à un autre membre.');
    }
    $stmt = $pdo->prepare("UPDATE club_members SET status = 'active', cv_used = 0, expiry_date = DATE_ADD(GREATEST(COALESCE(expiry_date, NOW()), NOW()), INTERVAL 30 DAY) WHERE id = ?");
    $stmt->execute([$memberId]);
    if ($stmt->rowCount() !== 1) throw new InvalidArgumentException('Membre introuvable.');
    $pdo->prepare("UPDATE club_payments SET status = 'verified' WHERE id = ? AND status = 'pending'")->execute([$paymentId]);
}
