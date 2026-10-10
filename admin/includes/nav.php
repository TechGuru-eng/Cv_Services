<?php
$currentPage = basename($_SERVER['PHP_SELF']);
// Determine if we need to go up a directory (simple heuristic for standard admin structure)
// We will assume standard behavior (included in admin/*.php), so prefix is empty.
// If you ever move admin pages to subfolders, set $navBaseUrl before including this file.
$navBaseUrl = isset($navBaseUrl) ? $navBaseUrl : '';
?>
<!-- Desktop Sidebar -->
<aside
    class="w-64 bg-gray-900 text-white min-h-screen hidden md:flex flex-col flex-shrink-0 transition-all duration-300">
    <div class="p-6">
        <h1 class="text-2xl font-bold text-blue-400 tracking-wider">HireMe <span
                class="text-gray-400 text-xs font-normal">Admin</span></h1>
    </div>
    <nav class="mt-6 flex-1 px-2 space-y-2">
        <a href="<?= $navBaseUrl ?>index"
            class="group flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200 <?= $currentPage == 'index.php' ? 'bg-blue-600 text-white shadow-lg shadow-blue-900/50' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?>">
            <span class="mr-3 text-xl">🏠</span> Dashboard
        </a>
        <a href="<?= $navBaseUrl ?>live.php"
            class="group flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200 <?= $currentPage == 'live.php' ? 'bg-red-600 text-white shadow-lg shadow-red-900/50' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?>">
            <span class="mr-3 text-xl">📡</span> Live Pulse
        </a>
        <a href="<?= $navBaseUrl ?>orders"
            class="group flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200 <?= ($currentPage == 'orders.php' || $currentPage == 'order-details.php') ? 'bg-blue-600 text-white shadow-lg shadow-blue-900/50' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?>">
            <span class="mr-3 text-xl">📋</span> Commandes (CV)
        </a>
        <a href="<?= $navBaseUrl ?>linkedin/commandes.php"
            class="group flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200 <?= (strpos($_SERVER['PHP_SELF'], 'linkedin') !== false) ? 'bg-sky-600 text-white shadow-lg shadow-sky-900/50' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?>">
            <span class="mr-3 text-xl">💼</span> Commandes LinkedIn
        </a>
        <a href="<?= $navBaseUrl ?>orders?type=advance"
            class="group flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200 <?= (isset($_GET['type']) && $_GET['type'] == 'advance') ? 'bg-purple-600 text-white shadow-lg shadow-purple-900/50' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?>">
            <span class="mr-3 text-xl">📅</span> Réservations
        </a>
        <a href="<?= $navBaseUrl ?>prospects"
            class="group flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200 <?= $currentPage == 'prospects.php' ? 'bg-orange-600 text-white shadow-lg shadow-orange-900/50' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?>">
            <span class="mr-3 text-xl">🛒</span> Relances (Liste)
        </a>
        <a href="<?= $navBaseUrl ?>kanban"
            class="group flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200 <?= $currentPage == 'kanban.php' ? 'bg-orange-600 text-white shadow-lg shadow-orange-900/50' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?>">
            <span class="mr-3 text-xl">📋</span> CRM Kanban
        </a>
        <a href="<?= $navBaseUrl ?>clients"
            class="group flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200 <?= $currentPage == 'clients.php' ? 'bg-blue-600 text-white shadow-lg shadow-blue-900/50' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?>">
            <span class="mr-3 text-xl">👥</span> Clients (CV)
        </a>
        <a href="<?= $navBaseUrl ?>redesign_requests.php"
            class="group flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200 <?= $currentPage == 'redesign_requests.php' ? 'bg-blue-600 text-white shadow-lg shadow-blue-900/50' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?>">
            <span class="mr-3 text-xl">🔄</span> Demandes Refonte
        </a>
        <a href="<?= $navBaseUrl ?>cv_packs.php"
            class="group flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200 <?= $currentPage == 'cv_packs.php' ? 'bg-blue-600 text-white shadow-lg shadow-blue-900/50' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?>">
            <span class="mr-3 text-xl">📦</span> Packs CV
        </a>
        <a href="<?= $navBaseUrl ?>foreign_customers"
            class="group flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200 <?= $currentPage == 'foreign_customers.php' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-900/50' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?>">
            <span class="mr-3 text-xl">🌍</span> Clients Étrangers
        </a>

        <a href="<?= $navBaseUrl ?>cv-models"
            class="group flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200 <?= $currentPage == 'cv-models.php' ? 'bg-blue-600 text-white shadow-lg shadow-blue-900/50' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?>">
            <span class="mr-3 text-xl">🎨</span> Modèles CV
        </a>
        <a href="<?= $navBaseUrl ?>before-after.php"
            class="group flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200 <?= $currentPage == 'before-after.php' ? 'bg-blue-600 text-white shadow-lg shadow-blue-900/50' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?>">
            <span class="mr-3 text-xl">🔄</span> Avant / Après CV
        </a>
        <a href="<?= $navBaseUrl ?>blog.php"
            class="group flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200 <?= ($currentPage == 'blog.php' || $currentPage == 'blog_edit.php') ? 'bg-blue-600 text-white shadow-lg shadow-blue-900/50' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?>">
            <span class="mr-3 text-xl">✏️</span> Blog & Articles
        </a>
        <a href="<?= $navBaseUrl ?>blog_subscribers.php"
            class="group flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200 <?= $currentPage == 'blog_subscribers.php' ? 'bg-teal-600 text-white shadow-lg shadow-teal-900/50' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?>">
            <span class="mr-3 text-xl">📧</span> Abonnés Blog
        </a>
        <a href="<?= $navBaseUrl ?>promos"
            class="group flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200 <?= $currentPage == 'promos.php' ? 'bg-green-600 text-white shadow-lg shadow-green-900/50' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?>">
            <span class="mr-3 text-xl">🎟️</span> Codes Promo
        </a>
        <a href="<?= $navBaseUrl ?>revenue"
            class="group flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200 <?= $currentPage == 'revenue.php' ? 'bg-yellow-600 text-white shadow-lg shadow-yellow-900/50' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?>">
            <span class="mr-3 text-xl">💰</span> Revenus (Transactions)
        </a>
        <a href="<?= $navBaseUrl ?>finance"
            class="group flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200 <?= $currentPage == 'finance.php' ? 'bg-blue-600 text-white shadow-lg shadow-blue-900/50' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?>">
            <span class="mr-3 text-xl">📈</span> Finance (P&L)
        </a>
        <a href="<?= $navBaseUrl ?>targets"
            class="group flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200 <?= $currentPage == 'targets.php' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-900/50' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?>">
            <span class="mr-3 text-xl">🎯</span> Objectifs
        </a>
        <a href="<?= $navBaseUrl ?>affiliates"
            class="group flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200 <?= $currentPage == 'affiliates.php' ? 'bg-pink-600 text-white shadow-lg shadow-pink-900/50' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?>">
            <span class="mr-3 text-xl">🤝</span> Ambassadeurs
        </a>
        <a href="<?= $navBaseUrl ?>analytics"
            class="group flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200 <?= $currentPage == 'analytics.php' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-900/50' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?>">
            <span class="mr-3 text-xl">📊</span> Analytiques
        </a>
        <a href="<?= $navBaseUrl ?>marketing.php"
            class="group flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200 <?= $currentPage == 'marketing.php' ? 'bg-blue-600 text-white shadow-lg shadow-blue-900/50' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?>">
            <span class="mr-3 text-xl">🚀</span> Marketing
        </a>
        <a href="<?= $navBaseUrl ?>sms-panel.php"
            class="group flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200 <?= $currentPage == 'sms-panel.php' ? 'bg-sky-600 text-white shadow-lg shadow-sky-900/50' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?>">
            <span class="mr-3 text-xl">📱</span> Dashboard SMS
        </a>
        <a href="<?= $navBaseUrl ?>leads_rescue.php"
            class="group flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200 <?= $currentPage == 'leads_rescue.php' ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-900/50' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?>">
            <span class="mr-3 text-xl">🚀</span> Leads Rescue
        </a>
        <a href="<?= $navBaseUrl ?>leads"
            class="group flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200 <?= $currentPage == 'leads.php' ? 'bg-yellow-600 text-white shadow-lg shadow-yellow-900/50' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?>">
            <span class="mr-3 text-xl">👻</span> Paniers (Relances)
        </a>
        <a href="<?= $navBaseUrl ?>export-leads.php"
            class="group flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200 <?= $currentPage == 'export-leads.php' ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-900/50' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?>">
            <span class="mr-3 text-xl">📝</span> Leads CV Builder
        </a>
        <a href="<?= $navBaseUrl ?>flash-analytics.php"
            class="group flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200 <?= $currentPage == 'flash-analytics.php' ? 'bg-yellow-500 text-white shadow-lg shadow-yellow-900/50' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?>">
            <span class="mr-3 text-xl">⚡</span> Stats Flash
        </a>
        <a href="<?= $navBaseUrl ?>settings"
            class="group flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200 <?= $currentPage == 'settings.php' ? 'bg-gray-700 text-white shadow-lg' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?>">
            <span class="mr-3 text-xl">⚙️</span> Paramètres
        </a>
        <a href="<?= $navBaseUrl ?>announcements"
            class="group flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200 <?= $currentPage == 'announcements.php' ? 'bg-purple-600 text-white shadow-lg shadow-purple-900/50' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?>">
            <span class="mr-3 text-xl">📢</span> Annonces
        </a>
        <a href="<?= $navBaseUrl ?>exit_popups"
            class="group flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200 <?= $currentPage == 'exit_popups.php' ? 'bg-purple-600 text-white shadow-lg shadow-purple-900/50' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?>">
            <span class="mr-3 text-xl">🚪</span> Popups Sortie
        </a>
        <a href="<?= $navBaseUrl ?>sponsorships"
            class="group flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200 <?= $currentPage == 'sponsorships.php' ? 'bg-teal-600 text-white shadow-lg shadow-teal-900/50' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?>">
            <span class="mr-3 text-xl">🤝</span> Parrainages
        </a>
        <a href="<?= $navBaseUrl ?>beneficiaries.php"
            class="group flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200 <?= $currentPage == 'beneficiaries.php' ? 'bg-blue-600 text-white shadow-lg shadow-blue-900/50' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?>">
            <span class="mr-3 text-xl">🎓</span> Bénéficiaires
        </a>
        <a href="<?= $navBaseUrl ?>school_leads.php"
            class="group flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200 <?= $currentPage == 'school_leads.php' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-900/50' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?>">
            <span class="mr-3 text-xl">🏫</span> Leads Écoles
        </a>
        <a href="<?= $navBaseUrl ?>club_platform.php"
            class="group flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200 <?= ($currentPage == 'club_platform.php' || $currentPage == 'subscriptions.php') ? 'bg-blue-600 text-white shadow-lg shadow-blue-900/50' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?>">
            <span class="mr-3 text-xl">⚡</span> Club HireMe
        </a>
        <a href="<?= $navBaseUrl ?>manage_admins"
            class="group flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200 <?= $currentPage == 'manage_admins.php' ? 'bg-purple-600 text-white shadow-lg shadow-purple-900/50' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?>">
            <span class="mr-3 text-xl">🛡️</span> Admins
        </a>
        <a href="<?= $navBaseUrl ?>security.php"
            class="group flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200 <?= $currentPage == 'security.php' ? 'bg-red-600 text-white shadow-lg shadow-red-900/50' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?>">
            <span class="mr-3 text-xl">🔒</span> Sécurité
        </a>
        <a href="<?= $navBaseUrl ?>litiges.php"
            class="group flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200 <?= $currentPage == 'litiges.php' ? 'bg-black text-white shadow-lg shadow-gray-900/50' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?>">
            <span class="mr-3 text-xl">🏴‍☠️</span> Gestion Litiges
        </a>
        <a href="<?= $navBaseUrl ?>remboursements.php"
            class="group flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200 <?= $currentPage == 'remboursements.php' ? 'bg-orange-600 text-white shadow-lg shadow-orange-900/50' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?>">
            <span class="mr-3 text-xl">💸</span> Remboursements
        </a>
        <a href="<?= $navBaseUrl ?>cleanup_non_payers.php"
            class="group flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200 <?= $currentPage == 'cleanup_non_payers.php' ? 'bg-red-900 text-white shadow-lg shadow-red-950/50' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?>">
            <span class="mr-3 text-xl">🧹</span> Nettoyage CV
        </a>
        <a href="<?= $navBaseUrl ?>reviews.php"
            class="group flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200 <?= $currentPage == 'reviews.php' ? 'bg-yellow-500 text-white shadow-lg shadow-yellow-900/50' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?>">
            <span class="mr-3 text-xl">⭐</span> Avis Clients
        </a>
    </nav>
    <div class="p-4 border-t border-gray-800">
        <a href="<?= $navBaseUrl ?>logout.php"
            class="flex items-center justify-center w-full px-4 py-2 text-sm font-medium text-red-100 bg-red-900 rounded-lg hover:bg-red-800 transition-colors">
            🔓 Déconnexion
        </a>
    </div>
