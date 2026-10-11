<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}
require_once 'config/db.php';

$typeFilter = ($_GET['type'] ?? '') === 'advance' ? 'advance' : '';
$statusFilter = is_string($_GET['status'] ?? null) ? $_GET['status'] : '';
if (!in_array($statusFilter, ['pending', 'verified', 'failed'], true)) $statusFilter = '';
$search = is_string($_GET['q'] ?? null) ? mb_substr(trim($_GET['q']), 0, 150) : '';
$page = max(1, min(1000000, (int)(is_scalar($_GET['page'] ?? 1) ? ($_GET['page'] ?? 1) : 1)));
$perPage = 50;
$conditions = [];
$params = [];
if ($typeFilter === 'advance') {
    $conditions[] = "order_type = 'advance'";
}
if ($search !== '') {
    // Treat %, _ and ! as literal customer input rather than LIKE wildcards.
    $pattern = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search) . '%';
    $fields = ['name', 'email', 'whatsapp', 'order_ref', 'transaction_number'];
    $searchConditions = [];
    foreach ($fields as $field) { $searchConditions[] = "$field LIKE ? ESCAPE '!'"; $params[] = $pattern; }
    $phone = preg_replace('/[^0-9]/', '', $search);
    if (strlen($phone) >= 6 && preg_match('/^[+0-9\s().-]+$/', $search)) {
        $searchConditions[] = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(whatsapp, ' ', ''), '+', ''), '-', ''), '(', ''), ')', '') LIKE ?";
        $params[] = '%' . $phone . '%';
    }
    $conditions[] = '(' . implode(' OR ', $searchConditions) . ')';
}
$where = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';
$countsStatement = $pdo->prepare('SELECT payment_status, COUNT(*) AS total FROM orders' . $where . ' GROUP BY payment_status');
$countsStatement->execute($params);
$counts = ['pending' => 0, 'verified' => 0, 'failed' => 0];
$allCount = 0;
foreach ($countsStatement->fetchAll() as $row) {
    $allCount += (int)$row['total'];
    $counts[$row['payment_status']] = (int)$row['total'];
}
$total = $statusFilter === '' ? $allCount : $counts[$statusFilter];
$pageCount = max(1, (int)ceil($total / $perPage));
$page = min($page, $pageCount);
if ($statusFilter !== '') { $conditions[] = 'payment_status = ?'; $params[] = $statusFilter; }
$where = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';
$sql = 'SELECT * FROM orders' . $where . ' ORDER BY created_at DESC, id DESC LIMIT ' . $perPage . ' OFFSET ' . (($page - 1) * $perPage);
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

