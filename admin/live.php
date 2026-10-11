<?php
// admin/live.php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}
require_once 'config/db.php';
include 'includes/header.php';
?>
<style>
    @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;900&display=swap');
    body { font-family: 'Outfit', sans-serif; }
    .live-shell { height: 100vh; height: 100dvh; }
    .live-content { min-width: 0; min-height: 0; container-type: inline-size; }
    .live-header { flex-shrink: 0; position: relative; flex-wrap: wrap; }
    .live-main { min-width: 0; min-height: 0; overflow-x: hidden; }
    .live-grid { display: grid; grid-template-columns: minmax(0, 1fr); }
    .live-grid > * { min-width: 0; grid-column: auto; }
    .live-table { overflow-x: auto; }
    .live-table table { min-width: 600px; }
    .live-event { min-width: 0; overflow-wrap: anywhere; }
    .live-event > :first-child { flex-shrink: 0; }
    .live-event-content { min-width: 0; }
    #visitor-cards-mobile { overflow-wrap: anywhere; }
    @container (min-width: 1100px) {
        .live-grid { grid-template-columns: minmax(0, 1fr) minmax(0, 2fr); }
    }
    @container (max-width: 700px) {
        .live-header { align-items: flex-start; }
        .live-header-controls { width: 100%; flex-wrap: wrap; gap: 12px; }
        .live-table { display: none; }
        #visitor-cards-mobile { display: block; }
        .live-stats { grid-template-columns: minmax(0, 1fr); }
        .live-stats > div { padding: 20px; border-radius: 24px; }
        .live-stats h3 { font-size: 2rem; }
    }
    .glass { background: rgba(15, 23, 42, 0.8); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.05); }
    .neon-pulse { animation: neon-pulse 2s infinite; }
    @keyframes neon-pulse {
        0% { box-shadow: 0 0 5px rgba(34, 197, 94, 0.2); }
        50% { box-shadow: 0 0 20px rgba(34, 197, 94, 0.6); }
        100% { box-shadow: 0 0 5px rgba(34, 197, 94, 0.2); }
    }
    .custom-scroll::-webkit-scrollbar { width: 4px; }
    .custom-scroll::-webkit-scrollbar-track { background: transparent; }
    .custom-scroll::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.1); border-radius: 10px; }
</style>

