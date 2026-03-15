<?php
// admin/config/db.php

$host = 'localhost';
$dbname = 'hireme_db'; // Default local name
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
} catch (PDOException $e) {
    // If local fails, try the Hostinger name
    try {
        $dbname = 'u158764452_hireme_db';
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    } catch (PDOException $e2) {
        error_log("Database Connection Error: " . $e2->getMessage());
        die("<h1>Service momentanément indisponible</h1><p>Erreur de connexion base de données.</p>");
    }
}
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec("set names utf8mb4");

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

// Set App Timezone (UTC+1)
date_default_timezone_set('Africa/Douala');
$pdo->exec("SET time_zone = '+01:00'");

// Security Integration Removed at User Request
?>