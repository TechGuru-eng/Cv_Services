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
        <a href="<?= $navBaseUrl ?>index.php"
            class="group flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200 <?= $currentPage == 'index.php' ? 'bg-blue-600 text-white shadow-lg shadow-blue-900/50' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?>">
            <span class="mr-3 text-xl">🏠</span> Dashboard
        </a>
        <a href="<?= $navBaseUrl ?>orders.php"
            class="group flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200 <?= ($currentPage == 'orders.php' || $currentPage == 'order-details.php') ? 'bg-blue-600 text-white shadow-lg shadow-blue-900/50' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?>">
            <span class="mr-3 text-xl">📋</span> Commandes
        </a>
        <a href="<?= $navBaseUrl ?>clients.php"
            class="group flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200 <?= $currentPage == 'clients.php' ? 'bg-blue-600 text-white shadow-lg shadow-blue-900/50' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?>">
            <span class="mr-3 text-xl">👥</span> Clients (CV)
        </a>
        <a href="<?= $navBaseUrl ?>promos.php"
            class="group flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200 <?= $currentPage == 'promos.php' ? 'bg-green-600 text-white shadow-lg shadow-green-900/50' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?>">
            <span class="mr-3 text-xl">🎟️</span> Codes Promo
        </a>
        <a href="<?= $navBaseUrl ?>revenue.php"
            class="group flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200 <?= $currentPage == 'revenue.php' ? 'bg-yellow-600 text-white shadow-lg shadow-yellow-900/50' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?>">
            <span class="mr-3 text-xl">💰</span> Revenus
        </a>
        <a href="<?= $navBaseUrl ?>settings.php"
            class="group flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200 <?= $currentPage == 'settings.php' ? 'bg-gray-700 text-white shadow-lg' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?>">
            <span class="mr-3 text-xl">⚙️</span> Paramètres
        </a>
    </nav>
    <div class="p-4 border-t border-gray-800">
        <a href="<?= $navBaseUrl ?>logout.php"
            class="flex items-center justify-center w-full px-4 py-2 text-sm font-medium text-red-100 bg-red-900 rounded-lg hover:bg-red-800 transition-colors">
            🔓 Déconnexion
        </a>
    </div>
</aside>

<!-- Mobile Bottom Navigation (Visible only on mobile) -->
<nav
    class="md:hidden fixed bottom-0 left-0 right-0 bg-white/90 backdrop-blur-md border-t border-gray-200 shadow-[0_-5px_15px_rgba(0,0,0,0.05)] z-50 pb-safe">
    <div class="flex justify-around items-center h-16 px-1">

        <a href="<?= $navBaseUrl ?>index.php"
            class="flex flex-col items-center justify-center w-full h-full space-y-1 <?= $currentPage == 'index.php' ? 'text-blue-600' : 'text-gray-500 hover:text-gray-700' ?>">
            <svg class="w-6 h-6 <?= $currentPage == 'index.php' ? 'fill-current' : 'fill-none stroke-current' ?>"
                viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
            <span class="text-[10px] font-medium leading-none">Accueil</span>
        </a>

        <a href="<?= $navBaseUrl ?>orders.php"
            class="flex flex-col items-center justify-center w-full h-full space-y-1 <?= ($currentPage == 'orders.php' || $currentPage == 'order-details.php') ? 'text-blue-600' : 'text-gray-500 hover:text-gray-700' ?>">
            <svg class="w-6 h-6 <?= ($currentPage == 'orders.php' || $currentPage == 'order-details.php') ? 'fill-current' : 'fill-none stroke-current' ?>"
                viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
            <span class="text-[10px] font-medium leading-none">Commandes</span>
        </a>

        <!-- FAB for Revenue/Clients (Highlight) -->
        <a href="<?= $navBaseUrl ?>clients.php"
            class="relative -top-5 bg-blue-600 text-white w-14 h-14 rounded-full shadow-lg shadow-blue-500/40 flex items-center justify-center transform transition-transform active:scale-95 border-4 border-white">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
        </a>

        <a href="<?= $navBaseUrl ?>revenue.php"
            class="flex flex-col items-center justify-center w-full h-full space-y-1 <?= $currentPage == 'revenue.php' ? 'text-blue-600' : 'text-gray-500 hover:text-gray-700' ?>">
            <svg class="w-6 h-6 <?= $currentPage == 'revenue.php' ? 'fill-current' : 'fill-none stroke-current' ?>"
                viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="text-[10px] font-medium leading-none">Revenus</span>
        </a>

        <a href="<?= $navBaseUrl ?>settings.php"
            class="flex flex-col items-center justify-center w-full h-full space-y-1 <?= $currentPage == 'settings.php' ? 'text-blue-600' : 'text-gray-500 hover:text-gray-700' ?>">
            <svg class="w-6 h-6 <?= $currentPage == 'settings.php' ? 'fill-current' : 'fill-none stroke-current' ?>"
                viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <span class="text-[10px] font-medium leading-none">Reglages</span>
        </a>

    </div>
</nav>