$pageTitle = ($typeFilter === 'advance') ? "Réservations" : "Commandes";
$statusLabels = ['' => 'Toutes', 'pending' => 'En attente', 'verified' => 'Approuvées', 'failed' => 'Échouées'];
function ordersUrl(array $changes = []): string {
    global $typeFilter, $statusFilter, $search;
    $query = array_merge(['type' => $typeFilter, 'status' => $statusFilter, 'q' => $search], $changes);
    $query = array_filter($query, static fn($value) => $value !== '' && $value !== null);
    return 'orders.php' . ($query ? '?' . http_build_query($query) : '');
}
?>
<?php include 'includes/header.php'; ?>
<body class="bg-gray-50">
    <title><?= $pageTitle ?> - HireMe Admin</title>
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <?php include 'includes/nav.php'; ?>

        <!-- Main Content -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-gray-800"><?= $pageTitle ?></h1>
                <a href="create-order.php" class="bg-blue-600 text-white px-4 py-2 rounded shadow hover:bg-blue-700 transition flex items-center">
                    <span class="mr-2 text-xl">+</span> Nouvelle Commande
                </a>
            </div>

            <!-- Category Filter Tabs -->
            <div class="flex flex-wrap items-center gap-2 mb-6">
                <a href="<?= htmlspecialchars(ordersUrl(['type' => ''])) ?>" class="px-4 py-2 rounded-xl text-xs font-bold transition shadow-sm <?= empty($typeFilter) ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100' ?>">
                    📋 Commandes CV
                </a>
                <a href="<?= htmlspecialchars(ordersUrl(['type' => 'advance'])) ?>" class="px-4 py-2 rounded-xl text-xs font-bold transition shadow-sm <?= ($typeFilter === 'advance') ? 'bg-purple-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100' ?>">
                    📅 Réservations
                </a>
                <a href="linkedin/commandes.php" class="px-4 py-2 rounded-xl text-xs font-bold transition shadow-sm bg-white text-sky-700 hover:bg-sky-50 border border-sky-200 flex items-center gap-1.5">
                    💼 Commandes LinkedIn &rarr;
                </a>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-4 mb-6">
                <form method="get" action="orders.php" class="flex flex-wrap items-end gap-3">
                    <?php if ($typeFilter !== ''): ?><input type="hidden" name="type" value="advance"><?php endif; ?>
                    <?php if ($statusFilter !== ''): ?><input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>"><?php endif; ?>
                    <div class="flex-1 min-w-0" style="min-width: min(100%, 240px)">
                        <label for="order-search" class="block text-sm font-semibold text-gray-700 mb-2">Rechercher une commande</label>
                        <input id="order-search" name="q" type="search" maxlength="150" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" placeholder="Nom, email, téléphone ou référence" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <button type="submit" class="bg-blue-600 text-white rounded-lg px-4 py-2 hover:bg-blue-700">Rechercher</button>
                    <?php if ($search !== '' || $statusFilter !== ''): ?><a href="<?= htmlspecialchars(ordersUrl(['q' => '', 'status' => ''])) ?>" class="text-gray-600 px-2 py-2 underline">Réinitialiser</a><?php endif; ?>
                </form>
                <nav aria-label="Filtrer par statut de paiement" class="flex flex-wrap gap-2 mt-4">
                    <?php foreach ($statusLabels as $code => $label): ?>
                        <a href="<?= htmlspecialchars(ordersUrl(['status' => $code])) ?>" <?= $statusFilter === $code ? 'aria-current="page"' : '' ?> class="px-4 py-2 rounded-lg text-sm font-semibold <?= $statusFilter === $code ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                            <?= $label ?> <span class="ml-1">(<?= $code === '' ? $allCount : $counts[$code] ?>)</span>
                        </a>
                    <?php endforeach; ?>
                </nav>
                <p class="text-sm text-gray-500 mt-3">« Approuvées » signifie que le paiement a été validé. Une commande en attente est enregistrée, mais son paiement reste à confirmer.</p>
                <p class="text-sm text-gray-700 mt-2" role="status"><?= $total ?> commande(s) trouvée(s)<?= $search !== '' ? ' pour « ' . htmlspecialchars($search, ENT_QUOTES, 'UTF-8') . ' »' : '' ?>.</p>
            </div>

            <div class="bg-white shadow-md rounded-lg overflow-hidden overflow-x-auto">
                <table class="min-w-full leading-normal">
                    <thead>
                        <tr>
                            <th
                                class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Ref/ID</th>
                            <th
                                class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Client</th>
                            <th
                                class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Pack</th>
                            <th
                                class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Montant</th>
                            <th
                                class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Méthode</th>
                            <th
                                class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Statut</th>
                            <th
                                class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Date</th>
                            <th
                                class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$orders): ?>
                            <tr><td colspan="8" class="px-5 py-10 text-center text-gray-500">Aucune commande ne correspond à ces filtres. Essayez un autre nom, numéro ou statut.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                    <div class="font-bold"><?= htmlspecialchars($order['order_ref'] ?? $order['id']) ?></div>
                                    <?php if (($order['order_type'] ?? 'standard') === 'advance'): ?>
                                        <span class="bg-purple-100 text-purple-700 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider">Réservation</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                    <div class="font-bold"><?= htmlspecialchars($order['name']) ?></div>
                                    <div class="text-gray-500 text-xs"><?= htmlspecialchars($order['email']) ?></div>
                                    <div class="text-gray-500 text-xs mt-1"><?= htmlspecialchars($order['whatsapp'] ?? '') ?></div>
                                </td>
                                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                    <span class="bg-blue-50 text-blue-700 px-2 py-1 rounded-full text-xs">
                                        <?= htmlspecialchars($order['package_name'] ?? 'Standard') ?>
                                    </span>
                                    <?php if (!empty($order['has_recruiter_list'])): ?>
                                        <span class="bg-indigo-100 text-indigo-700 px-1 py-0.5 rounded text-[10px] font-bold ml-1" title="Bible Recruteur Incluse">+Bible</span>
                                    <?php endif; ?>
                                    <?php if (!empty($order['due_date'])): ?>
                                        <div class="mt-1 text-xs font-bold text-red-500 flex items-center">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 00-2 2z"></path></svg>
                                            Pour le: <?= date('d/m/Y', strtotime($order['due_date'])) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                    <div class="font-semibold"><?= number_format($order['amount'], 0, ',', ' ') ?> FCFA</div>
                                    <?php if ($order['discount_amount'] > 0): ?>
                                        <div class="text-[10px] text-gray-400 line-through"><?= number_format($order['original_amount'], 0, ',', ' ') ?> FCFA</div>
                                        <div class="text-[10px] text-green-600 font-bold">-<?= number_format($order['discount_amount'], 0, ',', ' ') ?> FCFA</div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                    <div class="text-gray-900"><?= htmlspecialchars($order['payment_method'] ?: 'À confirmer') ?></div>
                                    <?php if (($order['order_type'] ?? 'standard') !== 'advance'): ?>
                                        <div class="text-gray-400 text-[10px] font-mono break-all"><?= htmlspecialchars($order['transaction_number'] ?? '') ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                    <span class="relative inline-block px-3 py-1 font-semibold leading-tight 
                                    <?php
                                    if ($order['payment_status'] == 'verified')
                                        echo 'text-green-900';
                                    elseif ($order['payment_status'] == 'failed')
                                        echo 'text-red-900';
                                    else
                                        echo 'text-yellow-900';
                                    ?>">
                                        <span aria-hidden class="absolute inset-0 opacity-50 rounded-full 
                                        <?php
                                        if ($order['payment_status'] == 'verified')
                                            echo 'bg-green-200';
                                        elseif ($order['payment_status'] == 'failed')
                                            echo 'bg-red-200';
                                        else
                                            echo 'bg-yellow-200';
                                        ?>"></span>
                                        <span class="relative"><?= htmlspecialchars($statusLabels[$order['payment_status']] ?? $order['payment_status']) ?></span>
                                    </span>
                                </td>
                                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm text-gray-500">
                                    <?= date('d/m H:i', strtotime($order['created_at'])) ?>
                                </td>
                                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                    <a href="order-details.php?id=<?= $order['id'] ?>" class="text-blue-600 hover:text-blue-900 font-bold inline-flex items-center">
                                        Gérer
                                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                    </a>

                                    <?php 
                                    // WhatsApp Delivery Button for Pack Lettres
                                    if (stripos($order['package_name'], 'Lettres') !== false): 
                                        $phone = preg_replace('/[^0-9]/', '', $order['whatsapp']);
                                        if (strlen($phone) == 9) $phone = '237' . $phone;
                                        
                                        $msg = "Bonjour " . $order['name'] . ", merci pour votre achat du Pack 300 Lettres de Motivation 📄.\n\n";
                                        $msg .= "Voici votre lien de téléchargement sécurisé : https://drive.google.com/drive/folders/1R103KEkAWeE4j7IX3iYMGc_fjusk14dl?usp=drive_link\n\n";
                                        $msg .= "🎁 BONUS : Vous avez les droits de revente ! Vous pouvez utiliser ces modèles pour vous ou les revendre à votre propre prix.\n\n";
                                        $msg .= "Bon succès ! L'équipe HireMe.";
                                        
                                        $waLink = "https://wa.me/" . $phone . "?text=" . urlencode($msg);
                                    ?>
                                        <a href="receipt.php?id=<?= $order['id'] ?>" target="_blank" class="ml-3 text-slate-400 hover:text-blue-600 transition-colors" title="Imprimer le Reçu">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                        </a>

                                        <a href="<?= $waLink ?>" target="_blank" class="ml-3 bg-green-500 text-white px-3 py-1 rounded-full text-xs font-bold hover:bg-green-600 inline-flex items-center shadow-sm transition-colors" title="Envoyer le pack sur WhatsApp">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                                            Livrer
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($pageCount > 1): ?>
                <nav aria-label="Pagination des commandes" class="flex flex-wrap items-center justify-between gap-3 mt-4">
                    <p class="text-sm text-gray-600">Page <?= $page ?> sur <?= $pageCount ?> · <?= $perPage ?> commandes maximum par page</p>
                    <div class="flex gap-3">
                        <?php if ($page > 1): ?><a class="bg-white px-4 py-2 rounded-lg shadow-sm text-blue-600" href="<?= htmlspecialchars(ordersUrl(['page' => $page - 1])) ?>">Précédente</a><?php endif; ?>
                        <?php if ($page < $pageCount): ?><a class="bg-white px-4 py-2 rounded-lg shadow-sm text-blue-600" href="<?= htmlspecialchars(ordersUrl(['page' => $page + 1])) ?>">Suivante</a><?php endif; ?>
                    </div>
                </nav>
            <?php endif; ?>
        </main>
    </div>
</body>

</html>
