<?php
session_start();
if (!isset($_SESSION['admin'])) {
    die("Unauthorized");
}
require_once '../config/db.php';

$type = $_GET['type'] ?? 'visits';
$filename = "hireme_analytics_" . $type . "_" . date('Y-m-d') . ".csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

$output = fopen('php://output', 'w');

if ($type === 'events') {
    // Export High-Intent Events
    fputcsv($output, ['ID', 'Visit ID', 'Event Name', 'Data', 'Date', 'IP', 'Country']);
    $query = $pdo->query("SELECT e.*, v.ip_address, v.country FROM stats_events e JOIN stats_visits v ON e.visit_id = v.id ORDER BY e.created_at DESC");
    while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $row['id'],
            $row['visit_id'],
            $row['event_name'],
            $row['event_data'],
            $row['created_at'],
            $row['ip_address'],
            $row['country']
        ]);
    }
} else {
    // Export Visits (Default)
    fputcsv($output, ['ID', 'IP', 'Country', 'Page', 'User Agent', 'Unique', 'UTM Source', 'UTM Medium', 'UTM Campaign', 'Date']);
    $query = $pdo->query("SELECT * FROM stats_visits ORDER BY created_at DESC");
    while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $row['id'],
            $row['ip_address'],
            $row['country'],
            $row['page_url'],
            $row['user_agent'],
            $row['is_unique'] ? 'Yes' : 'No',
            $row['utm_source'],
            $row['utm_medium'],
            $row['utm_campaign'],
            $row['created_at']
        ]);
    }
}

fclose($output);
exit;
?>
