<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}
require_once 'config/db.php';

$message = '';

// Handle Password Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($new !== $confirm) {
        $message = '<div class="bg-red-100 text-red-700 p-3 rounded mb-4">Les mots de passe ne correspondent pas.</div>';
    } else {
        $stmt = $pdo->prepare("SELECT password FROM admins WHERE id = ?");
        $stmt->execute([$_SESSION['admin_id']]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($current, $admin['password'])) {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $update = $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?");
            $update->execute([$hash, $_SESSION['admin_id']]);
            $message = '<div class="bg-green-100 text-green-700 p-3 rounded mb-4">Mot de passe mis à jour avec succès !</div>';
        } else {
            $message = '<div class="bg-red-100 text-red-700 p-3 rounded mb-4">Mot de passe actuel incorrect.</div>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Paramètres - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50">
    <div class="flex h-screen overflow-hidden">

        <?php include 'includes/nav.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
            <h1 class="text-3xl font-bold text-gray-800 mb-6">Paramètres</h1>

            <?= $message ?>

            <div class="bg-white shadow rounded-lg p-6 max-w-lg">
                <h2 class="text-xl font-bold mb-4 border-b pb-2">Changer le mot de passe</h2>
                <form method="POST">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Mot de passe actuel</label>
                        <input type="password" name="current_password" required
                            class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Nouveau mot de passe</label>
                        <input type="password" name="new_password" required
                            class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="mb-6">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Confirmer le nouveau mot de
                            passe</label>
                        <input type="password" name="confirm_password" required
                            class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <button type="submit" name="update_password"
                        class="bg-blue-600 text-white font-bold py-2 px-4 rounded hover:bg-blue-700 transition">
                        Mettre à jour
                    </button>
                </form>
            </div>
        </main>
    </div>
</body>

</html>