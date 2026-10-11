<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}
require_once 'config/db.php';
require_once __DIR__ . '/includes/admin-page-schema.php';
ensureAdminPageTable($pdo, 'before_after_cvs');
require_once 'includes/csrf.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') verify_csrf();

$msg = '';
if (isset($_GET['masked'])) $msg = 'Image masquée publiée. Les modifications sont visibles dans la galerie avant/après.';
$error = '';

// Handle Upload / Create
if (isset($_POST['upload_ba'])) {
    $category = trim($_POST['category'] ?? '💼 Gestion & Direction');
    $beforeTag = 'CV avant';
    $beforeScore = '';
    $beforeQuote = '';
    
    $afterTag = 'CV après';
    $afterScore = '';
    $afterBadge = '';
    $afterQuote = '';
    $displayOrder = (int)$pdo->query('SELECT COALESCE(MAX(display_order), 0) + 1 FROM before_after_cvs')->fetchColumn();

    // Validate files
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    $targetDir = '../uploads/before_after/';
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $beforePath = null;
    $afterPath = null;

    if (isset($_FILES['before_img']) && $_FILES['before_img']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['before_img']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $fname = 'ba-before-' . time() . '-' . uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['before_img']['tmp_name'], $targetDir . $fname)) {
                $beforePath = 'uploads/before_after/' . $fname;
            }
        }
    }

    if (isset($_FILES['after_img']) && $_FILES['after_img']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['after_img']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $fname = 'ba-after-' . time() . '-' . uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['after_img']['tmp_name'], $targetDir . $fname)) {
                $afterPath = 'uploads/before_after/' . $fname;
            }
        }
    }

    if ($beforePath && $afterPath) {
        $stmt = $pdo->prepare("INSERT INTO before_after_cvs (category, before_img, before_tag, before_score, before_quote, after_img, after_tag, after_score, after_badge, after_quote, display_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
        $stmt->execute([$category, $beforePath, $beforeTag, $beforeScore, $beforeQuote, $afterPath, $afterTag, $afterScore, $afterBadge, $afterQuote, $displayOrder]);
        $msg = "Comparaison Avant / Après ajoutée avec succès !";
    } else {
        $error = "Veuillez fournir à la fois l'image AVANT et l'image APRÈS au format JPG, PNG ou WEBP.";
    }
}

// Handle Delete
if (isset($_POST['delete_id'])) {
    $id = intval($_POST['delete_id']);
    $stmt = $pdo->prepare("SELECT before_img, after_img FROM before_after_cvs WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if ($row) {
        if (strpos($row['before_img'], 'uploads/before_after/') === 0 && file_exists('../' . $row['before_img'])) {
            unlink('../' . $row['before_img']);
        }
        if (strpos($row['after_img'], 'uploads/before_after/') === 0 && file_exists('../' . $row['after_img'])) {
            unlink('../' . $row['after_img']);
        }
        $pdo->prepare("DELETE FROM before_after_cvs WHERE id = ?")->execute([$id]);
        $msg = "Élément supprimé avec succès.";
    }
}

// Handle Toggle Active
if (isset($_POST['toggle_id'])) {
    $id = intval($_POST['toggle_id']);
    $pdo->prepare("UPDATE before_after_cvs SET is_active = 1 - is_active WHERE id = ?")->execute([$id]);
    $msg = "Statut mis à jour.";
}

