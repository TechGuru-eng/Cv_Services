<?php
require_once __DIR__ . '/../../includes/database-connect.php';
try { $pdo = hiremeDatabaseConnect(); }
catch (Throwable $error) {
    error_log('Database Connection Error: ' . $error->getMessage());
    http_response_code(503);
    foreach (headers_list() as $header) {
        if (stripos($header, 'Content-Type: application/json') === 0) exit(json_encode(['success' => false, 'message' => 'Service momentanément indisponible. Réessayez plus tard.']));
    }
    exit('<h1>Service momentanément indisponible</h1><p>Erreur de connexion base de données.</p>');
}
date_default_timezone_set('Africa/Douala');
