<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}
require_once 'config/db.php';

$message = '';
$messageType = '';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'create') {
        $announcement_message = $_POST['message'];
        $type = $_POST['type'];
        $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : NULL;
        $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : NULL;
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        $stmt = $pdo->prepare("INSERT INTO announcements (message, type, start_date, end_date, is_active) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$announcement_message, $type, $start_date, $end_date, $is_active])) {
            $message = "Annonce créée avec succès !";
            $messageType = "success";
        } else {
            $message = "Erreur lors de la création.";
            $messageType = "error";
        }
    }
}

if (isset($_GET['delete_id'])) {
    $stmt = $pdo->prepare("DELETE FROM announcements WHERE id = ?");
    $stmt->execute([$_GET['delete_id']]);
    header('Location: announcements.php');
    exit;
}

if (isset($_GET['toggle_id'])) {
    $stmt = $pdo->prepare("UPDATE announcements SET is_active = NOT is_active WHERE id = ?");
    $stmt->execute([$_GET['toggle_id']]);
    header('Location: announcements.php');
    exit;
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
                <form action="announcements.php" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="md:col-span-2">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Message</label>
                        <textarea name="message" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline h-24" placeholder="Ex: Joyeux Noël à tous nos clients ! Profitez de -50%..."></textarea>
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Type / Thème</label>
                        <select name="type" class="shadow border rounded w-full py-2 px-3 text-gray-700 focus:outline-none focus:shadow-outline">
                            <option value="info">Info (Bleu)</option>
                            <option value="success">Succès (Vert)</option>
                            <option value="warning">Attention (Jaune)</option>
                            <option value="danger">Danger (Rouge)</option>
                            <option value="new_year">Nouvel An (Festif)</option>
                            <option value="christmas">Noël (Festif)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Actif Immédiatement ?</label>
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
                                    <?php if ($row['is_active']): ?>
                                        <span class="relative inline-block px-3 py-1 font-semibold text-green-900 leading-tight">
                                            <span aria-hidden class="absolute inset-0 bg-green-200 opacity-50 rounded-full"></span>
                                            <span class="relative">Actif</span>
                                        </span>
                                    <?php else: ?>
                                        <span class="relative inline-block px-3 py-1 font-semibold text-red-900 leading-tight">
                                            <span aria-hidden class="absolute inset-0 bg-red-200 opacity-50 rounded-full"></span>
                                            <span class="relative">Inactif</span>
                                        </span>
                                    <?php endif; ?>
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
                                    <a href="announcements.php?toggle_id=<?= $row['id'] ?>" class="text-blue-600 hover:text-blue-900 mr-3" title="Activer/Désactiver">
                                        <i class="fas fa-power-off"></i>
                                    </a>
                                    <a href="announcements.php?delete_id=<?= $row['id'] ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Êtes-vous sûr ?')" title="Supprimer">
                                        <i class="fas fa-trash"></i>
                                    </a>
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