// Fetch All Items
$items = $pdo->query("SELECT * FROM before_after_cvs ORDER BY display_order ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<?php include 'includes/header.php'; ?>
<body class="bg-gray-50">
    <div class="flex h-screen overflow-hidden">
        <?php include 'includes/nav.php'; ?>
        
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                <div>
                    <h1 class="text-3xl font-extrabold text-gray-800 flex items-center gap-3">
                        Avant / Après CV
                    </h1>
                    <p class="text-sm text-gray-500 mt-1">Ajoutez les images avant et après pour la page d’accueil. Rotation toutes les 20 secondes.</p>
                </div>

            </div>

            <?php if ($msg): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-xl mb-4 font-semibold"><?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-xl mb-4 font-semibold"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <!-- Form: Add new Before/After Pair -->
            <div class="bg-white shadow-sm border border-gray-200 rounded-2xl p-6 mb-8">
                <h2 class="text-lg font-extrabold text-gray-800 mb-4 flex items-center gap-2">
                    Ajouter une comparaison
                </h2>
                <form method="POST" enctype="multipart/form-data" class="space-y-6">
                    <?php csrf_field(); ?>
                    <div class="max-w-xl">
                        <div>
                            <label class="block text-gray-700 text-xs font-bold uppercase tracking-wider mb-1">Catégorie / Métier</label>
                            <input type="text" name="category" required class="border border-gray-300 rounded-xl w-full py-2.5 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Ex: 💻 Ingénierie & Tech">
                        </div>

                    </div>

                    <!-- Side-by-side Uploads: Before & After -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 pt-2">
                        <!-- Before Section -->
                        <div class="bg-red-50/50 border border-red-200 rounded-2xl p-5 space-y-4">
                            <h3 class="text-sm font-extrabold text-red-700 uppercase tracking-wider flex items-center gap-2">
                                CV avant
                            </h3>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Image du CV Avant *</label>
                                <input type="file" name="before_img" required accept="image/jpeg,image/png,image/webp" class="border border-gray-300 rounded-xl w-full py-2 px-3 text-sm bg-white">
                            </div>
                        </div>

                        <!-- After Section -->
                        <div class="bg-emerald-50/50 border border-emerald-200 rounded-2xl p-5 space-y-4">
                            <h3 class="text-sm font-extrabold text-emerald-800 uppercase tracking-wider flex items-center gap-2">
                                CV après
                            </h3>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Image du CV Après *</label>
                                <input type="file" name="after_img" required accept="image/jpeg,image/png,image/webp" class="border border-gray-300 rounded-xl w-full py-2 px-3 text-sm bg-white">
                            </div>
                        </div>
                    </div>

                    <div class="text-right">
                        <button type="submit" name="upload_ba" class="bg-blue-600 hover:bg-blue-700 text-white font-extrabold py-3 px-8 rounded-xl shadow-md transition-all text-sm">
                            Enregistrer la comparaison
                        </button>
                    </div>
                </form>
            </div>

            <!-- List of Existing Items -->
            <div class="bg-white shadow-sm border border-gray-200 rounded-2xl p-6">
                <h2 class="text-lg font-extrabold text-gray-800 mb-4">Comparaisons (<?= count($items) ?>)</h2>
                
                <?php if (!$items): ?>
                    <p class="text-gray-500">Aucune comparaison. Ajoutez vos deux premières images ci-dessus.</p>
                <?php endif; ?>
                <div class="space-y-6">
                    <?php foreach ($items as $item): ?>
                    <div class="border border-gray-200 rounded-2xl p-5 bg-gray-50/50 flex flex-col lg:flex-row items-center gap-6 justify-between">
                        <div class="flex-1 w-full">
                            <div class="flex items-center gap-3 mb-3">
                                <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-extrabold"><?= htmlspecialchars($item['category']) ?></span>
                                <?php if ($item['is_active']): ?>
                                    <span class="px-2 py-0.5 bg-green-100 text-green-700 text-[10px] font-bold rounded-full">Actif</span>
                                <?php else: ?>
                                    <span class="px-2 py-0.5 bg-gray-200 text-gray-600 text-[10px] font-bold rounded-full">Inactif</span>
                                <?php endif; ?>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- Before Thumb -->
                                <div class="bg-white p-3 rounded-xl border border-red-100 flex gap-3 items-center">
                                    <img src="../<?= htmlspecialchars($item['before_img']) ?>" alt="Avant" class="w-16 h-20 object-cover rounded-lg border border-gray-200 shrink-0 shadow-sm">
                                    <a href="before-after-privacy.php?id=<?= (int)$item['id'] ?>&amp;side=before" class="text-blue-600 text-xs font-bold underline">Masquer / modifier avant</a>
                                    <div class="text-sm font-semibold">Avant</div>
                                </div>

                                <!-- After Thumb -->
                                <div class="bg-white p-3 rounded-xl border border-emerald-100 flex gap-3 items-center">
                                    <img src="../<?= htmlspecialchars($item['after_img']) ?>" alt="Après" class="w-16 h-20 object-cover rounded-lg border border-gray-200 shrink-0 shadow-sm">
                                    <a href="before-after-privacy.php?id=<?= (int)$item['id'] ?>&amp;side=after" class="text-blue-600 text-xs font-bold underline">Masquer / modifier après</a>
                                    <div class="text-sm font-semibold">Après</div>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-2 shrink-0">
                            <form method="POST" class="inline">
                    <?php csrf_field(); ?>
                                <input type="hidden" name="toggle_id" value="<?= $item['id'] ?>">
                                <button type="submit" class="px-4 py-2 text-xs font-bold rounded-xl border border-gray-300 hover:bg-gray-100 transition-all">
                                    <?= $item['is_active'] ? 'Désactiver' : 'Activer' ?>
                                </button>
                            </form>
                            <form method="POST" class="inline" onsubmit="return confirm('Supprimer définitivement cette comparaison ?');">
                    <?php csrf_field(); ?>
                                <input type="hidden" name="delete_id" value="<?= $item['id'] ?>">
                                <button type="submit" class="px-4 py-2 text-xs font-bold text-red-600 rounded-xl border border-red-200 hover:bg-red-50 transition-all">
                                    Supprimer
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
