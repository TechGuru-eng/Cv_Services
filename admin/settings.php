<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}
require_once 'config/db.php';
require_once 'includes/csrf.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_ip_bans'])) {
    verify_csrf();
    $enabled = ($_POST['ip_bans_enabled'] ?? '1') === '0' ? '0' : '1';
    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('ip_bans_enabled', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    $stmt->execute([$enabled, $enabled]);
    $message = '<div class="bg-green-100 text-green-700 p-3 rounded mb-4">Paramètre de bannissement enregistré.</div>';
}
$stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'ip_bans_enabled'");
$stmt->execute();
$ipBansEnabled = $stmt->fetchColumn() !== '0';

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

// Handle Flash Settings Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_flash'])) {
    $status = $_POST['flash_status'] ?? 'CLOSED';
    $endTime = $_POST['flash_end_time'] ?? '';
    
    // Update Status
    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('flash_status', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    $stmt->execute([$status, $status]);
    
    // Update End Time
    $stmt2 = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('flash_end_time', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    $stmt2->execute([$endTime, $endTime]);

    $message = '<div class="bg-green-100 text-green-700 p-3 rounded mb-4">Configuration Flash mise à jour !</div>';
}


// Handle Token Generation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_token'])) {
    $newToken = bin2hex(random_bytes(8)); // 16 chars random
    $stmt3 = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('flash_access_token', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    $stmt3->execute([$newToken, $newToken]);
    $message = '<div class="bg-blue-100 text-blue-700 p-3 rounded mb-4">Nouveau lien d\'accès généré !</div>';
}

// Handle SMS Settings Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_sms'])) {
    $smsEnabled = $_POST['sms_enabled'] ?? '0';
    $apiKey = $_POST['textbee_api_key'] ?? '';
    $deviceId = $_POST['textbee_device_id'] ?? '';
    
    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('sms_enabled', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    $stmt->execute([$smsEnabled, $smsEnabled]);
    
    $stmt2 = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('textbee_api_key', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    $stmt2->execute([$apiKey, $apiKey]);
    
    $stmt3 = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('textbee_device_id', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    $stmt3->execute([$deviceId, $deviceId]);

    // Send Test SMS if phone is filled
    $testPhone = $_POST['test_phone'] ?? '';
    $testMsg = '';
    if (!empty($testPhone) && $smsEnabled === '1') {
        require_once 'config/sms_helper.php';
        $res = SMSHelper::sendSMS($testPhone, "Test SMS MonCvPro: Votre passerelle TextBee fonctionne parfaitement ! 🚀");
        if ($res['success']) {
            $testMsg = '<span class="text-green-600 block text-xs font-bold mt-1">✓ SMS de test préparé. Approbation requise dans le tableau de bord SMS.</span>';
        } else {
            $testMsg = '<span class="text-red-600 block text-xs font-bold mt-1">✗ Échec de l\'envoi du SMS : ' . htmlspecialchars($res['error']) . '</span>';
        }
    }

    $message = '<div class="bg-green-100 text-green-700 p-3 rounded mb-4">Configuration SMS mise à jour ! ' . $testMsg . '</div>';
}

// Fetch Current Flash Status
$stmtS = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'flash_status'");
$stmtS->execute();
$flashStatus = $stmtS->fetchColumn() ?: 'CLOSED';

// Fetch Flash End Time
$stmtT = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'flash_end_time'");
$stmtT->execute();
$flashEndTime = $stmtT->fetchColumn() ?: '';

// Fetch Secret Token
$stmtK = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'flash_access_token'");
$stmtK->execute();
$flashToken = $stmtK->fetchColumn() ?: '';
$flashLink = $flashToken ? "https://moncvpro.hiremeguide.com/flash-promo?access=" . $flashToken : "Aucun lien actif";

// Fetch SMS Settings
$stmtSE = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'sms_enabled'");
$stmtSE->execute();
$smsEnabled = $stmtSE->fetchColumn() ?: '0';

$stmtSK = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'textbee_api_key'");
$stmtSK->execute();
$textbeeApiKey = $stmtSK->fetchColumn() ?: '';

$stmtSD = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'textbee_device_id'");
$stmtSD->execute();
$textbeeDeviceId = $stmtSD->fetchColumn() ?: '';

?>
<?php include 'includes/header.php'; ?>
<body class="bg-gray-50">
    <div class="flex h-screen overflow-hidden">

        <?php include 'includes/nav.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
            <h1 class="text-3xl font-bold text-gray-800 mb-6">Paramètres</h1>

            <?= $message ?>

            <div class="bg-white shadow rounded-lg p-6 max-w-lg mb-8">
                <h2 class="text-xl font-bold mb-4 border-b pb-2">Bannissement des visiteurs</h2>
                <form method="POST">
                    <?php csrf_field(); ?>
                    <label for="ip_bans_enabled" class="block text-gray-700 text-sm font-bold mb-2">Bannissement par adresse IP</label>
                    <select id="ip_bans_enabled" name="ip_bans_enabled" class="w-full border rounded p-2 mb-3">
                        <option value="1" <?= $ipBansEnabled ? 'selected' : '' ?>>Activé</option>
                        <option value="0" <?= !$ipBansEnabled ? 'selected' : '' ?>>Désactivé</option>
                    </select>
                    <p class="text-sm text-gray-600 mb-4">Désactivé : les visiteurs bannis peuvent revenir et aucun nouveau bannissement automatique n’est créé. Les requêtes suspectes restent bloquées. La liste des IP bannies est conservée et reprend effet à la réactivation.</p>
                    <button type="submit" name="update_ip_bans" value="1" class="bg-blue-600 text-white font-bold px-4 py-2 rounded">Enregistrer</button>
                </form>
            </div>

            <div class="bg-white shadow rounded-lg p-6 max-w-lg mb-8 border border-blue-200">
                <h2 class="text-xl font-bold mb-4 border-b pb-2 text-blue-800">⚡ Flash Page Configuration</h2>
                <form method="POST">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Statut de la vente</label>
                        <select name="flash_status" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="IDLE" <?= $flashStatus === 'IDLE' ? 'selected' : '' ?>>Non planifiée</option>
                            <option value="OPEN" <?= $flashStatus === 'OPEN' ? 'selected' : '' ?>>✅ OPEN (Ouvert)</option>
                            <option value="CLOSED" <?= $flashStatus === 'CLOSED' ? 'selected' : '' ?>>❌ CLOSED (Fermé)</option>
                        </select>
                    </div>
                    <div class="mb-6">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Fin de la vente (Compte à rebours)</label>
                        <input type="datetime-local" name="flash_end_time" value="<?= $flashEndTime ?>" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <p class="text-xs text-gray-500 mt-1">Laissez vide pour masquer le compteur.</p>
                    </div>
                    <div class="flex items-center justify-between mb-8">
                        <button type="submit" name="update_flash" class="bg-blue-600 text-white font-bold py-2 px-4 rounded hover:bg-blue-700 transition">
                            Sauvegarder la config
                        </button>
                    </div>
                </form>

                <!-- Secret Link Section -->
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <h3 class="text-sm font-bold text-yellow-800 mb-2">🔗 Lien d'accès Secret (Changeant)</h3>
                    <p class="text-xs text-gray-600 mb-3">Seul ce lien permet d'accéder à la page. Changez-le régulièrement pour bloquer les anciens accès.</p>
                    
                    <div class="flex items-center gap-2 mb-3">
                        <input type="text" value="<?= $flashLink ?>" readonly class="w-full px-2 py-1 text-xs border rounded bg-white text-gray-500 select-all" onclick="this.select()">
                        <form method="POST" class="shrink-0">
                            <button type="submit" name="generate_token" class="bg-gray-800 text-white text-xs font-bold py-1 px-3 rounded hover:bg-black transition" onclick="return confirm('Attention: L\'ancien lien ne fonctionnera plus. Continuer ?');">
                                🔄 Régénérer
                            </button>
                        </form>
                    </div>
                </div>

                <div class="mt-4 text-right">
                    <a href="flash-products.php" class="text-sm text-blue-600 hover:underline">Gérer les produits Flash &rarr;</a>
                </div>
            </div>

            <!-- SMS Gateway Configuration Card -->
            <div class="bg-white shadow rounded-lg p-6 max-w-lg mb-8 border border-sky-200">
                <h2 class="text-xl font-bold mb-4 border-b pb-2 text-sky-800 flex items-center gap-2">
                    <span>📱</span> Configuration SMS Gateway (TextBee)
                </h2>
                <form method="POST">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Statut de la passerelle SMS</label>
                        <select name="sms_enabled" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-sky-500 bg-white">
                            <option value="0" <?= $smsEnabled === '0' ? 'selected' : '' ?>>❌ Désactivée</option>
                            <option value="1" <?= $smsEnabled === '1' ? 'selected' : '' ?>>✅ Activée</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">TextBee API Key</label>
                        <input type="text" name="textbee_api_key" value="<?= htmlspecialchars($textbeeApiKey) ?>" placeholder="Entrez votre clé API TextBee..." class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-sky-500 bg-white text-xs">
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">TextBee Device ID</label>
                        <input type="text" name="textbee_device_id" value="<?= htmlspecialchars($textbeeDeviceId) ?>" placeholder="Entrez le Device ID de votre Android..." class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-sky-500 bg-white text-xs">
                    </div>
                    <div class="bg-sky-50 border border-sky-100 rounded-lg p-4 mb-6">
                        <h3 class="text-xs font-bold text-sky-800 mb-1.5 flex items-center gap-1">🧪 Préparer un SMS de test</h3>
                        <div class="flex items-center gap-2">
                            <input type="text" name="test_phone" placeholder="Numéro (Ex: 690034617)" class="flex-grow px-3 py-1.5 border rounded focus:outline-none focus:ring-1 focus:ring-sky-500 bg-white text-xs">
                            <p class="text-[10px] text-gray-500 italic shrink-0">(Facultatif)</p>
                        </div>
                    </div>
                    <button type="submit" name="update_sms" class="bg-sky-600 hover:bg-sky-700 text-white font-bold py-2 px-4 rounded transition">
                        Sauvegarder la config SMS
                    </button>
                </form>
            </div>


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
