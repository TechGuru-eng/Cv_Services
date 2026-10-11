<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}
require_once 'config/db.php';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=blog_leads_' . date('Y-m-d') . '.csv');

// Output UTF-8 BOM for Excel compatibility
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');
fputcsv($output, array('ID', 'Nom', 'Email', 'Téléphone', 'Date d\'inscription'));

$stmt = $pdo->query("SELECT id, name, email, phone, created_at FROM blog_subscribers ORDER BY created_at DESC");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, $row);
}
fclose($output);
exit;
?>
