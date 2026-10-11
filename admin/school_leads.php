<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}
require_once 'config/db.php';

// Export CSV Logic
if (isset($_GET['export'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=school_leads_export_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Ecole', 'Ville', 'Contact', 'Email', 'Phone', 'Effectif', 'Statut', 'Notes', 'Date']);
    
    $stmt = $pdo->query("SELECT * FROM school_leads ORDER BY created_at DESC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $row['id'], $row['school_name'], $row['city'], $row['contact_name'], 
            $row['email'], $row['phone'], $row['students_count'], $row['status'], 
            $row['notes'], $row['created_at']
        ]);
    }
    fclose($output);
    exit;
}

// Delete Lead
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $pdo->prepare("DELETE FROM school_leads WHERE id = ?")->execute([$id]);
    header('Location: school_leads.php');
    exit;
}

// Fetch Params
$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';

// Base Query
$query = "SELECT * FROM school_leads WHERE 1=1";
$params = [];
if (!empty($search)) {
    $query .= " AND (school_name LIKE ? OR city LIKE ? OR contact_name LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
}
if (!empty($statusFilter)) {
    $query .= " AND status = ?";
    $params[] = $statusFilter;
}
$query .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$leads = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Stats Aggregation
$stats = ['new' => 0, 'contacted' => 0, 'negotiating' => 0, 'won' => 0, 'lost' => 0, 'abandoned' => 0];
$pipelineRev = 0;
$totalStudents = 0;
foreach ($leads as $l) {
    if (isset($stats[$l['status']])) $stats[$l['status']]++;
    $pipelineRev += ($l['students_count'] * 1000);
    $totalStudents += $l['students_count'];
}

$statusColors = [
    'new' => 'bg-blue-500/10 text-blue-600 border-blue-500/20',
    'contacted' => 'bg-amber-500/10 text-amber-600 border-amber-500/20',
    'negotiating' => 'bg-indigo-500/10 text-indigo-600 border-indigo-500/20',
    'won' => 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20',
    'lost' => 'bg-rose-500/10 text-rose-600 border-rose-500/20',
    'abandoned' => 'bg-slate-500/10 text-slate-500 border-slate-500/20'
];

