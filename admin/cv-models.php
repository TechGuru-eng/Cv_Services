<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}
require_once 'config/db.php';
require_once __DIR__ . '/includes/csrf.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') verify_csrf();

$msg = '';
$error = '';
if (isset($_GET['masked'])) $msg = 'Image masquée publiée. L’ancienne image publique a également été remplacée.';

// Handle Upload
if (isset($_POST['upload'])) {
    $title = trim($_POST['title']);
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (in_array($ext, $allowed)) {
            // Ensure directory exists
            $targetDir = '../uploads/designs/';
            if (!file_exists($targetDir)) {
                mkdir($targetDir, 0777, true);
            }
            
            $filename = 'design-' . time() . '-' . uniqid() . '.' . $ext;
            $targetPath = $targetDir . $filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                // Save to DB (Store relative path for consistency)
                $dbPath = 'uploads/designs/' . $filename;
                $stmt = $pdo->prepare("INSERT INTO cv_designs (title, image_path, created_at) VALUES (?, ?, NOW())");
                $stmt->execute([$title, $dbPath]);
                $msg = "Design ajouté avec succès !";
            } else {
                $error = "Erreur lors de l'upload du fichier.";
            }
        } else {
            $error = "Format non supporté (JPG, PNG, WEBP uniquement).";
        }
    } else {
        $error = "Veuillez sélectionner une image.";
    }
}

// Handle Delete
if (isset($_POST['delete_id'])) {
    $id = $_POST['delete_id'];
    $stmt = $pdo->prepare("SELECT image_path FROM cv_designs WHERE id = ?");
    $stmt->execute([$id]);
    $design = $stmt->fetch();
    
    if ($design) {
        // Delete file
        $filePath = '../' . $design['image_path'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        // Delete DB entry
        $pdo->prepare("DELETE FROM cv_designs WHERE id = ?")->execute([$id]);
        $msg = "Design supprimé.";
    }
}

// Fetch Designs
$designs = $pdo->query("SELECT * FROM cv_designs ORDER BY created_at DESC")->fetchAll();
?>
<?php include 'includes/header.php'; ?>
<body class="bg-gray-50">
    <div class="flex h-screen overflow-hidden">
        <?php include 'includes/nav.php'; ?>
        
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
            <h1 class="text-3xl font-bold text-gray-800 mb-6">Gestion des Modèles CV</h1> <!-- Added header -->
            
            <?php if($msg): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4"><?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>
            
            <?php if($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <!-- Upload Form -->
            <div class="bg-white shadow rounded-lg p-6 mb-8">
                <h2 class="text-xl font-bold mb-4">Ajouter un nouveau modèle</h2>
                <form method="POST" enctype="multipart/form-data" class="flex gap-4 items-end">
                    <?php csrf_field(); ?>
                    <div class="flex-1">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Titre du modèle</label>
                        <input type="text" name="title" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" placeholder="Ex: Moderne Bleu">
                    </div>
                    <div class="flex-1">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Image (Aperçu)</label>
                        <input type="file" name="image" required accept="image/*" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>
                    <button type="submit" name="upload" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded focus:outline-none focus:shadow-outline h-10">
                        Ajouter
                    </button>
                </form>
            </div>

            <!-- Gallery List -->
            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6">
                <?php foreach($designs as $design): ?>
                    <div class="bg-white rounded-lg shadow overflow-hidden group relative">
                        <img src="../<?= htmlspecialchars($design['image_path']) ?>" alt="<?= htmlspecialchars($design['title']) ?>" class="w-full h-48 object-cover object-top">
                        <div class="p-4">
                            <h3 class="font-bold text-gray-800"><?= htmlspecialchars($design['title']) ?></h3>
                            <p class="text-xs text-gray-500"><?= date('d/m/Y', strtotime($design['created_at'])) ?></p>
                            <a href="cv-model-privacy.php?id=<?= (int)$design['id'] ?>" class="inline-block mt-3 text-sm font-bold text-blue-600 underline">Masquer les informations</a>
                        </div>
                        
                        <form method="POST" onsubmit="return confirm('Confirmer la suppression ?');" class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
                            <?php csrf_field(); ?>
                            <input type="hidden" name="delete_id" value="<?= $design['id'] ?>">
                            <button type="submit" class="bg-red-600 text-white p-2 rounded-full hover:bg-red-700">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php if(empty($designs)): ?>
                <p class="text-center text-gray-500 mt-10">Aucun modèle téléchargé pour le moment.</p>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
