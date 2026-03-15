<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}
require_once 'config/db.php';
include 'includes/header.php';
?>
<body class="bg-[#0f172a] font-sans text-slate-200 overflow-hidden">
    
    <div class="flex h-screen">
        <!-- Sidebar -->
        <?php include 'includes/nav.php'; ?>

        <div class="flex-1 flex flex-col min-h-screen overflow-hidden">
            <!-- Header -->
            <header class="bg-slate-900/50 backdrop-blur-md border-b border-white/5 p-6 flex justify-between items-center z-20">
                <div class="flex items-center gap-4">
                    <div class="relative">
                        <div class="w-3 h-3 bg-red-500 rounded-full animate-ping absolute"></div>
                        <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                    </div>
                    <div>
                        <h2 class="text-2xl font-black tracking-tight text-white flex items-center gap-2">Live Pulse <span class="text-xs bg-red-500/20 text-red-400 px-2 py-0.5 rounded-full font-bold uppercase tracking-widest animate-pulse">En Direct</span></h2>
                        <p class="text-xs font-medium text-slate-400">Surveillance en temps réel de l'activité utilisateur</p>
                    </div>
                </div>
                <div class="flex items-center gap-6">
                    <div class="flex items-center gap-3 bg-slate-800/50 px-4 py-2 rounded-2xl border border-white/5">
                        <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Cacher Bots</p>
                        <button id="bot-toggle" class="w-10 h-5 bg-slate-700 rounded-full relative transition-colors focus:outline-none">
                            <div class="absolute left-1 top-1 w-3 h-3 bg-white rounded-full transition-transform" id="bot-toggle-knob"></div>
                        </button>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Revenu Aujourd'hui</p>
                        <p id="today-revenue" class="text-xl font-black text-green-400">0 XAF</p>
                    </div>
                    <div id="sync-indicator" class="w-10 h-10 rounded-full bg-slate-800 flex items-center justify-center border border-white/10 transition-all duration-300">
                        <i data-lucide="refresh-cw" class="w-5 h-5 text-slate-400"></i>
                    </div>
                </div>
            </header>

            <main class="flex-1 overflow-x-hidden overflow-y-auto p-6 md:p-8 space-y-8 bg-slate-950">
                
                <!-- Main Stats -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-slate-900 p-8 rounded-[2.5rem] border border-white/5 flex items-center justify-between group hover:border-blue-500/30 transition-all">
                        <div>
                            <p class="text-slate-500 text-[10px] font-black uppercase tracking-widest mb-2">Visiteurs Actifs</p>
                            <h3 id="active-now" class="text-5xl font-black text-white italic tracking-tighter">0</h3>
                        </div>
                        <div class="p-5 bg-blue-500/10 text-blue-400 rounded-3xl group-hover:scale-110 transition-transform">
                            <i data-lucide="users" class="w-10 h-10"></i>
                        </div>
                    </div>

                    <div class="bg-slate-900 p-8 rounded-[2.5rem] border border-white/5 flex items-center justify-between group hover:border-orange-500/30 transition-all">
                        <div>
                            <p class="text-slate-500 text-[10px] font-black uppercase tracking-widest mb-2">Dernière Action</p>
                            <h3 id="last-update" class="text-2xl font-black text-white italic tracking-tighter">--:--</h3>
                        </div>
                        <div class="p-5 bg-orange-500/10 text-orange-400 rounded-3xl group-hover:scale-110 transition-transform">
                            <i data-lucide="zap" class="w-10 h-10"></i>
                        </div>
                    </div>

                    <div class="bg-slate-900 p-8 rounded-[2.5rem] border border-white/5 flex items-center justify-between group hover:border-green-500/30 transition-all">
                        <div>
                            <p class="text-slate-500 text-[10px] font-black uppercase tracking-widest mb-2">Status Système</p>
                            <h3 class="text-2xl font-black text-green-400 italic tracking-tighter">Optimal</h3>
                        </div>
                        <div class="p-5 bg-green-500/10 text-green-400 rounded-3xl group-hover:scale-110 transition-transform">
                            <i data-lucide="shield-check" class="w-10 h-10"></i>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- High Intent Feed -->
                    <div class="lg:col-span-1 space-y-6">
                        <h4 class="text-sm font-black text-slate-500 uppercase tracking-widest flex items-center gap-2">
                            <span class="w-2 h-2 bg-orange-500 rounded-full"></span> Adrénaline Marketing
                        </h4>
                        <div id="high-intent-feed" class="space-y-4">
                            <!-- Events will be injected here -->
                            <div class="p-8 text-center text-slate-600 bg-slate-900/30 rounded-[2rem] border border-white/5 animate-pulse">
                                En attente de signaux... 📡
                            </div>
                        </div>
                    </div>

                    <!-- Visitor Board -->
                    <div class="lg:col-span-2 space-y-6">
                        <div class="flex justify-between items-center">
                            <h4 class="text-sm font-black text-slate-500 uppercase tracking-widest flex items-center gap-2">
                                <span class="w-2 h-2 bg-blue-500 rounded-full"></span> Radar Visiteurs
                            </h4>
                            <span id="visitor-count-badge" class="px-3 py-1 bg-slate-800 text-slate-400 rounded-full text-[10px] font-black">0 Connectés</span>
                        </div>
                        
                        <div class="bg-slate-900 rounded-[2.5rem] border border-white/5 overflow-hidden">
                            <table class="w-full text-left">
                                <thead class="bg-slate-800/50 text-[9px] font-black text-slate-500 uppercase tracking-widest">
                                    <tr>
                                        <th class="p-6">Visiteur</th>
                                        <th class="p-6">Localisation</th>
                                        <th class="p-6">Page Actuelle</th>
                                        <th class="p-6 text-right">Activité</th>
                                    </tr>
                                </thead>
                                <tbody id="visitor-table-body" class="divide-y divide-white/5">
                                    <!-- Visitors will be injected here -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <!-- Audio Alerts -->
    <audio id="alert-high" src="https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3" preload="auto"></audio>
    <audio id="alert-low" src="https://assets.mixkit.co/active_storage/sfx/2358/2358-preview.mp3" preload="auto"></audio>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();

        let lastEventId = null;

        let hideBots = localStorage.getItem('hide_bots') === 'true';
        updateToggleUI();

        document.getElementById('bot-toggle').addEventListener('click', () => {
            hideBots = !hideBots;
            localStorage.setItem('hide_bots', hideBots);
            updateToggleUI();
            fetchLiveStats();
        });

        function updateToggleUI() {
            const toggle = document.getElementById('bot-toggle');
            const knob = document.getElementById('bot-toggle-knob');
            if (hideBots) {
                toggle.classList.replace('bg-slate-700', 'bg-blue-600');
                knob.style.transform = 'translateX(20px)';
            } else {
                toggle.classList.replace('bg-blue-600', 'bg-slate-700');
                knob.style.transform = 'translateX(0)';
            }
        }

        async function fetchLiveStats() {
            const syncIcon = document.querySelector('#sync-indicator i');
            syncIcon.classList.add('animate-spin');
            
            try {
                const response = await fetch(`api/get_live_stats.php?hide_bots=${hideBots ? 1 : 0}`);
                const data = await response.json();
                
                if (data.error) return;

                // Update Stats
                document.getElementById('active-now').textContent = data.active_now;
                document.getElementById('last-update').textContent = data.timestamp.split(' ')[1];
                document.getElementById('today-revenue').textContent = new Intl.NumberFormat('fr-FR').format(data.today_revenue) + ' XAF';
                document.getElementById('visitor-count-badge').textContent = data.visitors.length + (hideBots ? ' Humains' : ' Actifs');

                const serverNow = data.server_now_ms;

                // Update Visitors
                const visitorBody = document.getElementById('visitor-table-body');
                visitorBody.innerHTML = '';
                data.visitors.forEach(v => {
                    const row = document.createElement('tr');
                    row.className = 'hover:bg-white/5 transition-colors group';
                    row.innerHTML = `
                        <td class="p-6">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-2xl bg-slate-800 border border-white/10 flex items-center justify-center font-black text-slate-400 group-hover:text-blue-400 transition-colors">
                                    ${v.ip_address.split('.').slice(-1)}
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <p class="text-xs font-black text-white">${v.ip_address}</p>
                                        ${v.is_bot == 1 ? '<span class="text-[10px] bg-amber-500/20 text-amber-400 px-1.5 py-0.5 rounded font-black">BOT</span>' : ''}
                                    </div>
                                    <p class="text-[9px] text-slate-500 font-bold uppercase tracking-tighter">${v.browser || 'Navigateur Inconnu'}</p>
                                </div>
                            </div>
                        </td>
                        <td class="p-6">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold text-slate-400">${v.country || '🌍 Global'}</span>
                            </div>
                        </td>
                        <td class="p-6">
                            <span class="text-[10px] font-black text-blue-400 bg-blue-500/10 px-3 py-1 rounded-full uppercase truncate max-w-[150px] inline-block">
                                ${v.page_url === '/' ? 'Accueil' : v.page_url}
                            </span>
                        </td>
                        <td class="p-6 text-right">
                            <span class="text-[10px] font-black text-slate-500 italic">${calculateTimeAgo(v.updated_at, serverNow)}</span>
                        </td>
                    `;
                    visitorBody.appendChild(row);
                });

                // Update High Intent Feed
                const feed = document.getElementById('high-intent-feed');
                if (data.high_intent.length > 0) {
                    // Check if new event for sound
                    const newestEvent = data.high_intent[0];
                    if (lastEventId && newestEvent.id !== lastEventId) {
                        triggerAlert(newestEvent.event_name);
                    }
                    lastEventId = newestEvent.id;

                    feed.innerHTML = '';
                    data.high_intent.forEach(e => {
                        const card = document.createElement('div');
                        let color = 'blue';
                        let icon = 'eye';
                        let label = 'Action';

                        if(e.event_name === 'payment_initiate') { color = 'purple'; icon = 'credit-card'; label = 'Paiement Lancé'; }
                        if(e.event_name === 'pack_selected') { color = 'orange'; icon = 'shopping-bag'; label = 'Pack Choisi'; }
                        if(e.event_name === 'click_whatsapp_float') { color = 'green'; icon = 'message-circle'; label = 'Contact WhatsApp'; }

                        card.className = `p-6 bg-${color}-500/10 border border-${color}-500/20 rounded-[2rem] flex items-start gap-4 animate-in slide-in-from-right duration-500`;
                        card.innerHTML = `
                            <div class="p-3 bg-${color}-500/20 text-${color}-400 rounded-2xl">
                                <i data-lucide="${icon}" class="w-5 h-5"></i>
                            </div>
                            <div class="flex-1">
                                <div class="flex justify-between mb-1">
                                    <span class="text-[9px] font-black text-${color}-400 uppercase tracking-widest">${label}</span>
                                    <span class="text-[9px] font-black text-slate-600">${calculateTimeAgo(e.created_at, serverNow)}</span>
                                </div>
                                <p class="text-[11px] font-bold text-slate-200 mt-1">${formatEventData(e.event_data)}</p>
                                <p class="text-[9px] text-slate-500 mt-2 font-medium">📍 ${e.country || 'Inconnu'} • ${e.ip_address}</p>
                            </div>
                        `;
                        feed.appendChild(card);
                    });
                    lucide.createIcons();
                }

            } catch (err) {
                console.error("Live sync failed", err);
            } finally {
                setTimeout(() => syncIcon.classList.remove('animate-spin'), 1000);
            }
        }

        function calculateTimeAgo(timestamp, serverNowMs) {
            const date = new Date(timestamp.replace(/-/g, '/'));
            const now = new Date(serverNowMs);
            const diff = Math.floor((now - date) / 1000);
            if (diff <= 0) return 'À l\'instant';
            if (diff < 60) return `Il y a ${diff}s`;
            if (diff < 3600) return `Il y a ${Math.floor(diff / 60)}m`;
            return `Il y a ${Math.floor(diff / 3600)}h`;
        }

        function formatEventData(data) {
            try {
                const parsed = JSON.parse(data);
                if (parsed.pack) return `Intérêt pour : ${parsed.pack.toUpperCase()}`;
                if (parsed.country) return `Acheteur de : ${parsed.country}`;
                return data;
            } catch { return data; }
        }

        function triggerAlert(type) {
            const high = ['payment_initiate', 'pack_selected'];
            if (high.includes(type)) {
                document.getElementById('alert-high').play().catch(() => {});
            } else {
                document.getElementById('alert-low').play().catch(() => {});
            }
        }

        // Start Polling
        fetchLiveStats();
        setInterval(fetchLiveStats, 8000);

    </script>

    <style>
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #0f172a; }
        ::-webkit-scrollbar-thumb { background: #1e293b; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #334155; }
    </style>
</body>
</html>
