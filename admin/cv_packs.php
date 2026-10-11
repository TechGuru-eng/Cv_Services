<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}
require_once 'config/db.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/cv-pack-overview.php';

$message = '';
$messageType = '';

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add' || $_POST['action'] === 'edit') {
            $order_id = $_POST['order_id'];
            $full_name = $_POST['full_name'];
            $email = $_POST['email'];
            $pack_link = $_POST['pack_link'];
            $status = $_POST['status'];

            if ($_POST['action'] === 'add') {
                $stmt = $pdo->prepare("INSERT INTO cv_packs (order_id, full_name, email, pack_link, status) VALUES (?, ?, ?, ?, ?)");
                if ($stmt->execute([$order_id, $full_name, $email, $pack_link, $status])) {
                    $message = "Pack CV ajouté avec succès !";
                    $messageType = "success";
                }
            } else {
                $id = $_POST['id'];
                $stmt = $pdo->prepare("UPDATE cv_packs SET order_id=?, full_name=?, email=?, pack_link=?, status=? WHERE id=?");
                if ($stmt->execute([$order_id, $full_name, $email, $pack_link, $status, $id])) {
                    $message = "Pack CV mis à jour !";
                    $messageType = "success";
                }
            }
        } elseif ($_POST['action'] === 'delete') {
            $id = $_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM cv_packs WHERE id = ?");
            if ($stmt->execute([$id])) {
                $message = "Pack CV supprimé.";
                $messageType = "success";
            }
        }
    }
}

// Fetch verified orders for the dropdown
$orders = $pdo->query("SELECT id, name, email FROM orders WHERE payment_status = 'verified' ORDER BY created_at DESC")->fetchAll();

// Fetch existing packs
$search = is_string($_GET['q'] ?? null) ? mb_substr(trim($_GET['q']), 0, 150) : '';
$recoveryFilter = in_array($_GET['recovery'] ?? '', ['added', 'missing'], true) ? $_GET['recovery'] : '';
$overview = cvPackOverview($pdo, $search, $recoveryFilter);
$metrics = $overview['metrics'];
$packConditions = []; $packParams = [];
if ($search !== '') {
    $pattern = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search) . '%';
    $packConditions[] = "(p.full_name LIKE ? ESCAPE '!' OR p.email LIKE ? ESCAPE '!' OR o.order_ref LIKE ? ESCAPE '!' OR o.whatsapp LIKE ? ESCAPE '!')";
    $packParams = array_fill(0, 4, $pattern);
}
$packQuery = $pdo->prepare('SELECT p.*, o.order_ref FROM cv_packs p LEFT JOIN orders o ON p.order_id=o.id' . ($packConditions ? ' WHERE ' . implode(' AND ', $packConditions) : '') . ' ORDER BY p.created_at DESC');
$packQuery->execute($packParams);
$packs = $packQuery->fetchAll();

