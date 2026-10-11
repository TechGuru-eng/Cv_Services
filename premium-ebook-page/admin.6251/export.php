<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403);
    exit("Accès refusé.");
}
require '../api/db.php';

$type = $_GET['type'] ?? 'subscribers';

if ($type === 'subscribers') {
    $data = $pdo->query("SELECT id, full_name, email, whatsapp, city, profession, created_at FROM subscribers ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
    $filename = "precommandes_guide_rh_" . date('Y-m-d') . ".csv";
} else {
    $data = $pdo->query("SELECT id, session_id, email, whatsapp, last_seen FROM visitors WHERE is_converted = 0 AND is_contacted = 0 AND (email IS NOT NULL OR whatsapp IS NOT NULL) ORDER BY last_seen DESC")->fetchAll(PDO::FETCH_ASSOC);
    $filename = "retargeting_guide_rh_" . date('Y-m-d') . ".csv";
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// UTF-8 BOM for Excel
fputs($output, $bom =(chr(0xEF) . chr(0xBB) . chr(0xBF)));

if (count($data) > 0) {
    // Write headers
    fputcsv($output, array_keys($data[0]));
    
    // Write rows
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
} else {
    fputcsv($output, ['Aucune donnée disponible']);
}

fclose($output);
exit();
?>
