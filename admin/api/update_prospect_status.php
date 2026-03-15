<?php
require_once '../auth.php';
require_once '../config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $status = $_POST['status'] ?? null;

    if ($id && $status) {
        try {
            // Get current name for logging
            $stmt = $pdo->prepare("SELECT name FROM prospects WHERE id = ?");
            $stmt->execute([$id]);
            $name = $stmt->fetchColumn();

            $stmt = $pdo->prepare("UPDATE prospects SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$status, $id]);

            // Log activity
            log_activity("CRM: État Changé", "Le prospect $name est passé à l'état: $status");

            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Missing data']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid method']);
}