?>
<?php include 'includes/header.php'; ?>
<body class="bg-gray-50">
    <div class="flex h-screen overflow-hidden">
        <?php include 'includes/nav.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
            <h1 class="text-3xl font-bold text-gray-800 mb-6">Gestion des Packs CV 📦</h1>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
                <?php foreach ([['Clients avec paiement approuvé', $metrics['clients'], $metrics['orders'] . ' commandes approuvées'], ['Clients ajoutés à la récupération', $metrics['added_clients'], $metrics['added_orders'] . ' commandes avec un pack'], ['Clients restant à ajouter', $metrics['missing_clients'], $metrics['missing_orders'] . ' commandes sans pack']] as $card): ?>
                    <div class="bg-white p-4 rounded-xl shadow-sm"><p class="text-sm text-gray-600"><?= $card[0] ?></p><p class="text-3xl font-bold text-blue-600 mt-2"><?= $card[1] ?></p><p class="text-xs text-gray-500 mt-1"><?= $card[2] ?></p></div>
                <?php endforeach; ?>
            </div>
            <p class="text-sm text-gray-600 mb-4">Un client reste à ajouter si au moins une de ses commandes approuvées n’a pas de pack associé. Un pack en attente est ajouté, mais n’est pas encore prêt à récupérer.</p>
            <form method="get" class="bg-white p-4 rounded-xl mb-4 flex flex-wrap items-end gap-3">
                <div class="flex-1"><label for="recovery-search" class="block text-sm font-bold mb-1">Rechercher un client</label><input id="recovery-search" type="search" name="q" maxlength="150" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" placeholder="Nom, email, téléphone ou référence" class="w-full border p-2 rounded-lg"></div>
                <div><label for="recovery-filter" class="block text-sm font-bold mb-1">Liste de récupération</label><select id="recovery-filter" name="recovery" class="border p-2 rounded-lg"><option value="">Toutes les commandes approuvées</option><option value="added" <?= $recoveryFilter === 'added' ? 'selected' : '' ?>>Déjà ajoutées</option><option value="missing" <?= $recoveryFilter === 'missing' ? 'selected' : '' ?>>Pas encore ajoutées</option></select></div>
                <button class="bg-blue-600 text-white px-4 py-2 rounded-lg">Rechercher</button><a href="cv_packs.php" class="text-gray-600 underline py-2">Réinitialiser</a>
            </form>
            <section class="bg-white rounded-xl shadow-sm mb-6 p-4">
                <h2 class="text-lg font-bold mb-3">Suivi des commandes approuvées · <?= count($overview['rows']) ?> résultat(s)</h2>
                <div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead><tr class="text-gray-500"><th class="p-3">Client / Commande</th><th class="p-3">Récupération</th><th class="p-3">Action</th></tr></thead><tbody>
                <?php foreach ($overview['rows'] as $row): ?>
                    <tr class="border-t"><td class="p-3"><div class="font-bold"><?= htmlspecialchars($row['name'] ?? '') ?></div><div><?= htmlspecialchars($row['email'] ?? '') ?> · <?= htmlspecialchars($row['whatsapp'] ?? '') ?></div><div class="text-gray-500"><?= htmlspecialchars($row['order_ref'] ?? (string)$row['id']) ?></div></td>
                    <td class="p-3"><?= !$row['pack_id'] ? 'Pas encore ajouté' : ($row['pack_status'] === 'verified' ? 'Ajouté · Prêt à récupérer' : 'Ajouté · Livraison en attente') ?></td>
                    <td class="p-3"><button type="button" class="text-blue-600 font-bold" onclick='selectRecoveryOrder(<?= htmlspecialchars(json_encode(['id' => $row['id'], 'pack_id' => $row['pack_id']], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8') ?>)'><?= $row['pack_id'] ? 'Modifier le pack' : 'Ajouter le pack' ?></button></td></tr>
                <?php endforeach; ?>
                <?php if (!$overview['rows']): ?><tr><td colspan="3" class="p-6 text-gray-500">Aucune commande approuvée ne correspond à cette recherche.</td></tr><?php endif; ?>
                </tbody></table></div>
            </section>

            <?php if ($message): ?>
                <div class="p-4 mb-4 text-sm text-white rounded-lg <?= $messageType === 'success' ? 'bg-green-600' : 'bg-red-600' ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Add Form -->
                <div class="bg-white p-6 rounded-xl shadow-sm h-fit">
                    <h2 class="text-xl font-bold mb-4 text-blue-600">Ajouter / Modifier un Pack</h2>
                    <form method="POST" class="space-y-4">
                        <?php csrf_field(); ?>
                        <input type="hidden" name="action" id="formAction" value="add">
                        <input type="hidden" name="id" id="packId" value="">
                        
                        <div>
                            <label class="block text-sm font-bold mb-1">Associer à une Commande Vérifiée</label>
                            <select name="order_id" id="orderId" required class="w-full border p-2 rounded-lg" onchange="updateFields(this)">
                                <option value="">--- Sélectionner une commande ---</option>
                                <?php foreach ($orders as $o): ?>
                                    <option value="<?= $o['id'] ?>" data-name="<?= htmlspecialchars($o['name']) ?>" data-email="<?= htmlspecialchars($o['email']) ?>">
                                        <?= htmlspecialchars($o['name']) ?> (<?= htmlspecialchars($o['email']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-bold mb-1">Nom Complet (sur le pack)</label>
                            <input type="text" name="full_name" id="fullName" required class="w-full border p-2 rounded-lg">
                        </div>

                        <div>
                            <label class="block text-sm font-bold mb-1">Email Client</label>
                            <input type="email" name="email" id="email" required class="w-full border p-2 rounded-lg">
                        </div>

                        <div>
                            <label class="block text-sm font-bold mb-1">Lien du Pack (Google Drive / Dropbox)</label>
                            <input type="url" name="pack_link" id="packLink" required class="w-full border p-2 rounded-lg" placeholder="https://...">
                        </div>

                        <div>
                            <label class="block text-sm font-bold mb-1">Statut de Livraison</label>
                            <select name="status" id="status" class="w-full border p-2 rounded-lg">
                                <option value="pending">En attente (Pending)</option>
                                <option value="verified">Vérifié / Prêt (Verified)</option>
                            </select>
                        </div>

                        <button type="submit" class="w-full bg-blue-600 text-white font-bold py-3 rounded-lg hover:bg-blue-700 transition">
                            Enregistrer le Pack
                        </button>
                        <button type="button" onclick="resetForm()" class="w-full text-gray-500 text-sm underline mt-2">Réinitialiser</button>
                    </form>
                </div>

                <!-- List -->
                <div class="lg:col-span-2 bg-white rounded-xl shadow-sm overflow-hidden">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                            <tr>
                                <th class="p-4">Client / Email</th>
                                <th class="p-4">Lien</th>
                                <th class="p-4">Statut</th>
                                <th class="p-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 italic text-sm">
                            <?php foreach ($packs as $p): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="p-4">
                                        <div class="font-bold text-gray-800"><?= htmlspecialchars($p['full_name']) ?></div>
                                        <div class="text-xs text-gray-400"><?= htmlspecialchars($p['email']) ?></div>
                                    </td>
                                    <td class="p-4">
                                        <a href="<?= htmlspecialchars($p['pack_link']) ?>" target="_blank" class="text-blue-500 underline text-xs truncate max-w-[150px] inline-block">Voir le lien</a>
                                    </td>
                                    <td class="p-4">
                                        <?php if ($p['status'] === 'verified'): ?>
                                            <span class="bg-green-100 text-green-700 px-2 py-1 rounded-full text-[10px] font-bold">VERIFIED</span>
                                        <?php else: ?>
                                            <span class="bg-yellow-100 text-yellow-700 px-2 py-1 rounded-full text-[10px] font-bold">PENDING</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-4 text-right flex justify-end gap-2">
                                        <button onclick='editPack(<?= htmlspecialchars(json_encode($p, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8') ?>)' class="text-blue-600 hover:underline">Editer</button>
                                        <form method="POST" onsubmit="return confirm('Supprimer ce pack ?')" class="inline">
                                            <?php csrf_field(); ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                            <button type="submit" class="text-red-500 hover:underline">Supprimer</button>
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

    <script>
        const recoveryPacks = <?= json_encode($pdo->query('SELECT * FROM cv_packs')->fetchAll(PDO::FETCH_ASSOC), JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
        function selectRecoveryOrder(order) {
            const pack = recoveryPacks.find(item => String(item.id) === String(order.pack_id));
            if (pack) { editPack(pack); return; }
            resetForm();
            const select = document.getElementById('orderId');
            select.value = order.id;
            updateFields(select);
            document.getElementById('fullName').focus();
            select.scrollIntoView({behavior: 'smooth', block: 'center'});
        }
        function updateFields(select) {
            const option = select.options[select.selectedIndex];
            if (option.value) {
                document.getElementById('fullName').value = option.getAttribute('data-name');
                document.getElementById('email').value = option.getAttribute('data-email');
            }
        }

        function editPack(pack) {
            document.getElementById('formAction').value = 'edit';
            document.getElementById('packId').value = pack.id;
            document.getElementById('orderId').value = pack.order_id;
            document.getElementById('fullName').value = pack.full_name;
            document.getElementById('email').value = pack.email;
            document.getElementById('packLink').value = pack.pack_link;
            document.getElementById('status').value = pack.status;
            
            // Scroll to form
            document.querySelector('.lg\\:grid-cols-3').scrollIntoView({ behavior: 'smooth' });
        }

        function resetForm() {
            document.getElementById('formAction').value = 'add';
            document.getElementById('packId').value = '';
            document.getElementById('orderId').value = '';
            document.getElementById('fullName').value = '';
            document.getElementById('email').value = '';
            document.getElementById('packLink').value = '';
            document.getElementById('status').value = 'pending';
        }
    </script>
</body>
</html>
