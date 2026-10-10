<?php
// linkedin/suivi.php
// Order Tracking Page for HireMe LinkedIn Optimization

require_once __DIR__ . '/../admin/config/db.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suivi de Commande — LinkedIn | HireMe</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: { yellow: '#FACC15', dark: '#0F172A' }
                    },
                    fontFamily: { sans: ['Plus Jakarta Sans', 'sans-serif'] }
                }
            }
        }
    </script>
</head>
<body class="bg-slate-950 text-slate-100 font-sans antialiased min-h-screen py-10 px-4">

    <div class="max-w-3xl mx-auto space-y-8">
        
        <!-- Header -->
        <div class="text-center space-y-3">
            <div class="flex items-center justify-center gap-3">
                <a href="../index.html" class="inline-flex items-center gap-2 font-black text-2xl text-white">
                    <span class="w-9 h-9 rounded-xl bg-brand-yellow text-slate-950 flex items-center justify-center text-lg">HM</span>
                    Hire<span class="text-brand-yellow">Me</span>
                </a>
                <a href="../index.html" class="text-[11px] font-bold text-slate-400 hover:text-brand-yellow border border-slate-800 bg-slate-900 px-3 py-1 rounded-full transition-colors">
                    ← MonCvPro.com
                </a>
            </div>
            <h1 class="text-3xl font-black text-white">Suivi de Commande LinkedIn</h1>
            <p class="text-xs text-slate-400">Entrez votre numéro de référence et vos coordonnées pour consulter l'avancement de votre commande.</p>
        </div>

        <!-- Search Form -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 sm:p-8 shadow-2xl space-y-4">
            <form id="trackingSearchForm" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Référence de Commande *</label>
                        <input type="text" name="reference" id="inputRef" required placeholder="Ex: HM-LI-284731" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-brand-yellow uppercase">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Email ou Téléphone *</label>
                        <input type="text" name="contact" id="inputContact" required placeholder="Ex: email@domaine.cm ou 670000000" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-brand-yellow">
                    </div>
                </div>
                
                <button type="submit" id="searchBtn" class="w-full bg-brand-yellow hover:bg-yellow-400 text-slate-950 font-black py-3.5 rounded-xl shadow-lg transition-all">
                    <i class="fa-solid fa-magnifying-glass mr-2"></i> Rechercher ma commande
                </button>
            </form>

            <div id="searchError" class="hidden p-4 rounded-xl bg-red-500/10 border border-red-500/30 text-red-400 text-xs font-semibold"></div>
        </div>

        <!-- Result Container -->
        <div id="resultContainer" class="hidden bg-slate-900 border border-slate-800 rounded-2xl p-6 sm:p-8 space-y-6 shadow-2xl">
            
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center border-b border-slate-800 pb-4 gap-2">
                <div>
                    <span class="text-xs text-slate-400">Commande pour <strong class="text-white" id="resClientName">--</strong></span>
                    <h2 class="text-2xl font-mono font-black text-brand-yellow" id="resReference">HM-LI-XXXXXX</h2>
                </div>
                <div class="text-right">
                    <span class="text-xs font-bold text-slate-400 block" id="resPackage">Pack Pro</span>
                    <span class="text-xs text-slate-500" id="resDate">--</span>
                </div>
            </div>

            <!-- Timeline -->
            <div class="space-y-6">
                <h3 class="text-xs uppercase font-extrabold tracking-wider text-slate-400">Progression de votre commande :</h3>
                
                <div class="space-y-4" id="timelineList">
                    <!-- Timeline items rendered dynamically -->
                </div>
            </div>

            <div class="pt-4 border-t border-slate-800 flex justify-between items-center">
                <a id="resDirectLink" href="#" class="inline-flex items-center gap-2 text-xs font-bold text-brand-yellow hover:underline">
                    <span>Voir la fiche détaillée & téléchargeables</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </a>
                <a href="https://wa.me/237670000000" target="_blank" class="inline-flex items-center gap-2 text-xs font-bold text-emerald-400 hover:underline">
                    <i class="fa-brands fa-whatsapp"></i> Support WhatsApp
                </a>
            </div>

        </div>

    </div>

    <script>
        const statusMap = [
            { code: 'COMMANDE_RECUE', label: 'Commande enregistrée', desc: 'Vos informations et votre CV ont été enregistrés avec succès.' },
            { code: 'PAIEMENT_EN_VERIFICATION', label: 'Paiement en cours de vérification', desc: 'Notre équipe financière contrôle la transaction Mobile Money.' },
            { code: 'PAIEMENT_CONFIRME', label: 'Paiement validé', desc: 'Paiement confirmé. Votre dossier est transmis à notre spécialiste LinkedIn.' },
            { code: 'EN_COURS_D_OPTIMISATION', label: 'En cours d\'optimisation', desc: 'Réécriture des expériences, optimisation du titre et analyse des mots-clés.' },
            { code: 'EN_REVISION', label: 'En révision & contrôle qualité', desc: 'Relecture finale et mise en forme des livrables.' },
            { code: 'TERMINE', label: 'Optimisation terminée & livrée', desc: 'Vos livrables complets sont disponibles sur votre espace client.' }
        ];

        document.getElementById('trackingSearchForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const errBox = document.getElementById('searchError');
            const resBox = document.getElementById('resultContainer');
            const btn = document.getElementById('searchBtn');

            errBox.classList.add('hidden');
            resBox.classList.add('hidden');
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> Recherche...';

            const formData = new FormData(this);
            const apiUrl = '<?= (strpos($_SERVER["REQUEST_URI"], "/linkedin/") !== false) ? "../api/linkedin/track_order.php" : "api/linkedin/track_order.php" ?>';

            fetch(apiUrl, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-magnifying-glass mr-2"></i> Rechercher ma commande';

                if (data.success) {
                    const o = data.order;
                    document.getElementById('resClientName').innerText = o.client_name;
                    document.getElementById('resReference').innerText = o.reference;
                    document.getElementById('resPackage').innerText = o.package_name;
                    document.getElementById('resDate').innerText = new Date(o.created_at).toLocaleDateString('fr-FR');
                    document.getElementById('resDirectLink').href = o.view_url;

                    renderTimeline(o.order_status);
                    resBox.classList.remove('hidden');
                } else {
                    errBox.innerText = data.message || 'Aucune commande trouvée.';
                    errBox.classList.remove('hidden');
                }
            })
            .catch(err => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-magnifying-glass mr-2"></i> Rechercher ma commande';
                errBox.innerText = 'Erreur réseau ou serveur. Veuillez réessayer.';
                errBox.classList.remove('hidden');
            });
        });

        function renderTimeline(currentStatus) {
            const container = document.getElementById('timelineList');
            container.innerHTML = '';

            let reachedCurrent = false;

            // Determine status index
            const currentIdx = statusMap.findIndex(s => s.code === currentStatus);

            statusMap.forEach((s, idx) => {
                const isDone = currentIdx !== -1 && idx <= currentIdx;
                const isCurrent = currentIdx === idx;

                const item = document.createElement('div');
                item.className = `p-4 rounded-xl border flex items-start gap-4 transition-all ${isDone ? (isCurrent ? 'bg-brand-yellow/10 border-brand-yellow' : 'bg-slate-950 border-slate-800') : 'bg-slate-950/40 border-slate-900 opacity-50'}`;

                item.innerHTML = `
                    <div class="w-8 h-8 rounded-full ${isDone ? 'bg-brand-yellow text-slate-950 font-black' : 'bg-slate-800 text-slate-500'} flex items-center justify-center text-xs shrink-0 mt-0.5">
                        ${isDone ? '<i class="fa-solid fa-check"></i>' : (idx + 1)}
                    </div>
                    <div>
                        <strong class="text-sm font-bold ${isDone ? 'text-white' : 'text-slate-500'} block">${s.label}</strong>
                        <span class="text-xs text-slate-400 mt-0.5 block">${s.desc}</span>
                    </div>
                `;
                container.appendChild(item);
            });
        }
    </script>
</body>
</html>
