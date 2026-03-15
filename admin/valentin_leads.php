<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}
require_once 'config/db.php';

// --- Actions ---
if (isset($_GET['action']) && $_GET['action'] === 'archive' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("UPDATE abandoned_carts SET status = 'contacted' WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    header('Location: valentin_leads.php');
    exit;
}

// --- Data Fetching ---
// Fetch only Valentine leads that are 'new'
$leads = $pdo->query("SELECT * FROM abandoned_carts WHERE source_page = 'Valentin' AND status = 'new' ORDER BY updated_at DESC")->fetchAll();
$totalLeads = count($leads);

?>
<?php include 'includes/header.php'; ?>
<body class="bg-gray-50 font-sans text-gray-800">

    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <?php include 'includes/nav.php'; ?>

        <div class="flex-1 flex flex-col min-h-screen overflow-hidden">
            <header class="bg-white border-b border-gray-100 p-4 md:hidden flex justify-between items-center sticky top-0 z-30">
                <h1 class="text-xl font-black text-rose-600">Valentin <span class="text-slate-800">Leads</span></h1>
                <div class="bg-rose-50 text-rose-600 px-3 py-1 rounded-full text-[10px] font-black uppercase"><?= $totalLeads ?> Leads</div>
            </header>

            <main class="p-4 md:p-10 flex-1 overflow-x-hidden overflow-y-auto pb-32 md:pb-10">
                <div class="flex justify-between items-center mb-8">
                    <div>
                        <h2 class="text-3xl font-black text-slate-900">Leads Saint-Valentin ❤️</h2>
                        <p class="text-gray-500">Paniers abandonnés sur la page Valentin. Relancez-les vite !</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <a href="../valentin-flyer.php" target="_blank" class="bg-rose-50 text-rose-600 px-4 py-2 rounded-xl text-xs font-bold hover:bg-rose-100 transition-colors flex items-center gap-2">
                            <span>Voir le Flyer</span> 📸
                        </a>
                    </div>
                </div>

                <?php if(empty($leads)): ?>
                    <div class="bg-white p-12 rounded-3xl text-center border border-dashed border-gray-200">
                        <div class="text-4xl mb-4">🤷‍♂️</div>
                        <h3 class="text-lg font-bold text-gray-400">Aucun lead en attente</h3>
                        <p class="text-xs text-gray-300">Tout est calme pour le moment.</p>
                    </div>
                <?php else: ?>

                    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach($leads as $lead): 
                            $details = json_decode($lead['cart_details'] ?? '{}', true);
                            $price = $details['price'] ?? 0;
                            $pack = $details['pack'] ?? 'Inconnu';
                            
                            $firstName = explode(' ', trim($lead['name']))[0];
                            
                            // WhatsApp Message
                            $msg = "Bonjour $firstName ! ❤️ C'est l'équipe MonCv Pro. J'ai vu que vous étiez intéressé par notre *Offre Valentin ($pack)* mais que vous n'avez pas finalisé. \n\nUn souci au moment du paiement ? \n\nCliquez ici pour profiter de l'offre avant qu'elle n'expire : https://moncvpro.hiremeguide.com/valentin.php";
                            $waLink = "https://wa.me/" . preg_replace('/[^0-9]/', '', $lead['whatsapp']) . "?text=" . urlencode($msg);
                        ?>
                        <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow relative group">
                            
                            <div class="absolute top-4 right-4">
                                <span class="bg-rose-50 text-rose-600 text-[10px] font-black px-2 py-1 rounded-lg uppercase tracking-wider"><?= htmlspecialchars($pack) ?></span>
                            </div>

                            <div class="mb-4">
                                <h3 class="font-bold text-lg text-slate-800 mb-1"><?= htmlspecialchars($lead['name'] ?: 'Anonyme') ?></h3>
                                <a href="tel:<?= htmlspecialchars($lead['whatsapp']) ?>" class="block text-xl font-black text-slate-600 hover:text-rose-600 transition-colors tracking-wider mb-1" title="Appeler">
                                    <?= htmlspecialchars($lead['whatsapp']) ?> 📞
                                </a>
                                <div class="text-[10px] font-bold text-gray-300 uppercase">
                                    Vu à <?= date('H:i', strtotime($lead['updated_at'])) ?>
                                </div>
                            </div>

                            <div class="flex justify-between items-center mb-6">
                                <span class="text-2xl font-black text-slate-900"><?= number_format($price, 0, ',', ' ') ?> <small class="text-xs font-bold text-gray-400">XAF</small></span>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <a href="?action=archive&id=<?= $lead['id'] ?>" class="flex items-center justify-center py-3 rounded-xl border border-gray-100 text-gray-400 text-xs font-bold hover:bg-gray-50 hover:text-red-500 transition-colors">
                                    Ignorer
                                </a>
                                <a href="<?= $waLink ?>" target="_blank" class="flex items-center justify-center py-3 rounded-xl bg-[#25D366] text-white text-xs font-bold shadow-lg shadow-green-100 hover:bg-[#20ba56] transition-colors gap-2">
                                    <span>WhatsApp</span> 🚀
                                </a>
                            </div>

                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            </main>
        </div>
    </div>

</body>
</html>
