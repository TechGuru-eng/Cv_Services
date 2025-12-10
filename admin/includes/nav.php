<?php
// admin/includes/nav.php
?>
<aside class="w-64 bg-gray-900 text-white min-h-screen hidden md:block">
    <div class="p-6">
        <h1 class="text-2xl font-bold text-blue-400">HireMe <span class="text-white text-sm">Admin</span></h1>
    </div>
    <nav class="mt-6">
        <a href="index.php"
            class="block py-3 px-6 hover:bg-gray-800 border-l-4 border-transparent hover:border-blue-500 transition">
            🏠 Dashboard
        </a>
        <a href="orders.php"
            class="block py-3 px-6 hover:bg-gray-800 border-l-4 border-transparent hover:border-blue-500 transition">
            📋 Commandes
        </a>
        <a href="promos.php"
            class="block py-3 px-6 hover:bg-gray-800 border-l-4 border-transparent hover:border-green-500 transition">
            🎟️ Codes Promo <span class="bg-green-600 text-xs px-2 py-1 rounded ml-2">NEW</span>
        </a>
        <a href="revenue.php"
            class="block py-3 px-6 hover:bg-gray-800 border-l-4 border-transparent hover:border-yellow-500 transition">
            💰 Revenus
        </a>
        <a href="logout.php" class="block py-3 px-6 mt-10 text-red-400 hover:text-red-300 transition">
            🔓 Déconnexion
        </a>
    </nav>
</aside>