<?php
/**
 * URL Shortener Redirection Handler
 * Receives the code, logs the click count, and redirects to destination.
 */

require_once __DIR__ . '/admin/config/db.php';

$code = trim($_GET['c'] ?? '');

if (empty($code)) {
    header("Location: index.html");
    exit;
}

try {
    // 1. Fetch destination URL from database
    $stmt = $pdo->prepare("SELECT destination_url FROM short_links WHERE code = ? LIMIT 1");
    $stmt->execute([$code]);
    $destination = $stmt->fetchColumn();

    if ($destination) {
        // 2. Increment click count in background
        $stmtUp = $pdo->prepare("UPDATE short_links SET clicks_count = clicks_count + 1 WHERE code = ?");
        $stmtUp->execute([$code]);

        // 3. Perform redirect
        header("Location: " . $destination);
        exit;
    } else {
        // Fallback to error or index
        header("Location: index.html");
        exit;
    }
} catch (Exception $e) {
    // Fail-safe redirect
    header("Location: index.html");
    exit;
}
