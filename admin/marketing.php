<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}
require_once 'config/db.php';
require_once 'includes/activity_helper.php';

// --- Export Logic ---
if (isset($_GET['export'])) {
    $exportType = $_GET['export'];
    $filename = "";
    $data = [];
    $headers = [];

    if ($exportType === 'prospects') {
        $filename = "prospects_emails_" . date('Y-m-d') . ".csv";
        $headers = ['Nom', 'WhatsApp', 'Email', 'Ville', 'Pack', 'Date'];
        $stmt = $pdo->query("SELECT name, whatsapp, email, city, package_name, created_at FROM prospects ORDER BY created_at DESC");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        log_activity("Export Marketing", "Exportation de la liste des prospects.");
    } elseif ($exportType === 'customers') {
        $filename = "customers_emails_" . date('Y-m-d') . ".csv";
        $headers = ['Nom', 'WhatsApp', 'Email', 'Pack', 'Montant', 'Date'];
        $stmt = $pdo->query("SELECT name, whatsapp, email, package_name, amount, created_at FROM orders WHERE payment_status = 'verified' ORDER BY created_at DESC");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        log_activity("Export Marketing", "Exportation de la liste des clients vérifiés.");
    } elseif ($exportType === 'audits') {
        $filename = "audits_cv_" . date('Y-m-d') . ".csv";
        $headers = ['Nom', 'Email', 'Poste Visé (Notes)', 'Score', 'Date'];
        // Note: The score is often in 'notes', let's just dump notes for now as 'Poste Visé'
        $stmt = $pdo->query("SELECT name, email, notes, created_at FROM prospects WHERE source = 'cv_audit' ORDER BY created_at DESC");
        // We might want to parse 'notes' here if it's structured, but raw is fine for now
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        log_activity("Export Marketing", "Exportation des audits CV.");
    }

    if (!empty($filename)) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        $output = fopen('php://output', 'w');
        
        // Add UTF-8 BOM for Excel compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        fputcsv($output, $headers);
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        fclose($output);
        exit;
    }
}

// Stats for the page
$prospectCount = $pdo->query("SELECT COUNT(*) FROM prospects WHERE email IS NOT NULL AND email != ''")->fetchColumn();
$customerCount = $pdo->query("SELECT COUNT(*) FROM orders WHERE payment_status = 'verified' AND email IS NOT NULL AND email != ''")->fetchColumn();
$auditCount = $pdo->query("SELECT COUNT(*) FROM prospects WHERE source = 'cv_audit'")->fetchColumn();

include 'includes/header.php';
?>
<body class="bg-gray-50">
    <title>Marketing & Exports - HireMe Admin</title>
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <?php include 'includes/nav.php'; ?>

        <!-- Main Content -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6 md:p-10">
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-3xl font-extrabold text-gray-900">Marketing & Audience 🚀</h1>
                    <p class="text-gray-500 mt-1">Exportez vos listes de contacts pour vos campagnes d'emailing et marketing.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Prospects Card -->
                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden group hover:shadow-xl transition-all duration-300">
                    <div class="p-8">
                        <div class="w-14 h-14 bg-orange-100 text-orange-600 rounded-2xl flex items-center justify-center text-2xl mb-6 group-hover:scale-110 transition-transform">
                            🛒
                        </div>
                        <h2 class="text-2xl font-bold text-gray-800 mb-2">Base Prospects</h2>
                        <p class="text-gray-500 mb-6 leading-relaxed">Contient tous les utilisateurs ayant entamé un processus d'achat mais n'ayant pas forcément finalisé (paniers abandonnés included).</p>
                        
                        <div class="flex items-end justify-between mb-8">
                            <div>
                                <span class="text-4xl font-black text-orange-600"><?= $prospectCount ?></span>
                                <span class="text-gray-400 font-bold ml-1 uppercase text-xs">Emails dispos</span>
                            </div>
                        </div>

                        <a href="?export=prospects" class="block w-full bg-orange-600 hover:bg-orange-700 text-white text-center py-4 rounded-2xl font-bold shadow-lg shadow-orange-200 transition-all active:scale-95">
                            Télécharger la liste (CSV)
                        </a>
                    </div>
                </div>

                <!-- Customers Card -->
                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden group hover:shadow-xl transition-all duration-300">
                    <div class="p-8">
                        <div class="w-14 h-14 bg-blue-100 text-blue-600 rounded-2xl flex items-center justify-center text-2xl mb-6 group-hover:scale-110 transition-transform">
                            💎
                        </div>
                        <h2 class="text-2xl font-bold text-gray-800 mb-2">Base Clients Fidèles</h2>
                        <p class="text-gray-500 mb-6 leading-relaxed">Contient uniquement les clients ayant effectué un paiement vérifié. Idéal pour les offres de fidélisation ou upsells.</p>
                        
                        <div class="flex items-end justify-between mb-8">
                            <div>
                                <span class="text-4xl font-black text-blue-600"><?= $customerCount ?></span>
                                <span class="text-gray-400 font-bold ml-1 uppercase text-xs">Emails dispos</span>
                            </div>
                        </div>

                        <a href="?export=customers" class="block w-full bg-blue-600 hover:bg-blue-700 text-white text-center py-4 rounded-2xl font-bold shadow-lg shadow-blue-200 transition-all active:scale-95">
                            Télécharger la liste (CSV)
                        </a>
                    </div>
                </div>

                <!-- Audit Stats Card [NEW] -->
                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden group hover:shadow-xl transition-all duration-300">
                    <div class="p-8">
                        <div class="w-14 h-14 bg-purple-100 text-purple-600 rounded-2xl flex items-center justify-center text-2xl mb-6 group-hover:scale-110 transition-transform">
                            🔍
                        </div>
                        <h2 class="text-2xl font-bold text-gray-800 mb-2">Audits CV (Lead Magnet)</h2>
                        <p class="text-gray-500 mb-6 leading-relaxed">Nombre d'utilisateurs ayant scanné leur CV via le nouvel outil interactif. Ces leads sont automatiquement ajoutés à la base prospects.</p>
                        
                        <div class="flex items-end justify-between mb-8">
                            <div>
                                <span class="text-4xl font-black text-purple-600"><?= $auditCount ?></span>
                                <span class="text-gray-400 font-bold ml-1 uppercase text-xs">Audits réalisés</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 text-purple-700 font-bold text-sm">
                            <span class="flex h-2 w-2 rounded-full bg-purple-500 animate-ping"></span>
                            Nouveaux Leads capturés
                        </div>

                        <a href="?export=audits" class="block w-full bg-purple-600 hover:bg-purple-700 text-white text-center py-4 rounded-2xl font-bold shadow-lg shadow-purple-200 transition-all active:scale-95 mt-6">
                            Télécharger les Audits (CSV)
                        </a>
                    </div>
                </div>
            </div>

            <!-- Notice -->
            <div class="mt-12 bg-indigo-50 border border-indigo-100 rounded-3xl p-8 flex items-start gap-6">
                <div class="text-3xl">💡</div>
                <div>
                    <h3 class="font-bold text-indigo-900 mb-1">Conseil Marketing</h3>
                    <p class="text-indigo-700 text-sm leading-relaxed">Utilisez ces fichiers CSV pour les importer dans des outils comme Mailchimp, Sendinblue ou Brevo. Nous avons inclus le formatage UTF-8 avec BOM pour assurer une ouverture parfaite dans Excel.</p>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