</aside>

<!-- Mobile Bottom Navigation ($10k Premium Version) -->
<style>
    /* Safe Area for iPhone Home Indicator */
    .pb-safe { padding-bottom: env(safe-area-inset-bottom); }
    /* Hide scrollbar for drawer */
    .no-scrollbar::-webkit-scrollbar { display: none; }
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    
    /* Smooth Drawer Transition */
    #mobile-drawer { transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1); }
    #drawer-overlay { transition: opacity 0.3s ease-in-out; }
    
    /* Content Padding Fix */
    body { padding-bottom: 80px; } 
    @media (min-width: 768px) { body { padding-bottom: 0; } }
</style>

<!-- 1. The Drawer (Hidden by default, slides up) -->
<div id="drawer-overlay" onclick="toggleDrawer()" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[60] hidden opacity-0 pointer-events-none md:hidden transition-opacity duration-300"></div>

<div id="mobile-drawer" class="fixed bottom-0 left-0 right-0 bg-slate-900 text-white z-[70] rounded-t-[2.5rem] transform translate-y-full md:hidden shadow-[0_-10px_40px_rgba(0,0,0,0.5)] flex flex-col max-h-[85vh]">
    
    <!-- Drawer Handle -->
    <div class="w-full flex justify-between items-center p-6 border-b border-white/10" onclick="toggleDrawer()">
        <span class="text-lg font-black tracking-tight">Menu Complet</span>
        <button class="p-2 bg-white/10 rounded-full hover:bg-white/20 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
    </div>

    <!-- Drawer Content (Scrollable) -->
    <div class="overflow-y-auto p-6 space-y-2 no-scrollbar pb-24">
        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2 ml-2">Gestion</p>
        
        <a href="<?= $navBaseUrl ?>clients" class="flex items-center p-4 rounded-xl bg-white/5 hover:bg-blue-600/20 hover:text-blue-400 transition-all group">
            <span class="text-2xl mr-4 group-hover:scale-110 transition-transform">👥</span>
            <div>
                <div class="font-bold">Clients (CV)</div>
                <div class="text-xs text-slate-400">Gérer les dossiers finalisés</div>
            </div>
        </a>
        <a href="<?= $navBaseUrl ?>redesign_requests.php" class="flex items-center p-4 rounded-xl bg-white/5 hover:bg-blue-600/20 hover:text-blue-400 transition-all group">
            <span class="text-2xl mr-4 group-hover:scale-110 transition-transform">🔄</span>
            <div>
                <div class="font-bold">Demandes Refonte</div>
                <div class="text-xs text-slate-400">Garantie & Refonte CV</div>
            </div>
        </a>
        <a href="<?= $navBaseUrl ?>cv_packs.php" class="flex items-center p-4 rounded-xl bg-white/5 hover:bg-blue-600/20 hover:text-blue-400 transition-all group">
            <span class="text-2xl mr-4 group-hover:scale-110 transition-transform">📦</span>
            <div>
                <div class="font-bold">Packs CV</div>
                <div class="text-xs text-slate-400">Gérer les liens de téléchargement</div>
            </div>
        </a>
        <a href="<?= $navBaseUrl ?>foreign_customers" class="flex items-center p-4 rounded-xl bg-white/5 hover:bg-indigo-600/20 hover:text-indigo-400 transition-all group">
            <span class="text-2xl mr-4 group-hover:scale-110 transition-transform">🌍</span>
            <div>
                <div class="font-bold">Clients Étrangers</div>
                <div class="text-xs text-slate-400">Suivi des ventes internationales</div>
            </div>
        </a>
        <a href="<?= $navBaseUrl ?>orders?type=advance" class="flex items-center p-4 rounded-xl bg-white/5 hover:bg-purple-600/20 hover:text-purple-400 transition-all group">
            <span class="text-2xl mr-4 group-hover:scale-110 transition-transform">📅</span>
            <div>
                <div class="font-bold">Réservations</div>
                <div class="text-xs text-slate-400">Gérer les pré-commandes</div>
            </div>
        </a>
        <a href="<?= $navBaseUrl ?>school_leads.php" class="flex items-center p-4 rounded-xl bg-white/5 hover:bg-indigo-600/20 hover:text-indigo-400 transition-all group">
            <span class="text-2xl mr-4 group-hover:scale-110 transition-transform">🏫</span>
            <div>
                <div class="font-bold">Leads Écoles</div>
                <div class="text-xs text-slate-400">Demandes de partenariats</div>
            </div>
        </a>
        <a href="<?= $navBaseUrl ?>club_platform.php" class="flex items-center p-4 rounded-xl bg-white/5 hover:bg-blue-600/20 hover:text-blue-400 transition-all group">
            <span class="text-2xl mr-4 group-hover:scale-110 transition-transform">⚡</span>
            <div>
                <div class="font-bold">Club HireMe</div>
                <div class="text-xs text-slate-400">Plateforme de Membres V2</div>
            </div>
        </a>
        <a href="<?= $navBaseUrl ?>cv-models" class="flex items-center p-4 rounded-xl bg-white/5 hover:bg-blue-600/20 hover:text-blue-400 transition-all group">
            <span class="text-2xl mr-4 group-hover:scale-110 transition-transform">🎨</span>
            <div>
                <div class="font-bold">Modèles CV</div>
                <div class="text-xs text-slate-400">Ajouter ou modifier des designs</div>
            </div>
        </a>
        <a href="<?= $navBaseUrl ?>before-after.php" class="flex items-center p-4 rounded-xl bg-white/5 hover:bg-blue-600/20 hover:text-blue-400 transition-all group">
            <span class="text-2xl mr-4 group-hover:scale-110 transition-transform">🔄</span>
            <div>
                <div class="font-bold">Avant / Après CV</div>
                <div class="text-xs text-slate-400">Gérer les comparaisons carrousel (40s)</div>
            </div>
        </a>
        <a href="<?= $navBaseUrl ?>blog.php" class="flex items-center p-4 rounded-xl bg-white/5 hover:bg-blue-600/20 hover:text-blue-400 transition-all group">
            <span class="text-2xl mr-4 group-hover:scale-110 transition-transform">✏️</span>
            <div>
                <div class="font-bold">Blog & Articles</div>
                <div class="text-xs text-slate-400">Gérer les articles de conseil CV</div>
            </div>
        </a>
        <a href="<?= $navBaseUrl ?>blog_subscribers.php" class="flex items-center p-4 rounded-xl bg-white/5 hover:bg-teal-600/20 hover:text-teal-400 transition-all group">
            <span class="text-2xl mr-4 group-hover:scale-110 transition-transform">📧</span>
            <div>
                <div class="font-bold">Abonnés Blog</div>
                <div class="text-xs text-slate-400">Leads capturés par le blog</div>
            </div>
        </a>

        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mt-6 mb-2 ml-2">Finance & Marketing</p>
        
        <a href="<?= $navBaseUrl ?>revenue" class="flex items-center p-4 rounded-xl bg-white/5 hover:bg-yellow-600/20 hover:text-yellow-400 transition-all group">
            <span class="text-2xl mr-4 group-hover:scale-110 transition-transform">💰</span>
            <div class="font-bold">Revenus (Transactions)</div>
        </a>
        <a href="<?= $navBaseUrl ?>finance" class="flex items-center p-4 rounded-xl bg-white/5 hover:bg-yellow-600/20 hover:text-yellow-400 transition-all group">
            <span class="text-2xl mr-4 group-hover:scale-110 transition-transform">📈</span>
            <div class="font-bold">Finance (P&L)</div>
        </a>
        <a href="<?= $navBaseUrl ?>targets" class="flex items-center p-4 rounded-xl bg-white/5 hover:bg-indigo-600/20 hover:text-indigo-400 transition-all group">
            <span class="text-2xl mr-4 group-hover:scale-110 transition-transform">🎯</span>
            <div class="font-bold">Objectifs</div>
        </a>
        <a href="<?= $navBaseUrl ?>promos" class="flex items-center p-4 rounded-xl bg-white/5 hover:bg-green-600/20 hover:text-green-400 transition-all group">
            <span class="text-2xl mr-4 group-hover:scale-110 transition-transform">🎟️</span>
            <div class="font-bold">Codes Promo</div>
        </a>
        <a href="<?= $navBaseUrl ?>affiliates" class="flex items-center p-4 rounded-xl bg-white/5 hover:bg-pink-600/20 hover:text-pink-400 transition-all group">
            <span class="text-2xl mr-4 group-hover:scale-110 transition-transform">🤝</span>
            <div class="font-bold">Ambassadeurs</div>
        </a>
        <a href="<?= $navBaseUrl ?>marketing.php" class="flex items-center p-4 rounded-xl bg-white/5 hover:bg-blue-600/20 hover:text-blue-400 transition-all group">
            <span class="text-2xl mr-4 group-hover:scale-110 transition-transform">🚀</span>
            <div class="font-bold">Marketing & Exports</div>
        </a>
        <a href="<?= $navBaseUrl ?>sms-panel.php" class="flex items-center p-4 rounded-xl bg-white/5 hover:bg-sky-600/20 hover:text-sky-400 transition-all group">
            <span class="text-2xl mr-4 group-hover:scale-110 transition-transform">📱</span>
            <div>
                <div class="font-bold">Dashboard SMS</div>
                <div class="text-xs text-slate-400">Rapports & Campagnes TextBee</div>
            </div>
        </a>

        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mt-6 mb-2 ml-2">Système</p>
        
        <a href="<?= $navBaseUrl ?>analytics" class="flex items-center p-4 rounded-xl bg-white/5 hover:bg-indigo-600/20 hover:text-indigo-400 transition-all group">
            <span class="text-2xl mr-4 group-hover:scale-110 transition-transform">📊</span>
            <div class="font-bold">Analytiques</div>
        </a>
        <a href="<?= $navBaseUrl ?>flash-analytics.php" class="flex items-center p-4 rounded-xl bg-white/5 hover:bg-yellow-600/20 hover:text-yellow-400 transition-all group">
            <span class="text-2xl mr-4 group-hover:scale-110 transition-transform">⚡</span>
            <div class="font-bold">Stats Flash</div>
        </a>
         <a href="<?= $navBaseUrl ?>exit_popups" class="flex items-center p-4 rounded-xl bg-white/5 hover:bg-purple-600/20 hover:text-purple-400 transition-all group">
            <span class="text-2xl mr-4 group-hover:scale-110 transition-transform">🚪</span>
            <div class="font-bold">Popups Sortie</div>
        </a>
        <a href="<?= $navBaseUrl ?>announcements" class="flex items-center p-4 rounded-xl bg-white/5 hover:bg-purple-600/20 hover:text-purple-400 transition-all group">
            <span class="text-2xl mr-4 group-hover:scale-110 transition-transform">📢</span>
            <div class="font-bold">Annonces</div>
        </a>
        <a href="<?= $navBaseUrl ?>litiges.php" class="flex items-center p-4 rounded-xl bg-white/5 hover:bg-gray-600/20 hover:text-gray-400 transition-all group">
            <span class="text-2xl mr-4 group-hover:scale-110 transition-transform">🏴‍☠️</span>
            <div class="font-bold">Gestion Litiges</div>
        </a>
        <a href="<?= $navBaseUrl ?>remboursements.php" class="flex items-center p-4 rounded-xl bg-white/5 hover:bg-orange-600/20 hover:text-orange-400 transition-all group">
            <span class="text-2xl mr-4 group-hover:scale-110 transition-transform">💸</span>
            <div class="font-bold">Remboursements</div>
        </a>
        <a href="<?= $navBaseUrl ?>manage_admins" class="flex items-center p-4 rounded-xl bg-white/5 hover:bg-purple-600/20 hover:text-purple-400 transition-all group">
            <span class="text-2xl mr-4 group-hover:scale-110 transition-transform">🛡️</span>
            <div class="font-bold">Administrateurs</div>
        </a>
        <a href="<?= $navBaseUrl ?>security.php" class="flex items-center p-4 rounded-xl bg-white/5 hover:bg-red-600/20 hover:text-red-400 transition-all group">
            <span class="text-2xl mr-4 group-hover:scale-110 transition-transform">🔒</span>
            <div class="font-bold">Sécurité</div>
        </a>
        <a href="<?= $navBaseUrl ?>reviews.php" class="flex items-center p-4 rounded-xl bg-white/5 hover:bg-yellow-600/20 hover:text-yellow-400 transition-all group">
            <span class="text-2xl mr-4 group-hover:scale-110 transition-transform">⭐</span>
            <div class="font-bold">Avis Clients</div>
        </a>
        <a href="<?= $navBaseUrl ?>settings" class="flex items-center p-4 rounded-xl bg-white/5 hover:bg-gray-600/20 hover:text-gray-400 transition-all group">
            <span class="text-2xl mr-4 group-hover:scale-110 transition-transform">⚙️</span>
            <div class="font-bold">Paramètres</div>
        </a>
        <a href="<?= $navBaseUrl ?>leads" class="flex items-center p-4 rounded-xl bg-white/5 hover:bg-yellow-600/20 hover:text-yellow-400 transition-all group">
            <span class="text-2xl mr-4 group-hover:scale-110 transition-transform">🛒</span>
            <div class="font-bold">Paniers Abandonnés</div>
        </a>
        <a href="<?= $navBaseUrl ?>export-leads.php" class="flex items-center p-4 rounded-xl bg-white/5 hover:bg-emerald-600/20 hover:text-emerald-400 transition-all group">
            <span class="text-2xl mr-4 group-hover:scale-110 transition-transform">📝</span>
            <div>
                <div class="font-bold">Leads CV Builder</div>
                <div class="text-xs text-slate-400">Exportation prospects du CV Builder</div>
            </div>
        </a>
        <a href="<?= $navBaseUrl ?>kanban" class="flex items-center p-4 rounded-xl bg-white/5 hover:bg-orange-600/20 hover:text-orange-400 transition-all group">
            <span class="text-2xl mr-4 group-hover:scale-110 transition-transform">📋</span>
            <div class="font-bold">CRM Kanban</div>
        </a>

        <a href="<?= $navBaseUrl ?>logout.php" class="flex items-center p-4 rounded-xl bg-red-500/10 text-red-400 hover:bg-red-500 hover:text-white transition-all mt-8">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
            <div class="font-bold">Déconnexion</div>
        </a>
    </div>
