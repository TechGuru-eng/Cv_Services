<?php
require 'db.php';

if (!isset($_GET['id'])) {
    die("ID manquant.");
}

$id = (int)$_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM purchases WHERE id = ?");
$stmt->execute([$id]);
$purchase = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$purchase) {
    die("Commande introuvable.");
}

if ($purchase['status'] !== 'approved') {
    $updateStmt = $pdo->prepare("UPDATE purchases SET status = 'approved' WHERE id = ?");
    $updateStmt->execute([$id]);
}

header("Location: ../admin/index.php?success=approved");
exit();
?>
