<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}
require_once 'config/db.php';

// Handle Moderation Actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];
    
    if ($action === 'approve') {
        $pdo->prepare("UPDATE reviews SET status = 'approved' WHERE id = ?")->execute([$id]);
        $msg = "Avis approuvé !";
    } elseif ($action === 'reject') {
        $pdo->prepare("UPDATE reviews SET status = 'rejected' WHERE id = ?")->execute([$id]);
        $msg = "Avis rejeté.";
    } elseif ($action === 'delete') {
        $pdo->prepare("DELETE FROM reviews WHERE id = ?")->execute([$id]);
        $msg = "Avis supprimé définitivement.";
    }
}

// Fetch Reviews
$pending = $pdo->query("SELECT * FROM reviews WHERE status = 'pending' ORDER BY created_at DESC")->fetchAll();
$approved = $pdo->query("SELECT * FROM reviews WHERE status = 'approved' ORDER BY created_at DESC LIMIT 20")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<?php include 'includes/header.php'; ?>
<body class="bg-gray-50">
    <div class="flex h-screen overflow-hidden">
        <?php include 'includes/nav.php'; ?>
        <main class="flex-1 overflow-y-auto p-10">
            <h1 class="text-3xl font-black mb-6">Gestion des Avis Clients</h1>

            <?php if (isset($msg)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                    <?= $msg ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Pending Reviews -->
                <div>
                    <h2 class="text-xl font-bold mb-4 text-orange-600">Avis en attente (<?= count($pending) ?>)</h2>
                    <div class="space-y-4">
                        <?php foreach ($pending as $r): ?>
                            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                                <div class="flex justify-between items-start mb-2">
                                    <h3 class="font-bold"><?= htmlspecialchars($r['name']) ?></h3>
                                    <span class="text-yellow-500 font-bold"><?= str_repeat('⭐', $r['stars']) ?></span>
                                </div>
                                <p class="text-gray-600 text-sm mb-4 italic">"<?= nl2br(htmlspecialchars($r['comment'])) ?>"</p>
                                <div class="flex gap-2">
                                    <a href="?action=approve&id=<?= $r['id'] ?>" class="bg-green-500 text-white px-3 py-1.5 rounded text-xs font-bold hover:bg-green-600">Approuver</a>
                                    <a href="?action=reject&id=<?= $r['id'] ?>" class="bg-red-100 text-red-600 px-3 py-1.5 rounded text-xs font-bold hover:bg-red-200">Rejeter</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if(empty($pending)) echo '<p class="text-gray-400 italic text-sm">Aucun avis en attente.</p>'; ?>
                    </div>
                </div>

                <!-- Latest Approved -->
                <div>
                    <h2 class="text-xl font-bold mb-4 text-green-600">Derniers Avis Approuvés</h2>
                    <div class="space-y-4">
                        <?php foreach ($approved as $r): ?>
                            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 opacity-80">
                                <div class="flex justify-between items-start mb-2">
                                    <h3 class="font-bold"><?= htmlspecialchars($r['name']) ?></h3>
                                    <span class="text-xs text-gray-400"><?= date('d/m/Y', strtotime($r['created_at'])) ?></span>
                                </div>
                                <div class="text-yellow-500 text-xs mb-2"><?= str_repeat('⭐', $r['stars']) ?></div>
                                <p class="text-gray-600 text-xs italic">"<?= nl2br(htmlspecialchars($r['comment'])) ?>"</p>
                                <div class="mt-4">
                                    <a href="?action=delete&id=<?= $r['id'] ?>" onclick="return confirm('Supprimer ?')" class="text-red-400 hover:text-red-600 text-[10px] font-bold uppercase underline">Supprimer</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
