<?php
// create_admin.php - Run this ONCE to verify/reset admin password
require_once 'admin/config/db.php';

$email = 'moncvpro@hiremeguide.com'; // Admin Email
$password = 'admin123';               // Desired Password

// Hash password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

try {
    // 1. Check if exists
    $stmt = $pdo->prepare("SELECT id FROM admins WHERE email = ?");
    $stmt->execute([$email]);

    if ($stmt->fetch()) {
        // Update existing
        $update = $pdo->prepare("UPDATE admins SET password = ? WHERE email = ?");
        $update->execute([$hashed_password, $email]);
        echo "<h1>Succès ! Mot de passe mis à jour.</h1>";
    } else {
        // Create new
        $insert = $pdo->prepare("INSERT INTO admins (email, password) VALUES (?, ?)");
        $insert->execute([$email, $hashed_password]);
        echo "<h1>Succès ! Admin créé.</h1>";
    }

    echo "<p>Email: <strong>$email</strong></p>";
    echo "<p>Pass: <strong>$password</strong></p>";
    echo "<p><a href='admin/login.php'>Se connecter maintenant</a></p>";

} catch (PDOException $e) {
    echo "Erreur SQL: " . $e->getMessage();
}
?>