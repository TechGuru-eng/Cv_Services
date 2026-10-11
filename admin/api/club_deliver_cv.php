<?php
require_once '../config/db.php';
session_start();

if (!isset($_SESSION['admin'])) {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestId = (string)($_POST['request_id'] ?? '');
    if ($requestId === '') {
        die('Identifiant de demande invalide.');
    }

    $deliveredPath = '';
    if (isset($_FILES['optimized_cv']) && $_FILES['optimized_cv']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../../uploads/cv_delivered/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileName = strtolower(basename($_FILES['optimized_cv']['name']));
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        $allowedExtensions = ['pdf', 'doc', 'docx'];

        if (!in_array($extension, $allowedExtensions, true)) {
            die('Type de fichier non autorisé. Seuls les PDF, DOC et DOCX sont acceptés.');
        }

        if ($_FILES['optimized_cv']['size'] > 10 * 1024 * 1024) {
            die('Le fichier est trop volumineux. La taille maximale est de 10 Mo.');
        }

        $safeFileName = 'club_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $requestId) . '_' . time() . '.' . $extension;
        $destination = $uploadDir . $safeFileName;

        if (move_uploaded_file($_FILES['optimized_cv']['tmp_name'], $destination)) {
            $deliveredPath = 'uploads/cv_delivered/' . $safeFileName;
        }
    }

    if ($deliveredPath) {
        try {
            $stmt = $pdo->prepare("UPDATE club_cv_requests SET status = 'delivered', delivered_cv_path = ? WHERE id = ?");
            $stmt->execute([$deliveredPath, $requestId]);

            header('Location: ../club_platform.php?tab=requests&success=1');
            exit;
        } catch (PDOException $e) {
            die("Erreur: " . $e->getMessage());
        }
    }

    die("Erreur lors de l'upload du fichier.");
}