<body class="bg-[#020617] text-slate-200 custom-scroll">

    <div class="live-shell flex flex-col md:flex-row overflow-hidden">

        <?php include 'includes/nav.php'; ?>

        <div class="live-content flex-1 flex flex-col overflow-hidden bg-slate-950">

            <!-- Tactical Header -->
            <header class="live-header glass z-40 p-4 md:p-6 flex flex-col md:flex-row justify-between items-center gap-4">
                <div class="flex items-center gap-4">
                    <div class="relative">
                        <div class="w-4 h-4 bg-emerald-500 rounded-full animate-ping absolute"></div>
                        <div class="w-4 h-4 bg-emerald-500 rounded-full shadow-[0_0_15px_rgba(16,185,129,0.5)]"></div>
                    </div>
                    <div>
                        <h2 class="text-xl md:text-3xl font-black tracking-tighter text-white uppercase italic">Live <span class="text-emerald-500">Pulse</span></h2>
                        <div class="flex items-center gap-2">
                            <span class="text-[9px] font-black bg-emerald-500/10 text-emerald-400 px-2 py-0.5 rounded uppercase tracking-widest">Active Link</span>
                            <p class="text-[10px] font-medium text-slate-500 uppercase">Monitoring Real-Time</p>
                        </div>
                    </div>
                </div>

                <div class="live-header-controls flex items-center gap-4 w-full md:w-auto justify-between md:justify-end">
                    <div class="bg-slate-900/50 px-4 py-2 rounded-2xl border border-white/5 flex items-center gap-3">
                        <span class="text-[9px] font-black text-slate-500 uppercase">Bots</span>
                        <button id="bot-toggle" class="w-10 h-5 bg-slate-800 rounded-full relative transition-all focus:outline-none">
                            <div class="absolute left-1 top-1 w-3 h-3 bg-white rounded-full transition-all duration-300 shadow-lg" id="bot-toggle-knob"></div>
                        </button>
                    </div>
                    <div class="text-right h-full">
                        <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Revenue Today</p>
                        <p id="today-revenue" class="text-xl md:text-2xl font-black text-white italic tracking-tighter">0 <span class="text-xs text-slate-500">XAF</span></p>
                    </div>
                    <div id="sync-indicator" class="w-10 h-10 rounded-2xl bg-slate-900 flex items-center justify-center border border-white/5 transition-all">
                        <i data-lucide="refresh-cw" class="w-5 h-5 text-emerald-500"></i>
                    </div>
                </div>
            </header>

            <main class="live-main flex-1 overflow-y-auto p-4 md:p-8 space-y-8 custom-scroll pb-24">

                <!-- Main Intelligence Cards -->
                <div class="live-stats grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 md:gap-6">
                    <!-- Active Now -->
                    <div class="glass p-8 rounded-[2.5rem] relative overflow-hidden group">
                        <div class="absolute -right-4 -top-4 text-emerald-500/5 text-8xl font-black group-hover:text-emerald-500/10 transition-all italic">LIVE</div>
                        <div class="relative z-10 flex justify-between items-start">
                            <div>
                                <p class="text-slate-500 text-[10px] font-black uppercase tracking-[0.2em] mb-4">Visiteurs Actifs</p>
                                <div class="flex items-baseline gap-2">
                                    <h3 id="active-now" class="text-6xl font-black text-white italic tracking-tighter">0</h3>
                                    <span class="w-3 h-3 bg-emerald-500 rounded-full neon-pulse"></span>
                                </div>
                            </div>
                            <div class="p-4 bg-emerald-500/10 text-emerald-400 rounded-3xl">
                                <i data-lucide="users" class="w-8 h-8"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Last Activity -->
                    <div class="glass p-8 rounded-[2.5rem] relative overflow-hidden group">
                        <div class="absolute -right-4 -top-4 text-blue-500/5 text-8xl font-black italic">TIME</div>
                        <div class="relative z-10 flex justify-between items-start">
                            <div>
                                <p class="text-slate-500 text-[10px] font-black uppercase tracking-[0.2em] mb-4">Dernier Signal</p>
                                <h3 id="last-update" class="text-4xl font-black text-white italic tracking-tighter">--:--</h3>
                                <p class="text-[10px] text-slate-500 font-bold mt-2 uppercase">Dernière mise à jour</p>
                            </div>
                            <div class="p-4 bg-blue-500/10 text-blue-400 rounded-3xl">
                                <i data-lucide="zap" class="w-8 h-8"></i>
                            </div>
                        </div>
                    </div>

                    <!-- System Health -->
                    <div class="glass p-8 rounded-[2.5rem] relative overflow-hidden group hidden lg:flex">
                        <div class="absolute -right-4 -top-4 text-emerald-500/5 text-8xl font-black italic">SAFE</div>
                        <div class="relative z-10 flex justify-between items-start w-full">
                            <div>
                                <p class="text-slate-500 text-[10px] font-black uppercase tracking-[0.2em] mb-4">Status Système</p>
                                <h3 class="text-4xl font-black text-emerald-400 italic tracking-tighter uppercase" id="system-status-text">Optimal</h3>
                                <p class="text-[10px] text-slate-500 font-bold mt-2 uppercase" id="last-db-sync">Last Record: --:--</p>
                            </div>
                            <div class="p-4 bg-emerald-500/10 text-emerald-400 rounded-3xl">
                                <i data-lucide="shield-check" class="w-8 h-8"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="live-grid gap-6">

                    <!-- Adrenaline Feed (High Intent) -->
                    <div class="lg:col-span-4 space-y-6">
                        <div class="flex justify-between items-center px-2">
                            <h4 class="text-xs font-black text-slate-500 uppercase tracking-widest flex items-center gap-2 italic">
                                <span class="w-2 h-2 bg-orange-500 rounded-full"></span> Adrénaline Marketing
                            </h4>
                            <span class="text-[9px] text-slate-600 font-bold">LIVE FEED</span>
                        </div>

                        <div id="high-intent-feed" class="space-y-4 max-h-[600px] overflow-y-auto no-scrollbar pr-2">
                            <!-- Events will be injected here -->
                            <div class="glass p-8 text-center text-slate-600 rounded-[2.5rem] border-dashed border-white/10">
                                <div class="w-12 h-12 bg-slate-900 rounded-2xl mx-auto mb-4 flex items-center justify-center animate-pulse">
                                    <i data-lucide="radio" class="w-6 h-6"></i>
                                </div>
                                <p class="text-xs font-bold uppercase tracking-widest">En attente de signaux...</p>
                            </div>
                        </div>
                    </div>

                    <!-- Radar Board (Visitors) -->
                    <div class="lg:col-span-8 space-y-6">
                        <div class="flex justify-between items-center px-2">
                            <h4 class="text-xs font-black text-slate-500 uppercase tracking-widest flex items-center gap-2 italic">
                                <span class="w-2 h-2 bg-blue-500 rounded-full"></span> Radar de Navigation
                            </h4>
                            <span id="visitor-count-badge" class="px-3 py-1 bg-slate-900 text-blue-400 rounded-full text-[10px] font-black border border-blue-500/20">0 Connectés</span>
                        </div>

                        <!-- Desktop View (Table) -->
                        <div class="live-table hidden md:block glass rounded-[2.5rem]">
                            <table class="w-full text-left">
                                <thead class="bg-white/5 text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]">
                                    <tr>
                                        <th class="p-6">Visiteur / IP</th>
                                        <th class="p-6">Location</th>
                                        <th class="p-6">Page Active</th>
                                        <th class="p-6 text-right">Activité</th>
                                    </tr>
                                </thead>
                                <tbody id="visitor-table-body" class="divide-y divide-white/5">
                                    <!-- Rows will be injected -->
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile View (Cards) -->
                        <div id="visitor-cards-mobile" class="md:hidden space-y-4">
                            <!-- Cards will be injected -->
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <!-- Global Audio -->
    <audio id="alert-high" src="https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3" preload="auto"></audio>
    <audio id="alert-low" src="https://assets.mixkit.co/active_storage/sfx/2358/2358-preview.mp3" preload="auto"></audio>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        window.lucide?.createIcons();

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
                toggle.classList.add('bg-blue-600');
                toggle.classList.remove('bg-slate-800');
                knob.style.transform = 'translateX(20px)';
            } else {
                toggle.classList.add('bg-slate-800');
                toggle.classList.remove('bg-blue-600');
                knob.style.transform = 'translateX(0)';
            }
        }

        async function fetchLiveStats() {
            const syncIcon = document.getElementById('sync-indicator');
            syncIcon.classList.add('animate-spin');

            try {
                const response = await fetch(`api/get_live_stats.php?hide_bots=${hideBots ? 1 : 0}`, {cache: 'no-store'});
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                const data = await response.json();

                if (data.error || data.success === false) {
                    document.getElementById('active-now').textContent = "ERR";
                    document.getElementById('active-now').classList.add('text-red-500');
                    throw new Error(data.error || 'Sync unavailable');
                }

                document.getElementById('active-now').classList.remove('text-red-500');
                // ... rest of the sync indicators

                // 1. Update Core Stats
                document.getElementById('active-now').textContent = data.active_now;
                document.getElementById('last-update').textContent = data.timestamp.split(' ')[1].slice(0, 5);
                document.getElementById('last-db-sync').textContent = `Last Record: ${data.last_db_visit ? data.last_db_visit.split(' ')[1].slice(0, 5) : 'Aucune visite'}`;
                document.getElementById('today-revenue').innerHTML = `${new Intl.NumberFormat('fr-FR').format(data.today_revenue)} <span class="text-xs text-slate-500">XAF</span>`;
                document.getElementById('visitor-count-badge').textContent = `${data.visitors.length} ${hideBots ? 'Humains' : 'Actifs'}`;

                document.getElementById('system-status-text').textContent = 'Optimal';
                const serverNow = data.server_now_ms;

                // 2. Update Desktop Table & Mobile Cards
                const visitorBody = document.getElementById('visitor-table-body');
                const visitorCardsMobile = document.getElementById('visitor-cards-mobile');
                visitorBody.innerHTML = '';
                visitorCardsMobile.innerHTML = '';

                data.visitors.forEach(visitor => {
                    const v = {...visitor};
                    for (const key of ['ip_address', 'browser', 'country', 'page_url']) v[key] = escapeHTML(v[key] || '');
                    const ago = calculateTimeAgo(v.updated_at, serverNow);

                    // Desktop Row
                    const row = document.createElement('tr');
                    row.className = 'hover:bg-white/5 transition-all group';
                    row.innerHTML = `
                        <td class="p-6">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-2xl bg-slate-900 border border-white/5 flex items-center justify-center font-black text-slate-500 group-hover:text-emerald-400 group-hover:border-emerald-500/30 transition-all">
                                    ${v.ip_address.split('.').slice(-1)}
                                </div>
                                <div>
                                    <p class="text-sm font-black text-white tracking-tight">${v.ip_address}</p>
                                    <p class="text-[9px] text-slate-500 font-black uppercase">${v.browser || 'Browser'}</p>
                                </div>
                            </div>
                        </td>
                        <td class="p-6">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 bg-emerald-500 rounded-full"></span>
                                <span class="text-xs font-black text-slate-300">${v.country || 'Global'}</span>
                            </div>
                        </td>
                        <td class="p-6">
                            <span class="text-[10px] font-black text-blue-400 bg-blue-500/10 px-3 py-1 rounded-lg uppercase truncate max-w-[150px] inline-block border border-blue-500/10">
                                ${v.page_url === '/' ? 'Home' : v.page_url}
                            </span>
                        </td>
                        <td class="p-6 text-right">
                            <span class="text-[10px] font-black text-slate-500 italic">${ago}</span>
                        </td>
                    `;
                    visitorBody.appendChild(row);

                    // Mobile Card
                    const card = document.createElement('div');
                    card.className = 'glass p-5 rounded-3xl space-y-4';
                    card.innerHTML = `
                        <div class="flex justify-between items-start">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-slate-900 rounded-xl flex items-center justify-center font-black text-xs text-slate-500">${v.ip_address.split('.').slice(-1)}</div>
                                <div>
                                    <div class="text-sm font-black text-white">${v.ip_address}</div>
                                    <div class="text-[9px] text-slate-500 font-black uppercase">${v.browser || 'Unknown'}</div>
                                </div>
                            </div>
                            <span class="text-[9px] font-black text-slate-500">${ago}</span>
                        </div>
                        <div class="flex flex-wrap gap-2 justify-between items-center pt-2 border-t border-white/5">
                            <div class="text-[10px] font-bold text-slate-400 uppercase">${v.country || 'Global'}</div>
                            <div class="text-[9px] font-black text-blue-400 uppercase tracking-widest">${v.page_url === '/' ? 'HOME' : v.page_url}</div>
                        </div>
                    `;
                    visitorCardsMobile.appendChild(card);
                });

                // 3. Update Intelligence Feed
                const feed = document.getElementById('high-intent-feed');
                if (!data.high_intent.length) feed.innerHTML = '<p class="text-slate-500 p-6">En attente de signaux...</p>';
                if (data.high_intent.length > 0) {
                    const newestEvent = data.high_intent[0];
                    if (lastEventId && newestEvent.id !== lastEventId) {
                        triggerAlert(newestEvent.event_name);
                    }
                    lastEventId = newestEvent.id;

                    feed.innerHTML = '';
                    data.high_intent.forEach(e => {
                        const card = document.createElement('div');
                        let color = 'blue';
                        let icon = 'activity';
                        let label = 'Signal';

                        if(e.event_name === 'payment_initiate') { color = 'emerald'; icon = 'credit-card'; label = 'Paiement'; }
                        if(e.event_name === 'pack_selected') { color = 'orange'; icon = 'shopping-cart'; label = 'Intérêt'; }
                        if(e.event_name === 'click_whatsapp_float') { color = 'blue'; icon = 'message-circle'; label = 'Support'; }

                        card.className = `live-event glass p-4 sm:p-6 rounded-[2.5rem] border-l-4 border-${color}-500/50 flex gap-4 transition-all`;
                        card.innerHTML = `
                            <div class="p-3 bg-${color}-500/10 text-${color}-400 rounded-2xl h-fit">
                                <i data-lucide="${icon}" class="w-5 h-5"></i>
                            </div>
                            <div class="live-event-content flex-1">
                                <div class="flex flex-wrap gap-2 justify-between items-center mb-2">
                                    <span class="text-[9px] font-black text-${color}-400 uppercase tracking-[0.2em]">${label}</span>
                                    <span class="text-[9px] font-black text-slate-600 uppercase italic">${calculateTimeAgo(e.created_at, serverNow)}</span>
                                </div>
                                <p class="text-[11px] font-black text-white tracking-tight leading-tight">${formatEventData(e.event_data)}</p>
                                <div class="flex items-center gap-2 mt-3">
                                    <span class="text-[8px] font-black text-slate-500 uppercase">${escapeHTML(e.country || 'Global')}</span>
                                    <span class="w-1 h-1 bg-slate-700 rounded-full"></span>
                                    <span class="text-[8px] font-black text-slate-500 uppercase">${escapeHTML(e.ip_address)}</span>
                                </div>
                            </div>
                        `;
                        feed.appendChild(card);
                    });
                    window.lucide?.createIcons();
                }

            } catch (err) {
                document.getElementById('system-status-text').textContent = 'Hors ligne';
                document.getElementById('active-now').textContent = 'ERR';
                console.error('Pulse sync failure:', err);
            } finally {
                setTimeout(() => syncIcon.classList.remove('animate-spin'), 800);
            }
        }

        function calculateTimeAgo(timestamp, serverNowMs) {
            const date = new Date(timestamp.replace(' ', 'T') + '+01:00');
            const now = new Date(serverNowMs);
            const diff = Math.floor((now - date) / 1000);
            if (diff <= 2) return 'LIVE';
            if (diff < 60) return `${diff}s`;
            if (diff < 3600) return `${Math.floor(diff / 60)}m`;
            return `${Math.floor(diff / 3600)}h`;
        }

        function escapeHTML(value) {
            const element = document.createElement('span');
            element.textContent = String(value ?? '');
            return element.innerHTML;
        }

        function formatEventData(data) {
            try {
                const parsed = JSON.parse(data);
                if (parsed.package) {
                    const amount = Number(parsed.amount);
                    const total = Number.isFinite(amount) ? ` · ${escapeHTML(amount.toLocaleString('fr-FR'))} XAF` : '';
                    return `Paiement : <span class="text-emerald-400">${escapeHTML(parsed.package)}</span>${total}`;
                }
                if (parsed.pack) return `Pack selectionné : <span class="text-emerald-400">${escapeHTML(String(parsed.pack).toUpperCase())}</span>`;
                if (parsed.country) return `Acheteur potentiel : <span class="text-blue-400">${escapeHTML(parsed.country)}</span>`;
                return escapeHTML(data);
            } catch { return escapeHTML(data); }
        }

        function triggerAlert(type) {
            const critical = ['payment_initiate', 'pack_selected'];
            if (critical.includes(type)) {
                document.getElementById('alert-high').play().catch(() => {});
            } else {
                document.getElementById('alert-low').play().catch(() => {});
            }
        }

        // Initialize Command Center
        fetchLiveStats();
        setInterval(fetchLiveStats, 8000);

    </script>
</body>
</html>
