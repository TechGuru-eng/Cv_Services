<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}
require_once 'config/db.php';

// Fetch all subscribers
$stmt = $pdo->query("SELECT * FROM blog_subscribers ORDER BY created_at DESC");
$subscribers = $stmt->fetchAll();

$pageTitle = "Abonnés Blog";
include 'includes/header.php';
?>

<body class="bg-gray-50 font-sans text-gray-800">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <?php include 'includes/nav.php'; ?>

        <div class="flex-1 flex flex-col min-h-screen overflow-hidden relative">
            
            <header class="bg-white/80 backdrop-blur-md sticky top-0 z-30 border-b border-gray-100 p-4 flex justify-between items-center">
                <div class="md:hidden flex items-center gap-3">
                    <button id="mobileMenuBtn" class="p-2 text-gray-600 bg-gray-100 rounded-xl hover:bg-gray-200 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    </button>
                    <span class="text-xl font-black text-primary">MonCV</span>
                </div>
                <div class="hidden md:flex items-center gap-2">
                    <span class="text-xl font-black text-gray-900 tracking-tight">Abonnés Blog 📧</span>
                </div>
                <a href="logout.php" class="bg-red-50 text-red-500 px-4 py-2 rounded-xl text-sm font-bold hover:bg-red-100 transition-all">Sortir</a>
            </header>

            <main class="p-6 flex-1 overflow-x-hidden overflow-y-auto">
                
                <div class="flex justify-between items-center mb-8">
                    <div>
                        <h1 class="text-2xl font-black text-dark tracking-tight">Leads Capturés</h1>
                        <p class="text-gray-500 text-sm mt-1">Personnes ayant débloqué un article du blog.</p>
                    </div>
                    <div class="flex flex-col sm:flex-row gap-3">
                        <a href="export_blog_leads.php" class="bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 px-5 py-2.5 rounded-xl font-bold text-sm shadow-sm transition flex items-center gap-2 justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            Exporter Leads (CSV)
                        </a>
                        <a href="export_newsletter.php" class="bg-primary text-white px-5 py-2.5 rounded-xl font-bold text-sm shadow-lg shadow-sky-200 hover:bg-sky-600 transition flex items-center gap-2 justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            Exporter Newsletter (CSV)
                        </a>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-100">
                                    <th class="p-4 text-xs font-black text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="p-4 text-xs font-black text-gray-500 uppercase tracking-wider">Nom</th>
                                    <th class="p-4 text-xs font-black text-gray-500 uppercase tracking-wider">Email</th>
                                    <th class="p-4 text-xs font-black text-gray-500 uppercase tracking-wider">Téléphone</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <?php if(empty($subscribers)): ?>
                                    <tr>
                                        <td colspan="4" class="p-8 text-center text-gray-400 font-medium">Aucun abonné pour le moment.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach($subscribers as $sub): ?>
                                    <tr class="hover:bg-gray-50/50 transition-colors group">
                                        <td class="p-4 whitespace-nowrap text-sm text-gray-500 font-medium">
                                            <?= date('d/m/Y H:i', strtotime($sub['created_at'])) ?>
                                        </td>
                                        <td class="p-4">
                                            <div class="font-bold text-gray-900"><?= htmlspecialchars($sub['name']) ?></div>
                                        </td>
                                        <td class="p-4">
                                            <a href="mailto:<?= htmlspecialchars($sub['email']) ?>" class="text-primary hover:underline font-medium"><?= htmlspecialchars($sub['email']) ?></a>
                                        </td>
                                        <td class="p-4">
                                            <a href="tel:<?= htmlspecialchars($sub['phone']) ?>" class="text-gray-600 hover:text-dark font-medium"><?= htmlspecialchars($sub['phone']) ?></a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
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