$statusLabels = [
    'new' => 'Nouveau',
    'contacted' => 'Contacté',
    'negotiating' => 'Négociation',
    'won' => 'Gagné',
    'lost' => 'Perdu',
    'abandoned' => 'Archivé'
];
?>
<?php include 'includes/header.php'; ?>
<body class="bg-[#fcfdfe] font-sans antialiased text-slate-900 scroll-smooth">
    <!-- Lucide & Chart.js -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <div class="flex h-screen overflow-hidden">
        <?php include 'includes/nav.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-10 custom-scrollbar">
            
            <!-- Enterprise Header -->
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-8 pb-10 border-b border-slate-100">
                <div class="space-y-2">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-blue-200">
                            <i data-lucide="building-2" class="w-6 h-6"></i>
                        </div>
                        <h1 class="text-2xl font-black text-slate-900 tracking-tight">CRM Établissements</h1>
                    </div>
                    <p class="text-slate-400 font-medium text-xs ml-13">Console de pilotage et insights stratégiques des partenariats.</p>
                </div>
                
                <div class="flex items-center gap-4">
                    <a href="?export=1" class="flex items-center gap-2 bg-white border border-slate-200 px-5 py-3 rounded-2xl text-sm font-bold text-slate-600 hover:bg-slate-50 transition-all shadow-sm">
                        <i data-lucide="download" class="w-4 h-4"></i> Exporter CSV
                    </a>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mt-10">
                <div class="bg-white p-6 rounded-[2rem] border border-slate-100 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">
                    <div class="absolute -right-4 -bottom-4 opacity-5 text-blue-600 group-hover:scale-110 transition-transform">
                        <i data-lucide="trending-up" class="w-16 h-16"></i>
                    </div>
                    <p class="text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1">Pipeline Total</p>
                    <p class="text-xl font-black text-slate-900 leading-none mb-2"><?= number_format($pipelineRev / 1000, 1) ?>M <span class="text-[10px] text-slate-300 font-bold ml-1">XAF</span></p>
                    <div class="flex items-center gap-2">
                         <span class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse"></span>
                         <p class="text-[9px] font-bold text-blue-500 uppercase tracking-tighter italic">Potentiel brut</p>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-[2rem] border border-slate-100 shadow-sm hover:shadow-md transition-shadow">
                    <p class="text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1">Leads Actifs</p>
                    <p class="text-xl font-black text-slate-900 leading-none mb-2"><?= $stats['new'] + $stats['contacted'] + $stats['negotiating'] ?></p>
                    <p class="text-[9px] font-bold text-amber-500 uppercase tracking-tighter">Attente d'action</p>
                </div>

                <div class="bg-white p-6 rounded-[2rem] border border-slate-100 shadow-sm hover:shadow-md transition-shadow">
                    <p class="text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1">Partenaires Won</p>
                    <p class="text-xl font-black text-emerald-600 leading-none mb-2"><?= $stats['won'] ?></p>
                    <p class="text-[9px] font-bold text-emerald-500 uppercase tracking-tighter"><?= round(($stats['won'] / max(1, count($leads))) * 100) ?>% Taux conversion</p>
                </div>

                <div class="bg-white p-6 rounded-[2rem] border border-slate-100 shadow-sm hover:shadow-md transition-shadow">
                    <p class="text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1">Total Étudiants</p>
                    <p class="text-xl font-black text-slate-900 leading-none mb-2"><?= number_format($totalStudents) ?></p>
                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-tighter italic">Écosystème total</p>
                </div>
            </div>

            <!-- Main Content Area -->
            <div class="mt-12 space-y-8">
                
                <!-- Filters Bar -->
                <div class="bg-slate-900 p-6 rounded-[2.5rem] shadow-2xl flex flex-col xl:flex-row gap-6 items-center">
                    <form action="" method="GET" class="flex-1 flex flex-col md:flex-row gap-4 w-full">
                        <div class="relative flex-1">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500">
                                <i data-lucide="search" class="w-5 h-5"></i>
                            </span>
                            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Rechercher une école ou un contact..." 
                                   class="w-full pl-12 pr-4 py-4 rounded-2xl bg-slate-800 border-transparent text-white placeholder-slate-500 focus:ring-4 focus:ring-blue-500/20 focus:bg-slate-800/80 transition-all font-medium border border-slate-700/50">
                        </div>
                        <select name="status" class="py-4 px-6 rounded-2xl bg-slate-800 border-slate-700 text-white font-bold focus:ring-4 focus:ring-blue-500/20 cursor-pointer min-w-[200px]">
                            <option value="">Tous les statuts</option>
                            <?php foreach($statusLabels as $val => $label): ?>
                                <option value="<?= $val ?>" <?= $statusFilter === $val ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="bg-blue-600 text-white px-10 py-4 rounded-2xl font-black tracking-widest hover:bg-blue-700 transition-all shadow-xl shadow-blue-500/20 active:scale-95">
                            ANALYSER
                        </button>
                        <?php if(!empty($search) || !empty($statusFilter)): ?>
                            <a href="school_leads.php" class="bg-slate-800 text-slate-400 p-4 rounded-2xl hover:bg-slate-700 transition-all">
                                <i data-lucide="x-circle" class="w-6 h-6"></i>
                            </a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Leads Feed -->
                <div class="grid grid-cols-1 gap-6 pb-20">
                    <?php if(empty($leads)): ?>
                        <div class="py-32 bg-white rounded-[3rem] text-center border-2 border-dashed border-slate-100">
                            <i data-lucide="database-zap" class="w-16 h-16 text-slate-100 mx-auto mb-4"></i>
                            <p class="text-slate-400 font-bold uppercase tracking-widest text-sm">Zéro données disponibles</p>
                        </div>
                    <?php endif; ?>

                    <?php foreach($leads as $l): 
                        $waLink = "https://wa.me/" . preg_replace('/[^0-9]/', '', $l['phone']) . "?text=" . urlencode("Bonjour {$l['contact_name']}, je reviens vers vous concernant l'offre école HireMe...");
                        $emailLink = "mailto:{$l['email']}";
                        $isHot = (strtotime($l['updated_at'] ?? $l['created_at']) > strtotime('-3 days'));
                        $isOld = (strtotime($l['updated_at'] ?? $l['created_at']) < strtotime('-7 days') && !in_array($l['status'], ['won', 'lost', 'abandoned']));
                    ?>
                    <div class="bg-white p-8 rounded-[3rem] shadow-sm hover:shadow-2xl transition-all border border-slate-100 flex flex-col lg:flex-row gap-8 items-start lg:items-center relative group min-h-[160px]">
                        <?php if($isOld): ?>
                            <div class="absolute -left-1 top-10 bottom-10 w-2 bg-rose-500 rounded-full shadow-[0_0_10px_rgba(244,63,94,0.5)] animate-pulse"></div>
                        <?php endif; ?>

                        <!-- Info Column -->
                        <div class="flex-1 space-y-2 min-w-[300px]">
                            <div class="flex items-center gap-3">
                                <h3 class="text-lg font-black text-slate-900 tracking-tight leading-none group-hover:text-blue-600 transition-colors"><?= htmlspecialchars($l['school_name']) ?></h3>
                                <?php if($isHot): ?>
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.5)]"></span>
                                <?php endif; ?>
                                <span class="bg-slate-100 text-slate-500 text-[9px] font-black px-2 py-0.5 rounded-full uppercase tracking-widest border border-slate-200"><?= htmlspecialchars($l['package'] ?? 'Standard') ?></span>
                            </div>
                            <div class="flex flex-wrap items-center gap-y-2 gap-x-6 text-[10px] text-slate-400 font-bold uppercase tracking-tighter">
                                <span class="flex items-center gap-1.5"><i data-lucide="map-pin" class="w-2.5 h-2.5 text-rose-500"></i> <?= htmlspecialchars($l['city']) ?></span>
                                <span class="flex items-center gap-1.5"><i data-lucide="user" class="w-2.5 h-2.5 text-blue-500"></i> <?= htmlspecialchars($l['contact_name']) ?></span>
                                <span class="flex items-center gap-1.5 italic text-slate-300 font-medium tracking-tight">Posté le <?= date('d.m.Y', strtotime($l['created_at'])) ?></span>
                            </div>
                        </div>

                        <!-- Impact Column -->
                        <div class="w-full lg:w-40 bg-slate-50 p-4 rounded-[2rem] border border-slate-100 flex flex-col items-center justify-center group-hover:bg-blue-50/30 transition-colors">
                            <p class="text-[8px] font-black uppercase text-slate-400 mb-0.5 leading-none tracking-widest text-center">Audience</p>
                            <div class="flex items-baseline gap-0.5">
                                <p class="text-lg font-black text-slate-900"><?= number_format($l['students_count']) ?></p>
                                <span class="text-[9px] font-bold text-slate-300 italic">élèves</span>
                            </div>
                        </div>

                        <!-- Status Action -->
                        <div class="w-full lg:w-48">
                            <select onchange="updateLeadStatus(<?= $l['id'] ?>, this.value)" 
                                    class="w-full appearance-none px-6 py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-sm border focus:ring-8 focus:ring-blue-100 cursor-pointer transition-all <?= $statusColors[$l['status']] ?>">
                                <?php foreach($statusLabels as $val => $label): ?>
                                    <option value="<?= $val ?>" <?= $l['status'] === $val ? 'selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-3 w-full lg:w-auto justify-end">
                            <button onclick="openActivityTimeline(<?= $l['id'] ?>, `<?= htmlspecialchars($l['school_name']) ?>`)" 
                                    class="p-4 bg-slate-50 text-slate-400 rounded-2xl hover:bg-slate-900 hover:text-white transition-all shadow-sm" title="Timeline d'activité">
                                <i data-lucide="history" class="w-6 h-6"></i>
                            </button>
                            <a href="<?= $waLink ?>" target="_blank" class="h-14 w-14 bg-emerald-500 text-white rounded-2xl flex items-center justify-center shadow-xl shadow-emerald-200 hover:scale-105 active:scale-95 transition-all">
                                <i data-lucide="message-circle" class="w-7 h-7"></i>
                            </a>
                            <a href="<?= $emailLink ?>" class="h-14 w-14 bg-blue-600 text-white rounded-2xl flex items-center justify-center shadow-xl shadow-blue-200 hover:scale-105 active:scale-95 transition-all">
                                <i data-lucide="mail" class="w-6 h-6"></i>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Activity Timeline Modal -->
    <div id="timelineModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-md z-[100] hidden flex items-center justify-end">
        <div class="bg-white h-full w-full max-w-2xl shadow-[-20px_0_50px_rgba(0,0,0,0.1)] transition-transform duration-500 translate-x-full overflow-hidden flex flex-col" id="timelinePanel">
            
            <!-- Modal Header -->
            <div class="bg-slate-900 p-6 text-white relative">
                 <button onclick="closeTimeline()" class="absolute right-6 top-6 w-10 h-10 bg-white/10 hover:bg-white/20 rounded-2xl flex items-center justify-center transition-all">
                     <i data-lucide="x" class="w-5 h-5"></i>
                 </button>
                 <div class="flex items-center gap-3 mb-1">
                     <h3 class="text-xl font-black italic tracking-tight" id="timelineLeadName">ÉCOLE NOM</h3>
                     <span class="px-2 py-0.5 bg-blue-600 rounded-lg text-[9px] font-black uppercase tracking-widest shadow-lg shadow-blue-500/30">Dossier #<span id="timelineLeadId">0</span></span>
                 </div>
                 <p class="text-slate-400 text-xs font-medium">Historique complet des interactions et changements de statut.</p>
            </div>

            <!-- Timeline Content -->
            <div class="flex-1 overflow-y-auto p-10 space-y-12 custom-scrollbar bg-slate-50/50" id="activityFeed">
                <!-- Data injected via JS -->
            </div>

            <!-- Add Note Form -->
            <div class="p-8 bg-white border-t border-slate-100 shadow-[0_-20px_40px_rgba(0,0,0,0.02)]">
                <div class="relative group">
                    <textarea id="newNoteContent" rows="3" class="w-full rounded-3xl border-slate-200 bg-slate-50 p-6 text-slate-700 font-medium focus:ring-8 focus:ring-blue-500/10 focus:bg-white transition-all text-sm outline-none resize-none" placeholder="Rédigez une action ou un compte-rendu..."></textarea>
                    <button onclick="saveNewNote()" class="absolute bottom-4 right-4 bg-blue-600 text-white w-14 h-14 rounded-2xl flex items-center justify-center hover:bg-slate-900 transition-all shadow-xl shadow-blue-200 active:scale-95 group-hover:scale-105">
                         <i data-lucide="send" class="w-6 h-6"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();

        // --- DASHBOARD ACTIONS ---
        async function updateLeadStatus(id, newStatus) {
            try {
                const response = await fetch('api/update_school_lead.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id, status: newStatus })
                });
                const result = await response.json();
                if (result.success) location.reload();
                else alert(result.message);
            } catch (e) { alert('Erreur réseau'); }
        }

        async function openActivityTimeline(id, name) {
            document.getElementById('timelineLeadName').innerText = name.toUpperCase();
            document.getElementById('timelineLeadId').innerText = id;
            
            const modal = document.getElementById('timelineModal');
            const panel = document.getElementById('timelinePanel');
            const feed = document.getElementById('activityFeed');
            
            feed.innerHTML = '<div class="py-20 text-center"><i data-lucide="loader" class="w-8 h-8 animate-spin mx-auto text-blue-500"></i></div>';
            lucide.createIcons();

            modal.classList.remove('hidden');
            setTimeout(() => panel.classList.remove('translate-x-full'), 10);

            try {
                const response = await fetch(`api/get_school_lead_activities.php?lead_id=${id}`);
                const result = await response.json();
                
                if (result.success && result.activities.length > 0) {
                    feed.innerHTML = result.activities.map(act => `
                        <div class="relative pl-10 border-l-2 border-slate-100 pb-2 last:pb-0">
                            <div class="absolute -left-[11px] top-0 w-5 h-5 rounded-full border-4 border-white ${act.type === 'note' ? 'bg-blue-500 shadow-[0_0_10px_rgba(59,130,246,0.3)]' : 'bg-slate-300'}"></div>
                            <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100">
                                <div class="flex justify-between items-center mb-3">
                                    <span class="text-[10px] font-black uppercase tracking-widest ${act.type === 'note' ? 'text-blue-500' : 'text-slate-400'}">${act.type === 'note' ? 'Action / Note' : 'Transition Système'}</span>
                                    <span class="text-[9px] font-bold text-slate-300 italic">${new Date(act.created_at).toLocaleString()}</span>
                                </div>
                                <p class="text-sm font-medium text-slate-700 leading-relaxed">${act.content.replace(/\n/g, '<br>')}</p>
                            </div>
                        </div>
                    `).join('');
                } else {
                    feed.innerHTML = '<div class="py-20 text-center"><p class="text-slate-300 font-bold uppercase text-xs">Aucune activité enregistrée</p></div>';
                }
            } catch (e) {
                feed.innerHTML = '<div class="py-20 text-center text-rose-500 font-bold">Erreur de chargement</div>';
            }
            lucide.createIcons();
        }

        function closeTimeline() {
            const modal = document.getElementById('timelineModal');
            const panel = document.getElementById('timelinePanel');
            panel.classList.add('translate-x-full');
            setTimeout(() => modal.classList.add('hidden'), 500);
        }

        async function saveNewNote() {
            const id = document.getElementById('timelineLeadId').innerText;
            const content = document.getElementById('newNoteContent').value;
            if(!content.trim()) return;

            try {
                const response = await fetch('api/update_school_lead.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id, notes: content })
                });
                const result = await response.json();
                if (result.success) {
                    document.getElementById('newNoteContent').value = '';
                    openActivityTimeline(id, document.getElementById('timelineLeadName').innerText);
                }
            } catch (e) { alert('Erreur'); }
        }
    </script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');
        body { font-family: 'Inter', sans-serif; }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #f1f5f9; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #e2e8f0; }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    </style>
</body>
</html>
