<?php
// admin/config/db.php

$host = 'localhost';
$dbname = 'u158764452_hireme_db'; // CHANGE THIS
$username = 'u158764452_root';    // CHANGE THIS
$password = 'Dreamer.Sophia#27';        // CHANGE THIS

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // SECURITY: Log error to server logs, do NOT show to user
    error_log("Database Connection Error: " . $e->getMessage());
    die("<h1>Service momentanément indisponible</h1><p>Veuillez réessayer plus tard.</p>");
}
?>