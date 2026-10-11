<?php
// create_admin.php
// This helper intentionally does not use a hardcoded password.
// Set ADMIN_EMAIL and ADMIN_PASSWORD in the environment before running.
require_once 'admin/config/db.php';

$email = getenv('ADMIN_EMAIL') ?: 'admin@hireme.local';
$password = getenv('ADMIN_PASSWORD');

if (!$password) {
    http_response_code(500);
    die("<h1>Configuration requise</h1><p>Définissez les variables d'environnement ADMIN_EMAIL et ADMIN_PASSWORD avant d'exécuter ce script.</p>");
}

$hashed_password = password_hash($password, PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare("SELECT id FROM admins WHERE email = ?");
    $stmt->execute([$email]);

    if ($stmt->fetch()) {
        $update = $pdo->prepare("UPDATE admins SET password = ? WHERE email = ?");
        $update->execute([$hashed_password, $email]);
        echo "<h1>Succès ! Mot de passe mis à jour.</h1>";
    } else {
        $insert = $pdo->prepare("INSERT INTO admins (email, password) VALUES (?, ?)");
        $insert->execute([$email, $hashed_password]);
        echo "<h1>Succès ! Admin créé.</h1>";
    }

    echo "<p>Email: <strong>" . htmlspecialchars($email) . "</strong></p>";
    echo "<p><a href='admin/login.php'>Se connecter maintenant</a></p>";
} catch (PDOException $e) {
    echo "Erreur SQL: " . htmlspecialchars($e->getMessage());
}
?>