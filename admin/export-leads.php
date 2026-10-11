<?php
/**
 * Admin Panel: Export CV Builder Leads
 */
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

require_once 'config/db.php';
require_once __DIR__ . '/includes/admin-page-schema.php';
ensureAdminPageTable($pdo, 'cv_builder_leads');

// 1. Process CSV Export request
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=leads_cv_builder_' . date('Y-m-d') . '.csv');
    
    // Add UTF-8 BOM for MS Excel compatibility
    echo "\xEF\xBB\xBF";
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Nom', 'WhatsApp', 'Email', 'Poste Cible', 'Créé le', 'Statut Conversion']);
    
    $stmt = $pdo->query("
        SELECT l.id, l.name, l.whatsapp, l.email, l.job_title, l.created_at,
               (SELECT COUNT(*) FROM orders o WHERE l.whatsapp <> '' AND CONVERT(o.whatsapp USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(l.whatsapp USING utf8mb4) COLLATE utf8mb4_unicode_ci AND o.payment_status = 'verified') as has_order
        FROM cv_builder_leads l 
        ORDER BY l.created_at DESC
    ");
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $status = ($row['has_order'] > 0) ? 'Payé (Premium)' : 'Gratuit (Simple)';
        fputcsv($output, [
            $row['id'],
            $row['name'],
            $row['whatsapp'],
            $row['email'],
            $row['job_title'],
            $row['created_at'],
            $status
        ]);
    }
    
    fclose($output);
    exit;
}

// 2. Fetch leads for standard web view
$leads = $pdo->query("
    SELECT l.*, 
           (SELECT COUNT(*) FROM orders o WHERE l.whatsapp <> '' AND CONVERT(o.whatsapp USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(l.whatsapp USING utf8mb4) COLLATE utf8mb4_unicode_ci AND o.payment_status = 'verified') as has_order
    FROM cv_builder_leads l 
    ORDER BY l.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

?>
<?php include 'includes/header.php'; ?>
<body class="bg-gray-100 font-sans text-gray-800">
    <div class="flex h-screen overflow-hidden">
        
        <!-- Sidebar Navigation -->
        <?php include 'includes/nav.php'; ?>

        <div class="flex-1 flex flex-col min-h-screen overflow-hidden">
            
            <!-- Topbar Header -->
            <header class="bg-white/80 backdrop-blur-md sticky top-0 z-30 border-b border-gray-100 p-4 flex justify-between items-center">
                <h1 class="text-xl font-black text-gray-800">📝 Leads CV Builder</h1>
                <div class="flex items-center gap-3">
                    <a href="?export=csv" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2.5 rounded-xl text-xs font-bold transition-all shadow-md flex items-center gap-1.5">
                        <i class="fa-solid fa-file-excel"></i> Exporter en CSV
                    </a>
                </div>
            </header>

            <!-- Main Content Grid -->
            <main class="p-6 md:p-10 flex-1 overflow-x-hidden overflow-y-auto">
                <div class="mb-8 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <h2 class="text-2xl font-black text-gray-900 tracking-tight">Base de données prospects</h2>
                        <p class="text-gray-500 text-xs mt-1">Candidats ayant complété le formulaire du CV gratuit en ligne.</p>
                    </div>
                    <div class="bg-white px-4 py-2 rounded-xl shadow-sm border border-gray-100">
                        <span class="text-xs text-gray-400 font-bold uppercase">Total Leads :</span>
                        <span class="text-sm font-black text-blue-600 ml-1"><?= count($leads) ?></span>
                    </div>
                </div>

                <!-- Leads Datatable -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-100 text-gray-400 uppercase text-[10px] font-black tracking-wider">
                                    <th class="p-4">Date de Capture</th>
                                    <th class="p-4">Identité du Candidat</th>
                                    <th class="p-4">Poste Cible</th>
                                    <th class="p-4">Conversion</th>
                                    <th class="p-4 text-right">Actions Marketing</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php foreach ($leads as $l): 
                                    $isConverted = ($l['has_order'] > 0);
                                    $whatsappFormatted = preg_replace('/[^0-9]/', '', $l['whatsapp']);
                                    // Custom follow-up pitch via WhatsApp
                                    $pitchText = "Bonjour " . explode(' ', trim($l['name']))[0] . " ! J'espère que vous allez bien. Notre équipe RH a bien reçu votre demande de rédaction de CV Premium. Avez-vous besoin d'aide pour finaliser ?";
                                    $waLink = "https://wa.me/" . $whatsappFormatted . "?text=" . urlencode($pitchText);
                                ?>
                                    <tr class="hover:bg-gray-50/50 transition">
                                        <td class="p-4">
                                            <div class="text-sm font-bold text-gray-800"><?= date('d/m/Y', strtotime($l['created_at'])) ?></div>
                                            <div class="text-[10px] text-gray-400"><?= date('H:i', strtotime($l['created_at'])) ?></div>
                                        </td>
                                        <td class="p-4">
                                            <div class="font-extrabold text-sm text-gray-900"><?= htmlspecialchars($l['name']) ?></div>
                                            <div class="flex flex-col sm:flex-row sm:gap-3 text-xs text-gray-500 mt-0.5">
                                                <span><i class="fa-brands fa-whatsapp text-green-500 mr-1"></i><?= htmlspecialchars($l['whatsapp']) ?></span>
                                                <span class="hidden sm:inline text-gray-300">|</span>
                                                <span><i class="fa-regular fa-envelope text-blue-400 mr-1"></i><?= htmlspecialchars($l['email']) ?></span>
                                            </div>
                                        </td>
                                        <td class="p-4 text-xs font-semibold text-gray-600">
                                            <?= htmlspecialchars($l['job_title'] ?: 'Non spécifié') ?>
                                        </td>
                                        <td class="p-4">
                                            <?php if ($isConverted): ?>
                                                <span class="inline-flex items-center gap-1 bg-green-50 text-green-700 px-2.5 py-1 rounded-full text-[10px] font-black uppercase">
                                                    <i class="fa-solid fa-circle-check"></i> Payé Premium
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center gap-1 bg-yellow-50 text-yellow-700 px-2.5 py-1 rounded-full text-[10px] font-black uppercase">
                                                    <i class="fa-solid fa-hourglass-half"></i> Gratuit Simple
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="p-4 text-right">
                                            <div class="flex justify-end gap-2">
                                                <a href="<?= $waLink ?>" target="_blank" class="bg-green-50 text-green-600 border border-green-200/50 hover:bg-green-600 hover:text-white px-3 py-1.5 rounded-lg text-xs font-bold transition-all inline-flex items-center gap-1.5">
                                                    <i class="fa-brands fa-whatsapp"></i> Relancer WhatsApp
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($leads)): ?>
                                    <tr>
                                        <td colspan="5" class="p-12 text-center text-gray-400 italic">
                                            Aucun prospect capturé par le CV Builder pour le moment.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>

        </div>
    </div>
</body>
</html>
