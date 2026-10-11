<?php
// linkedin/suivi.php
// Order Tracking Page for HireMe LinkedIn Optimization

require_once __DIR__ . '/../admin/config/db.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<?php $linkedinBase = preg_replace('~/(?:linkedin(?:/.*)?|suivi-linkedin)/?$~', '/linkedin/', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)); ?>
    <base href="<?= htmlspecialchars($linkedinBase, ENT_QUOTES, 'UTF-8') ?>">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
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
    <script src="../assets/js/site-language.js?v=6" defer></script>
<link rel="stylesheet" href="../assets/css/linkedin-landing.css?v=4"><link rel="stylesheet" href="../assets/css/linkedin-order.css?v=3"><script src="../assets/js/linkedin-landing.js?v=2" defer></script><title>Suivi de votre commande LinkedIn | HireMe</title>
    <meta name="description" content="Consultez l’avancement de votre commande d’optimisation LinkedIn et retrouvez les informations de suivi de votre prestation HireMe.">
    <meta name="robots" content="noindex, follow">
    <link rel="canonical" href="https://moncvpro.hiremeguide.com/suivi-linkedin">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="fr_FR">
    <meta property="og:site_name" content="HireMe">
    <meta property="og:title" content="Suivi de votre commande LinkedIn | HireMe">
    <meta property="og:description" content="Consultez l’avancement de votre commande d’optimisation LinkedIn et retrouvez les informations de suivi de votre prestation HireMe.">
    <meta property="og:image" content="https://moncvpro.hiremeguide.com/assets/images/linkedin/linkedin-workspace.jpg">
    <meta property="og:image:alt" content="Espace de travail professionnel — HireMe LinkedIn">
    <meta property="og:url" content="https://moncvpro.hiremeguide.com/suivi-linkedin">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Suivi de votre commande LinkedIn | HireMe">
    <meta name="twitter:description" content="Consultez l’avancement de votre commande d’optimisation LinkedIn et retrouvez les informations de suivi de votre prestation HireMe.">
    <meta name="twitter:image" content="https://moncvpro.hiremeguide.com/assets/images/linkedin/linkedin-workspace.jpg">
    <meta name="twitter:image:alt" content="Espace de travail professionnel — HireMe LinkedIn">

    <!-- Shared HireMe favicon -->
    <link rel="icon" type="image/png" sizes="48x48" href="../assets/favicons/favicon-48.png?v=20261011">
    <link rel="icon" type="image/png" sizes="32x32" href="../assets/favicons/favicon-32.png?v=20261011">
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/favicons/favicon-16.png?v=20261011">
    <link rel="apple-touch-icon" sizes="180x180" href="../assets/favicons/apple-touch-icon-180.png?v=20261011">
</head>
<body class="linkedin-landing li-order-page li-tracking-page"><header class="li-header"><a class="li-brand" href="./"><span class="li-brand-mark" aria-hidden="true">H</span><span class="li-brand-name" translate="no">HireMe<span>LinkedIn</span></span></a><nav class="li-desktop-nav"><a href="./">Optimisation LinkedIn</a><a href="commander">Commander</a><a href="suivi-linkedin">Suivi de commande</a></nav><div class="li-language-slot"></div></header>

    <div class="max-w-3xl mx-auto space-y-8">
        
        <!-- Header -->
        <div class="text-center space-y-3">
            <h1 class="text-3xl font-black text-white">Suivi de Commande LinkedIn</h1>
            <p class="text-xs text-slate-400">Entrez votre numéro de référence et vos coordonnées pour consulter l'avancement de votre commande.</p>
        </div>

        <!-- Search Form -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 sm:p-8 shadow-2xl space-y-4">
            <p class="li-tracking-hint">Utilisez les coordonnées renseignées lors de votre commande.</p>
            <form id="trackingSearchForm" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="inputRef" class="block text-xs font-bold text-slate-300 mb-1">Référence de Commande *</label>
                        <input type="text" name="reference" id="inputRef" required placeholder="Ex: HM-LI-284731" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-brand-yellow uppercase">
                    </div>
                    <div>
                        <label for="inputContact" class="block text-xs font-bold text-slate-300 mb-1">Email ou Téléphone *</label>
                        <input type="text" name="contact" id="inputContact" required placeholder="Ex: email@domaine.cm ou 670000000" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-brand-yellow">
                    </div>
                </div>
                
                <button type="submit" id="searchBtn" class="w-full bg-brand-yellow hover:bg-yellow-400 text-slate-950 font-black py-3.5 rounded-xl shadow-lg transition-all">
                    <i class="fa-solid fa-magnifying-glass mr-2"></i> Rechercher ma commande
                </button>
            </form>

            <div id="searchError" role="alert" class="hidden p-4 rounded-xl bg-red-500/10 border border-red-500/30 text-red-400 text-xs font-semibold"></div>
        </div>

        <div class="li-tracking-help"><p>Vous préparez votre première commande ?</p><a href="commander">Créer ma commande →</a><a href="./">Optimisation LinkedIn →</a></div>
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
                <a href="https://wa.me/237654250688" target="_blank" class="inline-flex items-center gap-2 text-xs font-bold text-emerald-400 hover:underline">
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

            if (btn.disabled) return;
            errBox.classList.add('hidden');
            resBox.classList.add('hidden');
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> Recherche...';

            const formData = new FormData(this);
            const apiUrl = '../api/linkedin/track_order.php';

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
                    document.getElementById('resDate').innerText = new Date(o.created_at).toLocaleDateString(document.documentElement.lang === 'en' ? 'en-GB' : 'fr-FR');
                    document.getElementById('resDirectLink').href = 'commande.php?ref=' + encodeURIComponent(o.reference) + '&token=' + encodeURIComponent(o.access_token);

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
