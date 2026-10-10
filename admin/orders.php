<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}
require_once 'config/db.php';

$typeFilter = $_GET['type'] ?? '';
$sql = "SELECT * FROM orders";
$params = [];

if ($typeFilter === 'advance') {
    $sql .= " WHERE order_type = 'advance'";
}

$sql .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

$pageTitle = ($typeFilter === 'advance') ? "Réservations" : "Commandes";
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
                <a href="orders.php" class="px-4 py-2 rounded-xl text-xs font-bold transition shadow-sm <?= empty($typeFilter) ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100' ?>">
                    📋 Commandes CV
                </a>
                <a href="orders.php?type=advance" class="px-4 py-2 rounded-xl text-xs font-bold transition shadow-sm <?= ($typeFilter === 'advance') ? 'bg-purple-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100' ?>">
                    📅 Réservations
                </a>
                <a href="linkedin/commandes.php" class="px-4 py-2 rounded-xl text-xs font-bold transition shadow-sm bg-white text-sky-700 hover:bg-sky-50 border border-sky-200 flex items-center gap-1.5">
                    💼 Commandes LinkedIn &rarr;
                </a>
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
                                        <span class="relative"><?= htmlspecialchars($order['payment_status']) ?></span>
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
        </main>
    </div>
</body>

</html>