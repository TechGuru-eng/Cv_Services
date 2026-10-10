<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}
require_once 'config/db.php';

$msg = '';
$error = '';

// Handle Upload / Create
if (isset($_POST['upload_ba'])) {
    $category = trim($_POST['category'] ?? '💼 Gestion & Direction');
    $beforeTag = trim($_POST['before_tag'] ?? 'CV Non Conforme ✗');
    $beforeScore = trim($_POST['before_score'] ?? 'Score ATS : 28%');
    $beforeQuote = trim($_POST['before_quote'] ?? '');
    
    $afterTag = trim($_POST['after_tag'] ?? 'CV ATS Optimisé ✓');
    $afterScore = trim($_POST['after_score'] ?? 'Score ATS : 98%');
    $afterBadge = trim($_POST['after_badge'] ?? 'ATS OPTIMISÉ');
    $afterQuote = trim($_POST['after_quote'] ?? '');
    $displayOrder = intval($_POST['display_order'] ?? 1);

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
                        <span>🔄</span> Avant / Après CV (Carrousel Accueil)
                    </h1>
                    <p class="text-sm text-gray-500 mt-1">Gérez les comparaisons CV affichées sur la page d'accueil (rotation automatique toutes les 40 secondes).</p>
                </div>
                <div class="bg-blue-50 border border-blue-200 text-blue-700 px-4 py-2 rounded-xl text-xs font-bold flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-blue-500 animate-pulse"></span>
                    <span>Rotation active : 40 secondes</span>
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
                    <span>➕</span> Ajouter une nouvelle comparaison
                </h2>
                <form method="POST" enctype="multipart/form-data" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 text-xs font-bold uppercase tracking-wider mb-1">Catégorie / Métier</label>
                            <input type="text" name="category" required class="border border-gray-300 rounded-xl w-full py-2.5 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Ex: 💻 Ingénierie & Tech">
                        </div>
                        <div>
                            <label class="block text-gray-700 text-xs font-bold uppercase tracking-wider mb-1">Ordre d'affichage</label>
                            <input type="number" name="display_order" value="<?= count($items) + 1 ?>" min="1" class="border border-gray-300 rounded-xl w-full py-2.5 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>

                    <!-- Side-by-side Uploads: Before & After -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 pt-2">
                        <!-- Before Section -->
                        <div class="bg-red-50/50 border border-red-200 rounded-2xl p-5 space-y-4">
                            <h3 class="text-sm font-extrabold text-red-700 uppercase tracking-wider flex items-center gap-2">
                                <span>❌</span> 1. CV Avant (Rejeté)
                            </h3>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Image du CV Avant *</label>
                                <input type="file" name="before_img" required accept="image/*" class="border border-gray-300 rounded-xl w-full py-2 px-3 text-sm bg-white">
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Tag Avant</label>
                                    <input type="text" name="before_tag" value="CV Ordinaire (Rejeté)" class="border border-gray-300 rounded-xl w-full py-2 px-3 text-xs bg-white">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Score ATS</label>
                                    <input type="text" name="before_score" value="Score ATS : 28%" class="border border-gray-300 rounded-xl w-full py-2 px-3 text-xs bg-white">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Critique / Problème constaté</label>
                                <input type="text" name="before_quote" value="Design dépassé, trop de texte dense, non optimisé pour les robots ATS." class="border border-gray-300 rounded-xl w-full py-2 px-3 text-xs bg-white">
                            </div>
                        </div>

                        <!-- After Section -->
                        <div class="bg-emerald-50/50 border border-emerald-200 rounded-2xl p-5 space-y-4">
                            <h3 class="text-sm font-extrabold text-emerald-800 uppercase tracking-wider flex items-center gap-2">
                                <span>✅</span> 2. CV Après (HireMe Optimisé)
                            </h3>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Image du CV Après *</label>
                                <input type="file" name="after_img" required accept="image/*" class="border border-gray-300 rounded-xl w-full py-2 px-3 text-sm bg-white">
                            </div>
                            <div class="grid grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Tag Après</label>
                                    <input type="text" name="after_tag" value="HireMe CV (Recruté)" class="border border-gray-300 rounded-xl w-full py-2 px-3 text-xs bg-white">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Score ATS</label>
                                    <input type="text" name="after_score" value="Score ATS : 98%" class="border border-gray-300 rounded-xl w-full py-2 px-3 text-xs bg-white">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Badge</label>
                                    <input type="text" name="after_badge" value="ATS OPTIMIZED" class="border border-gray-300 rounded-xl w-full py-2 px-3 text-xs bg-white">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Point fort / Citation résultat</label>
                                <input type="text" name="after_quote" value="Design épuré, mots-clés stratégiques, impact visuel immédiat." class="border border-gray-300 rounded-xl w-full py-2 px-3 text-xs bg-white">
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
                <h2 class="text-lg font-extrabold text-gray-800 mb-4">Comparaisons actives (<?= count($items) ?>)</h2>
                
                <div class="space-y-6">
                    <?php foreach ($items as $item): ?>
                    <div class="border border-gray-200 rounded-2xl p-5 bg-gray-50/50 flex flex-col lg:flex-row items-center gap-6 justify-between">
                        <div class="flex-1 w-full">
                            <div class="flex items-center gap-3 mb-3">
                                <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-extrabold"><?= htmlspecialchars($item['category']) ?></span>
                                <span class="text-xs text-gray-500 font-bold">Ordre : #<?= $item['display_order'] ?></span>
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
                                    <div class="text-xs">
                                        <div class="font-bold text-red-600"><?= htmlspecialchars($item['before_tag']) ?></div>
                                        <div class="text-gray-500 text-[11px]"><?= htmlspecialchars($item['before_score']) ?></div>
                                        <div class="text-gray-400 italic text-[10px] line-clamp-2">"<?= htmlspecialchars($item['before_quote']) ?>"</div>
                                    </div>
                                </div>

                                <!-- After Thumb -->
                                <div class="bg-white p-3 rounded-xl border border-emerald-100 flex gap-3 items-center">
                                    <img src="../<?= htmlspecialchars($item['after_img']) ?>" alt="Après" class="w-16 h-20 object-cover rounded-lg border border-gray-200 shrink-0 shadow-sm">
                                    <div class="text-xs">
                                        <div class="font-bold text-emerald-700"><?= htmlspecialchars($item['after_tag']) ?></div>
                                        <div class="text-gray-500 text-[11px]"><?= htmlspecialchars($item['after_score']) ?> • <span class="font-semibold text-blue-600"><?= htmlspecialchars($item['after_badge']) ?></span></div>
                                        <div class="text-gray-400 italic text-[10px] line-clamp-2">"<?= htmlspecialchars($item['after_quote']) ?>"</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-2 shrink-0">
                            <form method="POST" class="inline">
                                <input type="hidden" name="toggle_id" value="<?= $item['id'] ?>">
                                <button type="submit" class="px-4 py-2 text-xs font-bold rounded-xl border border-gray-300 hover:bg-gray-100 transition-all">
                                    <?= $item['is_active'] ? 'Désactiver' : 'Activer' ?>
                                </button>
                            </form>
                            <form method="POST" class="inline" onsubmit="return confirm('Supprimer définitivement cette comparaison ?');">
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
