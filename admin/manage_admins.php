<?php
// admin/manage_admins.php
require_once 'auth.php';
require_once 'config/db.php';

$message = "";

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add_admin') {
            $email = trim($_POST['email']);
            $password = $_POST['password'];

            if (!empty($email) && !empty($password)) {
                // Check if email exists
                $stmt = $pdo->prepare("SELECT id FROM admins WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $message = "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4'>Cet email est déjà utilisé.</div>";
                } else {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO admins (email, password) VALUES (?, ?)");
                    if ($stmt->execute([$email, $hashed_password])) {
                         $message = "<div class='bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4'>Administrateur ajouté avec succès.</div>";
                    } else {
                         $message = "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4'>Erreur lors de l'ajout.</div>";
                    }
                }
            } else {
                $message = "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4'>Veuillez remplir tous les champs.</div>";
            }
        } elseif ($_POST['action'] === 'delete_admin' && isset($_POST['admin_id'])) {
             // Prevent deleting the currently logged in admin or specific super admin if needed
             // checking if it's not the last admin could be good too, but kept simple for now
             $idToDelete = $_POST['admin_id'];
             
             // Optional: Don't let them delete themselves? 
             // Ideally we'd compare with $_SESSION['admin_id'] if we stored it. 
             // For now just basic delete.
             
             $stmt = $pdo->prepare("DELETE FROM admins WHERE id = ?");
             if ($stmt->execute([$idToDelete])) {
                 $message = "<div class='bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4'>Administrateur supprimé.</div>";
             }
        }
    }
}

// Fetch Admins
$stmt = $pdo->query("SELECT * FROM admins ORDER BY created_at DESC");
$admins = $stmt->fetchAll();
?>
<?php include 'includes/header.php'; ?>
<body class="bg-gray-50 text-gray-900">

    <div class="flex h-screen overflow-hidden">
        <?php include 'includes/nav.php'; ?>

        <div class="relative flex flex-col flex-1 overflow-y-auto overflow-x-hidden">
            <!-- Header -->
            <header class="bg-white shadow-sm sticky top-0 z-30">
                <div class="px-6 py-4">
                    <h2 class="text-xl font-semibold text-gray-800">Gestion des Administrateurs</h2>
                </div>
            </header>

            <main class="w-full flex-grow p-6">
                <?= $message ?>

                <!-- Add New Admin Card -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-8 max-w-2xl">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Ajouter un nouvel administrateur</h3>
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="add_admin">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input type="email" name="email" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Mot de passe</label>
                            <input type="text" name="password" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
                        </div>
                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-colors">
                            Créer l'administrateur
                        </button>
                    </form>
                </div>

                <!-- Existing Admins List -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h3 class="text-lg font-semibold text-gray-800">Administrateurs existants</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 text-gray-600 text-sm">
                                    <th class="px-6 py-3 font-medium">ID</th>
                                    <th class="px-6 py-3 font-medium">Email</th>
                                    <th class="px-6 py-3 font-medium">Créé le</th>
                                    <th class="px-6 py-3 font-medium text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php foreach ($admins as $admin): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 text-sm text-gray-500">#<?= $admin['id'] ?></td>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-800"><?= htmlspecialchars($admin['email']) ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-500"><?= $admin['created_at'] ?? 'N/A' ?></td>
                                    <td class="px-6 py-4 text-right">
                                        <form method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet administrateur ?');" class="inline">
                                            <input type="hidden" name="action" value="delete_admin">
                                            <input type="hidden" name="admin_id" value="<?= $admin['id'] ?>">
                                            <button type="submit" class="text-red-500 hover:text-red-700 text-sm font-medium transition-colors">
                                                Supprimer
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </main>
        </div>
    </div>
</body>
</html>
