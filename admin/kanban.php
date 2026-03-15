<?php
require_once 'auth.php';
require_once 'config/db.php';

$statuses = [
    'pending' => ['label' => 'Nouveau Panier', 'color' => 'bg-orange-50 text-orange-600 border-orange-100'],
    'contacted' => ['label' => 'Contacté', 'color' => 'bg-blue-50 text-blue-600 border-blue-100'],
    'negotiating' => ['label' => 'En Négociation', 'color' => 'bg-purple-50 text-purple-600 border-purple-100'],
    'converted' => ['label' => 'Payé / Converti', 'color' => 'bg-green-50 text-green-600 border-green-100'],
    'abandoned' => ['label' => 'Abandonné', 'color' => 'bg-gray-50 text-gray-500 border-gray-100']
];

$prospectsByStatus = [];
foreach ($statuses as $status => $info) {
    $stmt = $pdo->prepare("SELECT * FROM prospects WHERE status = ? ORDER BY position ASC, updated_at DESC");
    $stmt->execute([$status]);
    $prospectsByStatus[$status] = $stmt->fetchAll();
}

include 'includes/header.php';
?>
<script src="https://unpkg.com/lucide@latest"></script>

<body class="bg-[#f8fafc] font-sans text-slate-900 overflow-hidden">
    <div class="flex h-screen">
        <?php include 'includes/nav.php'; ?>

        <div class="flex-1 flex flex-col min-w-0">
            <header class="bg-white/80 backdrop-blur-md border-b border-slate-200 p-6 flex justify-between items-center z-10">
                <div>
                    <h2 class="text-2xl font-black tracking-tight text-slate-900">CRM Kanban Board 📋</h2>
                    <p class="text-sm font-medium text-slate-500">Gérez vos prospects par glisser-déposer.</p>
                </div>
                <div class="flex items-center gap-4">
                    <a href="prospects.php" class="text-xs font-bold text-blue-600 hover:underline flex items-center gap-1">
                        <i data-lucide="list" class="w-3 h-3"></i> Vue Liste
                    </a>
                </div>
            </header>

            <main class="flex-1 overflow-x-auto p-4 md:p-8 custom-scrollbar bg-[#f8fafc] scroll-smooth snap-x snap-mandatory" id="kanban-container">
                <div class="flex h-full gap-4 md:gap-6 min-w-max pb-4">
                    <?php foreach ($statuses as $status => $info): ?>
                        <div class="w-[85vw] md:w-80 flex flex-col h-full rounded-2xl bg-slate-100/50 border border-slate-200/60 p-4 kanban-column snap-center" 
                             data-status="<?= $status ?>" id="col-<?= $status ?>">
                            <div class="flex justify-between items-center mb-6 px-1">
                                <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest <?= $info['color'] ?> border">
                                    <?= $info['label'] ?>
                                </span>
                                <span class="text-[10px] font-black text-slate-400 bg-white border border-slate-200 px-2 py-0.5 rounded-lg shadow-sm">
                                    <?= count($prospectsByStatus[$status]) ?>
                                </span>
                            </div>

                            <div class="flex-1 overflow-y-auto space-y-4 pr-1 custom-scrollbar-thin kanban-list min-h-[50px]" id="<?= $status ?>-list">
                                <?php foreach ($prospectsByStatus[$status] as $prospect): ?>
                                    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm hover:shadow-md hover:border-blue-400 active:scale-95 transition-all cursor-grab group kanban-item relative overflow-hidden" 
                                         data-id="<?= $prospect['id'] ?>">
                                        <!-- Priority Indicator -->
                                        <div class="absolute top-0 right-0 w-1 h-full <?= $prospect['priority'] == 'high' ? 'bg-red-500' : ($prospect['priority'] == 'medium' ? 'bg-orange-500' : 'bg-slate-300') ?>"></div>
                                        
                                        <div class="flex justify-between items-start mb-2">
                                            <p class="text-sm font-black text-slate-800 line-clamp-1 group-hover:text-blue-600 transition-colors"><?= htmlspecialchars($prospect['name']) ?></p>
                                        </div>
                                        <p class="text-[10px] font-medium text-slate-400 mb-4 flex items-center gap-1">
                                            <i data-lucide="phone" class="w-3 h-3"></i> <?= htmlspecialchars($prospect['whatsapp']) ?>
                                        </p>
                                        
                                        <div class="bg-slate-50 rounded-xl p-3 border border-slate-100 mb-4 group-hover:bg-blue-50/50 transition-colors">
                                            <div class="flex justify-between items-center mb-1">
                                                <span class="text-[10px] font-bold text-slate-500 uppercase"><?= htmlspecialchars($prospect['package_name']) ?></span>
                                                <span class="text-[10px] font-black text-slate-900"><?= number_format($prospect['total_amount'], 0, ',', ' ') ?></span>
                                            </div>
                                            <div class="w-full h-1 bg-slate-200 rounded-full overflow-hidden">
                                                <div class="h-full bg-blue-500 w-1/3"></div>
                                            </div>
                                        </div>

                                        <div class="flex justify-between items-center">
                                            <div class="flex -space-x-2">
                                                <div class="w-6 h-6 rounded-full bg-indigo-500 border-2 border-white flex items-center justify-center text-[10px] text-white font-black"><?= substr($prospect['name'], 0, 1) ?></div>
                                            </div>
                                            <span class="text-[9px] font-black text-slate-300 uppercase italic">
                                                <?= date('d M', strtotime($prospect['updated_at'])) ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- Drag & Drop Logic -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script>
        document.querySelectorAll('.kanban-list').forEach(list => {
            new Sortable(list, {
                group: 'kanban',
                animation: 150,
                ghostClass: 'bg-blue-50',
                dragClass: 'opacity-50',
                onEnd: function (evt) {
                    const id = evt.item.dataset.id;
                    const newStatus = evt.to.parentElement.dataset.status;
                    
                    // Simple AJAX for status update
                    fetch('api/update_prospect_status.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `id=${id}&status=${newStatus}`
                    })
                    .then(r => r.json())
                    .then(data => {
                         if(data.success) {
                             console.log('Status updated to ' + newStatus);
                             // Optional: Trigger a sound or notification
                         }
                    });
                }
            });
        });
        
        lucide.createIcons();
    </script>
    
    <style>
        .custom-scrollbar::-webkit-scrollbar { height: 8px; width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 20px; }
        
        .custom-scrollbar-thin::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar-thin::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar-thin::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 20px; }
        
        .kanban-column { min-width: 85vw; max-width: 85vw; }
        @media (min-width: 768px) {
            .kanban-column { min-width: 320px; max-width: 320px; }
        }
        .kanban-item { cursor: grab; transition: transform 0.2s, box-shadow 0.2s; }
        .kanban-item:active { cursor: grabbing; transform: scale(0.98); }

        /* Mobile Nav Dots */
        .nav-dot { width: 8px; height: 8px; border-radius: 50%; background: #cbd5e1; transition: all 0.3s; }
        .nav-dot.active { width: 24px; background: #3b82f6; }
    </style>

    <!-- Mobile Navigation Overlay -->
    <div class="md:hidden fixed bottom-24 left-1/2 -translate-x-1/2 flex gap-2 p-2 bg-white/80 backdrop-blur-md rounded-full shadow-lg border border-slate-200 z-20">
        <?php foreach($statuses as $status => $info): ?>
            <button onclick="document.getElementById('col-<?= $status ?>').scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' })" 
                    class="nav-dot" id="dot-<?= $status ?>"></button>
        <?php endforeach; ?>
    </div>

    <script>
        const container = document.getElementById('kanban-container');
        const dots = <?php 
            $dotMap = [];
            foreach($statuses as $status => $info) {
                $dotMap["col-$status"] = "dot-$status";
            }
            echo json_encode($dotMap, JSON_PRETTY_PRINT);
        ?>;

        container.addEventListener('scroll', () => {
            const scrollLeft = container.scrollLeft;
            const width = container.offsetWidth;
            
            Object.keys(dots).forEach(id => {
                const el = document.getElementById(id);
                const dot = document.getElementById(dots[id]);
                const offset = el.offsetLeft - container.offsetLeft;
                
                if (Math.abs(offset - scrollLeft) < width / 2) {
                    dot.classList.add('active');
                } else {
                    dot.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>