</div>

<!-- 2. The Bottom Bar (Glassmorphism, High Priority Items Only) -->
<nav class="md:hidden fixed bottom-6 left-4 right-4 bg-slate-900/90 backdrop-blur-xl border border-white/10 rounded-2xl shadow-[0_8px_32px_rgba(0,0,0,0.3)] z-50 flex justify-between items-center px-6 py-4 transition-all duration-300">
    
    <!-- Home -->
    <a href="<?= $navBaseUrl ?>index.php" class="flex flex-col items-center gap-1 group">
        <div class="p-2 rounded-xl transition-all <?= $currentPage == 'index.php' ? 'bg-blue-500 text-white shadow-lg shadow-blue-500/40' : 'text-slate-400 group-hover:text-white' ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
        </div>
        <span class="text-[9px] font-bold uppercase tracking-wide <?= $currentPage == 'index.php' ? 'text-white' : 'text-slate-500' ?>">Accueil</span>
    </a>

    <!-- Live Pulse -->
    <a href="<?= $navBaseUrl ?>live.php" class="flex flex-col items-center gap-1 group">
        <div class="p-2 rounded-xl transition-all <?= $currentPage == 'live.php' ? 'bg-red-500 text-white shadow-lg shadow-red-500/40' : 'text-slate-400 group-hover:text-white' ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
        </div>
        <span class="text-[9px] font-bold uppercase tracking-wide <?= $currentPage == 'live.php' ? 'text-white' : 'text-slate-500' ?>">Live</span>
    </a>

    <!-- Orders -->
    <a href="<?= $navBaseUrl ?>orders.php" class="flex flex-col items-center gap-1 group">
        <div class="p-2 rounded-xl transition-all <?= ($currentPage == 'orders.php' || $currentPage == 'order-details.php') ? 'bg-blue-500 text-white shadow-lg shadow-blue-500/40' : 'text-slate-400 group-hover:text-white' ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
        </div>
        <span class="text-[9px] font-bold uppercase tracking-wide <?= ($currentPage == 'orders.php' || $currentPage == 'order-details.php') ? 'text-white' : 'text-slate-500' ?>">Commandes</span>
    </a>

    <!-- FAB: Prospects (Central) -->
    <a href="<?= $navBaseUrl ?>prospects.php" class="relative -top-8 group">
        <div class="w-14 h-14 rounded-full bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center shadow-xl shadow-orange-500/40 border-4 border-gray-100 group-hover:scale-110 transition-transform duration-300">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
        </div>
    </a>

    <!-- Menu Trigger -->
    <button onclick="toggleDrawer()" class="flex flex-col items-center gap-1 group">
         <div class="p-2 rounded-xl transition-all text-slate-400 group-hover:text-white group-active:scale-90">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path></svg>
        </div>
        <span class="text-[9px] font-bold uppercase tracking-wide text-slate-500">Menu</span>
    </button>

</nav>

<script>
    function toggleDrawer() {
        const drawer = document.getElementById('mobile-drawer');
        const overlay = document.getElementById('drawer-overlay');
        const isClosed = drawer.classList.contains('translate-y-full');

        if (isClosed) {
            // Open
            overlay.classList.remove('hidden', 'pointer-events-none', 'opacity-0');
            drawer.classList.remove('translate-y-full');
            document.body.style.overflow = 'hidden'; // Prevent background scrolling
        } else {
            // Close
            overlay.classList.add('opacity-0', 'pointer-events-none');
            drawer.classList.add('translate-y-full');
            setTimeout(() => {
                overlay.classList.add('hidden');
            }, 300);
            document.body.style.overflow = '';
        }
    }
</script>