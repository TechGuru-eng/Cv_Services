<?php
// admin/api/track_relance.php
session_start();
if (!isset($_SESSION['admin'])) exit('Unauthorized');

require_once '../config/db.php';

$id = $_GET['id'] ?? null;
$type = $_GET['type'] ?? 'standard';
$url = $_GET['url'] ?? null;

if ($id && $url) {
    try {
        // Increment followup_stage and update last_followup
        $stmt = $pdo->prepare("UPDATE prospects SET followup_stage = followup_stage + 1, last_followup = NOW(), updated_at = NOW() WHERE id = ?");
        $stmt->execute([$id]);
        
        // Redirect to the actual WhatsApp URL
        header("Location: " . $url);
        exit;
    } catch (Exception $e) {
        die("Error tracking relance: " . $e->getMessage());
    }
} else {
    die("Invalid request.");
}
?>
