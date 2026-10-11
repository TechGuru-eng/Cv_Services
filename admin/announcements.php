<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}
require_once 'config/db.php';
require_once 'includes/csrf.php';

$message = '';
$messageType = '';

// All publication and management actions require a valid admin session and CSRF token.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        $action = $_POST['action'] ?? '';
        if ($action === 'create') {
            $text = trim($_POST['message'] ?? '');
            $type = $_POST['type'] ?? 'info';
            if ($text === '' || mb_strlen($text) > 2000) throw new InvalidArgumentException('Le message doit contenir entre 1 et 2000 caractères.');
            if (!in_array($type, ['info', 'success', 'warning', 'danger', 'new_year', 'christmas'], true)) throw new InvalidArgumentException('Thème invalide.');
            $dates = [];
            foreach (['start_date', 'end_date'] as $field) {
                $raw = $_POST[$field] ?? '';
                $date = $raw === '' ? null : DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $raw);
                if ($raw !== '' && (!$date || $date->format('Y-m-d\TH:i') !== $raw)) throw new InvalidArgumentException('Date invalide.');
                $dates[$field] = $date ? $date->format('Y-m-d H:i:s') : null;
            }
            if ($dates['end_date'] && $dates['end_date'] <= ($dates['start_date'] ?: date('Y-m-d H:i:s'))) throw new InvalidArgumentException('La fin doit être après le début de publication.');
            $stmt = $pdo->prepare('INSERT INTO announcements (message, type, start_date, end_date, is_active) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$text, $type, $dates['start_date'], $dates['end_date'], isset($_POST['is_active']) ? 1 : 0]);
            $message = 'Annonce enregistrée. Elle sera affichée pendant les dates prévues si elle est activée.';
            $messageType = 'success';
        } elseif (in_array($action, ['delete', 'toggle'], true)) {
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
            if (!$id || $id < 1) throw new InvalidArgumentException('Annonce invalide.');
            $sql = $action === 'delete' ? 'DELETE FROM announcements WHERE id = ?' : 'UPDATE announcements SET is_active = NOT is_active WHERE id = ?';
            $pdo->prepare($sql)->execute([$id]);
            header('Location: announcements');
            exit;
        }
    } catch (InvalidArgumentException $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}

// Fetch Announcements
$stmt = $pdo->query("SELECT * FROM announcements ORDER BY created_at DESC");
$announcements = $stmt->fetchAll();
?>
<?php include 'includes/header.php'; ?>
<body class="bg-gray-50">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <?php include 'includes/nav.php'; ?>

        <!-- Main Content -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
            <h1 class="text-3xl font-bold text-gray-800 mb-6">Gestion des Annonces (Popups/Bannières)</h1>

            <?php if ($message): ?>
                <div class="p-4 mb-4 text-sm text-white rounded-lg <?= $messageType === 'success' ? 'bg-green-600' : 'bg-red-600' ?>" role="alert">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <!-- Formulaire Création -->
            <div class="bg-white shadow-md rounded-lg p-6 mb-8">
                <h2 class="text-xl font-semibold mb-4">Créer une nouvelle annonce</h2>
                <p class="text-sm text-gray-600 mb-4">Affichage dans la bannière en haut de l’accueil. Le thème Urgent ouvre aussi un popup une fois par session. La dernière annonce publiée et disponible est prioritaire. Dates en heure du Cameroun (UTC+1).</p>
                <form action="announcements.php" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php csrf_field(); ?>
                    <input type="hidden" name="action" value="create">
                    
                    <div class="md:col-span-2">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Message</label>
                        <textarea name="message" required maxlength="2000" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline h-24" placeholder="Ex: Joyeux Noël à tous nos clients ! Profitez de -50%..."></textarea>
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Type / Thème</label>
                        <select name="type" class="shadow border rounded w-full py-2 px-3 text-gray-700 focus:outline-none focus:shadow-outline">
                            <option value="info">Info (Bleu)</option>
                            <option value="success">Succès (Vert)</option>
                            <option value="warning">Attention (Jaune)</option>
                            <option value="danger">Urgent (Rouge + popup)</option>
                            <option value="new_year">Nouvel An (Festif)</option>
                            <option value="christmas">Noël (Festif)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Annonce activée ?</label>
                        <div class="mt-2">
                            <label class="inline-flex items-center">
                                <input type="checkbox" name="is_active" class="form-checkbox h-5 w-5 text-blue-600" checked>
                                <span class="ml-2 text-gray-700">Oui</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Date de début (Optionnel)</label>
                        <input type="datetime-local" name="start_date" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Date de fin (Optionnel)</label>
                        <input type="datetime-local" name="end_date" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>

                    <div class="md:col-span-2">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition duration-150 ease-in-out">
                            Publier l'annonce
                        </button>
                    </div>
                </form>
            </div>

            <!-- Liste des annonces -->
            <div class="bg-white shadow-md rounded-lg overflow-hidden overflow-x-auto">
                <table class="min-w-full leading-normal">
                    <thead>
                        <tr>
                            <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">État</th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Message</th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Type</th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Dates</th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($announcements as $row): ?>
                            <tr>
                                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                    <?php
                                    $now = date('Y-m-d H:i:s');
                                    $status = !$row['is_active'] ? 'Inactif' : (($row['end_date'] && $row['end_date'] < $now) ? 'Expiré' : (($row['start_date'] && $row['start_date'] > $now) ? 'Programmé' : 'Disponible'));
                                    ?>
                                    <span class="inline-block px-3 py-1 rounded-full bg-gray-100 font-semibold"><?= $status ?></span>
                                </td>
                                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                    <p class="text-gray-900 whitespace-no-wrap"><?= htmlspecialchars(substr($row['message'], 0, 50)) ?>...</p>
                                </td>
                                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                    <span class="inline-block bg-gray-200 rounded-full px-3 py-1 text-sm font-semibold text-gray-700 mr-2"><?= htmlspecialchars($row['type']) ?></span>
                                </td>
                                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                    <div class="text-xs">Début: <?= $row['start_date'] ? $row['start_date'] : '-' ?></div>
                                    <div class="text-xs">Fin: <?= $row['end_date'] ? $row['end_date'] : '-' ?></div>
                                </td>
                                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                    <form method="POST" class="inline">
                                        <?php csrf_field(); ?>
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                        <button class="text-blue-600 mr-3" title="Activer/Désactiver"><i class="fas fa-power-off"></i></button>
                                    </form>
                                    <form method="POST" class="inline" onsubmit="return confirm('Supprimer cette annonce ?');">
                                        <?php csrf_field(); ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                        <button class="text-red-600" title="Supprimer"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        </main>
    </div>
</body>

</html>
