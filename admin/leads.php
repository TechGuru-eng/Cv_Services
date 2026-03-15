<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}
require_once 'config/db.php';

// Mark as Contacted
if (isset($_GET['mark_contacted'])) {
    $id = $_GET['mark_contacted'];
    $pdo->prepare("UPDATE abandoned_carts SET status = 'contacted' WHERE id = ?")->execute([$id]);
    header('Location: leads.php');
    exit;
}

$leads = $pdo->query("SELECT * FROM abandoned_carts WHERE status = 'new' ORDER BY updated_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<?php include 'includes/header.php'; ?>
<body class="bg-gray-50">
    <div class="flex h-screen overflow-hidden">
        <?php include 'includes/nav.php'; ?>
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
            <h1 class="text-3xl font-bold text-gray-800 mb-6">👻 Paniers Abandonnés</h1>

            <!-- List -->
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <table class="min-w-full leading-normal">
                    <thead>
                        <tr>
                            <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Date</th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Client</th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Source</th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Détails</th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($leads as $l): 
                            $details = json_decode($l['cart_details'], true) ?? [];
                            $amount = $details['amount'] ?? '?';
                            $pack = $details['pack'] ?? '?';
                            
                            // Recovery Message
                            $msg = "Bonjour {$l['name']}, je vois que vous avez essayé de commander le pack '$pack' sur MonCVPro mais le paiement n'est pas passé. Avez-vous rencontré une difficulté ?";
                            $waLink = "https://wa.me/{$l['whatsapp']}?text=" . urlencode($msg);
                        ?>
                        <tr>
                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                <p class="text-gray-900 whitespace-no-wrap"><?= date('d/m H:i', strtotime($l['updated_at'])) ?></p>
                                <p class="text-gray-400 text-xs text-xs">Il y a <?= floor((time() - strtotime($l['updated_at'])) / 60) ?> min</p>
                            </td>
                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                <div class="font-bold"><?= htmlspecialchars($l['name']) ?></div>
                                <div class="text-blue-600"><?= htmlspecialchars($l['whatsapp']) ?></div>
                                <div class="text-gray-500 text-xs"><?= htmlspecialchars($l['email']) ?></div>
                            </td>
                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded text-xs font-bold"><?= htmlspecialchars($l['source_page']) ?></span>
                            </td>
                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                <p>Pack: <b><?= $pack ?></b></p>
                                <p>Montant: <b><?= $amount ?> F</b></p>
                            </td>
                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                <a href="<?= $waLink ?>" target="_blank" onclick="fetch('?mark_contacted=<?= $l['id'] ?>')" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-3 rounded text-xs flex items-center w-max">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.52.151-.174.2-.298.3-.495.099-.198.05-.372-.025-.52-.075-.149-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                                    Relancer
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
