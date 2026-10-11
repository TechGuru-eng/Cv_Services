<?php
require_once 'admin/config/db.php';
date_default_timezone_set('Africa/Douala');

// Check Global Switch (Optional, reusing 'flash_status' or assume always open for now)
// $stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'flash_status'");
// $status = $stmt->fetchColumn();

// Hardcoded Deadline: 25 Jan 2026 23:59:59
$deadline = '2026-01-25 23:59:59';
if (time() > strtotime($deadline)) {
    // Expired
    header("Location: index.html");
    exit;
}

$price = 6000;
?>
<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidature CAMTEL 2026 - Pack Expert | HireMe</title>
    <meta name="description" content="Ne ratez pas votre chance chez CAMTEL ! Obtenez un CV professionnel et une lettre de motivation sur mesure pour 6000 FCFA. Offre Spéciale Candidature 2026.">
    
    <!-- OG Meta -->
    <meta property="og:title" content="Recrutement Camtel : Votre dossier validé à 100%">
    <meta property="og:description" content="Maximisez vos chances ! CV + Lettre de Motivation optimisés pour le concours CAMTEL. Prix Promo : 6000 FCFA. Cliquez ici pour commander.">
    <meta property="og:image" content="https://moncvpro.hiremeguide.com/assets/camtel_flyer.jpg">
    <meta property="og:url" content="https://moncvpro.hiremeguide.com/camtel-promo.php">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Favicons -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: {
                        camtel: '#009FE3', // Official Camtel Blue approx
                        dark: '#0f172a',
                        gold: '#fbbf24'
                    }
                }
            }
        }
    </script>
    <style>
        .glass {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(229, 231, 235, 0.5);
        }
    </style>
    <script src="assets/js/site-language.js?v=4" defer></script>

    <!-- Shared HireMe favicon -->
    <link rel="icon" type="image/png" sizes="48x48" href="assets/favicons/favicon-48.png?v=20261011">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicons/favicon-32.png?v=20261011">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/favicons/favicon-16.png?v=20261011">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/favicons/apple-touch-icon-180.png?v=20261011">
</head>
<body class="bg-gray-50 text-gray-900 font-[Inter] antialiased">

    <!-- Top Urgent Bar -->
    <div class="bg-red-600 text-white text-[10px] md:text-xs font-bold text-center py-3 px-4 shadow sticky top-0 z-50">
        <span class="animate-pulse">⚠️ ALERTE : CLÔTURE DANS 2 JOURS — DÉPÊCHEZ-VOUS !</span>
        <span id="countdown" class="font-mono text-sm ml-2 bg-black/20 px-2 py-1 rounded">...</span>
    </div>

    <!-- Trust Navigation -->
    <nav class="bg-white border-b border-gray-100 py-3 shadow-sm relative z-40">
        <div class="max-w-4xl mx-auto px-4 flex justify-between items-center">
            <a href="index.html" class="flex items-center gap-2 text-gray-800 hover:text-camtel transition">
                <span class="font-black text-xl tracking-tighter">MonCV<span class="text-camtel">Pro</span></span>
                <span class="hidden sm:inline-block bg-gray-100 text-gray-500 text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider">Site Officiel</span>
            </a>
            <a href="index.html" class="text-xs font-bold text-gray-500 hover:text-camtel flex items-center gap-1 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                Visiter le site principal
            </a>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto px-4 py-8">
        
        <!-- Hero Section -->
        <div class="text-center mb-10">
            <img src="assets/camtel_flyer.jpg" alt="Camtel Recrutement" class="w-full max-w-lg mx-auto rounded-2xl shadow-2xl mb-8 hover:scale-[1.01] transition-transform duration-500 border-4 border-white">
            
            <h1 class="text-3xl md:text-5xl font-black mb-4 text-camtel">
                Maximisez vos chances chez CAMTEL
            </h1>
            <p class="text-xl text-gray-600 font-medium max-w-2xl mx-auto">
                Ne laissez pas un mauvais CV rejeter votre candidature. 
                Obtenez un dossier professionnel et optimisé pour <b>Stagiaires Professionnels, Techniciens & Commerciaux</b>.
            </p>
        </div>

        <!-- Urgent Notice Box -->
        <div class="bg-red-50 border-l-4 border-red-600 p-6 rounded-2xl mb-8 shadow-sm">
            <div class="flex items-start gap-4">
                <div class="bg-red-600 text-white p-2 rounded-full animate-bounce">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                </div>
                <div>
                    <h3 class="text-red-900 font-black text-lg">ALERTE : Clôture imminente !</h3>
                    <p class="text-red-700 text-sm mt-1 leading-relaxed">
                        Il ne reste plus que <b>2 jours</b> pour soumettre votre dossier CAMTEL. 
                        <b>⚠️ IMPORTANT :</b> C'est aujourd'hui le <b>dernier jour</b> pour obtenir votre <b>Timbre Fiscal</b> avant la fermeture des guichets. Ne vous laissez pas bloquer à la dernière minute !
                    </p>
                </div>
            </div>
        </div>

        <!-- The Offer -->
        <div class="bg-white rounded-3xl shadow-xl overflow-hidden mb-12 border border-gray-100">
            <div class="bg-camtel p-6 text-white text-center relative overflow-hidden">
                <div class="absolute top-0 right-0 p-16 bg-white/10 rounded-full -mr-10 -mt-10 blur-2xl"></div>
                <h2 class="text-2xl font-black uppercase tracking-tight relative z-10">Pack Spécial Recrutement</h2>
                <div class="mt-2 relative z-10">
                    <span class="text-sm opacity-80 line-through">Valeur 15 000 FCFA</span>
                    <span class="text-4xl font-black ml-2 text-yellow-300">6 000 FCFA</span>
                </div>
            </div>
            
            <div class="p-8 md:p-12 grid md:grid-cols-2 gap-8 items-center">
                <div class="space-y-4">
                    <h3 class="font-bold text-gray-800 text-lg">Inclus dans le pack :</h3>
                    <ul class="space-y-3">
                        <li class="flex items-start gap-3">
                            <span class="text-green-500 font-bold text-xl">✓</span>
                            <span class="text-gray-600">Refonte complète du <b>CV Design & ATS</b></span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-green-500 font-bold text-xl">✓</span>
                            <span class="text-gray-600">Rédaction de la <b>Lettre de Motivation</b> ciblée Camtel</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-green-500 font-bold text-xl">✓</span>
                            <span class="text-gray-600">Guide pour l'inscription sur <b>career.camtel.cm</b></span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-green-500 font-bold text-xl">✓</span>
                            <span class="text-gray-600">Livraison Express (24h max)</span>
                        </li>
                    </ul>
                </div>
                
                <div class="bg-gray-50 p-6 rounded-2xl border border-dashed border-gray-300 text-center">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4">Places limitées</p>
                    <button onclick="scrollToForm()" class="w-full bg-camtel hover:bg-blue-600 text-white font-black py-4 rounded-xl shadow-lg shadow-blue-500/30 transition transform hover:scale-105">
                        COMMANDER MAINTENANT
                    </button>
                    <p class="text-xs text-gray-500 mt-3 flex items-center justify-center gap-1">
                        <svg class="w-3 h-3 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        Satisfait ou Remboursé
                    </p>
                </div>
            </div>
        </div>

        <!-- Order Form -->
        <div id="orderForm" class="max-w-2xl mx-auto">
            <div class="bg-white rounded-2xl shadow-xl p-8 border border-gray-100 relative pt-10">
                <div class="absolute -top-5 left-1/2 transform -translate-x-1/2 bg-gray-900 text-white px-6 py-2 rounded-full font-bold text-xs sm:text-sm uppercase tracking-widest shadow-lg whitespace-nowrap z-10 w-max">
                    Formulaire de commande
                </div>

                <form id="camtelForm" class="mt-2 space-y-6">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-gray-500 uppercase">Nom complet</label>
                            <input type="text" id="name" required class="w-full bg-gray-50 border border-gray-200 rounded-lg p-3 focus:ring-2 focus:ring-camtel focus:border-transparent outline-none transition">
                        </div>
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-gray-500 uppercase">Ville</label>
                            <input type="text" id="city" required class="w-full bg-gray-50 border border-gray-200 rounded-lg p-3 focus:ring-2 focus:ring-camtel focus:border-transparent outline-none transition">
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="text-xs font-bold text-gray-500 uppercase">Numéro WhatsApp</label>
                        <input type="tel" id="whatsapp" required placeholder="Ex: 6xxxxxxx" class="w-full bg-gray-50 border border-gray-200 rounded-lg p-3 focus:ring-2 focus:ring-camtel focus:border-transparent outline-none transition">
                    </div>

                    <div class="pt-4 border-t border-gray-100">
                         <label class="text-xs font-bold text-gray-500 uppercase block mb-3">Paiement Mobile (<?= number_format($price, 0, ',', ' ') ?> FCFA)</label>
                         
                         <div class="grid grid-cols-2 gap-3 mb-4">
                            <label class="cursor-pointer">
                                <input type="radio" name="method" value="MTN" class="peer hidden" checked onchange="updateUSSD()">
                                <div class="p-3 rounded-lg border border-gray-200 peer-checked:border-yellow-400 peer-checked:bg-yellow-50 text-center transition">
                                    <span class="text-sm font-bold text-gray-700">MTN Money</span>
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="method" value="Orange" class="peer hidden" onchange="updateUSSD()">
                                <div class="p-3 rounded-lg border border-gray-200 peer-checked:border-orange-500 peer-checked:bg-orange-50 text-center transition">
                                    <span class="text-sm font-bold text-gray-700">Orange Money</span>
                                </div>
                            </label>
                        </div>

                        <div class="bg-blue-50 p-4 rounded-xl border border-blue-100 text-center mb-4 overflow-hidden">
                            <p class="text-xs text-blue-600 mb-1">Composez le code ci-dessous :</p>
                            <a id="ussdLink" href="#" class="text-sm sm:text-lg md:text-xl font-mono font-black text-blue-900 block bg-white py-3 rounded border border-blue-200 shadow-sm hover:shadow transition break-all sm:break-normal">
                                ...
                            </a>
                            <p class="text-[10px] text-gray-400 mt-2">Nom : NJAI NDINTEK HANDSON</p>
                        </div>

                        <div class="space-y-1">
                            <label class="text-xs font-bold text-gray-500 uppercase">ID Transaction / Capture</label>
                            <input type="text" id="transId" placeholder="ID (facultatif si capture)" class="w-full bg-gray-50 border border-gray-200 rounded-lg p-3 mb-2 text-sm">
                            <input type="file" id="proof" accept="image/*" class="w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-camtel file:text-white hover:file:bg-blue-600">
                        </div>
                    </div>

                    <button type="submit" id="submitBtn" class="w-full bg-gray-900 text-white font-black py-4 rounded-xl shadow-xl hover:bg-black transition-all transform hover:-translate-y-1 disabled:opacity-70 disabled:cursor-not-allowed">
                        VALIDER MA COMMANDE
                    </button>
                    
                </form>
            </div>
        </div>
        
        <div class="mt-12 text-center text-gray-400 text-sm">
            <p>Besoin d'aide ? <a href="https://wa.me/237654250688" class="text-camtel font-bold hover:underline">Contactez-nous sur WhatsApp</a></p>
            <p class="mt-2 text-xs">HireMe Guide - 2026. Non affilié à Camtel.</p>
        </div>

    </div>

    <!-- Logic -->
    <script>
        const PRICE = <?= $price ?>;
        
        // Countdown
        const deadline = new Date("<?= $deadline ?>").getTime();
        setInterval(() => {
            const now = new Date().getTime();
            const t = deadline - now;
            if (t < 0) {
                document.getElementById("countdown").innerText = "EXPIRED";
                return;
            }
            const days = Math.floor(t / (1000 * 60 * 60 * 24));
            const hours = Math.floor((t % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((t % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((t % (1000 * 60)) / 1000);
            document.getElementById("countdown").innerText = `${days}j ${hours}h ${minutes}m ${seconds}s`;
        }, 1000);

        function scrollToForm() {
            document.getElementById('orderForm').scrollIntoView({behavior: 'smooth'});
        }

        // USSD
        function updateUSSD() {
            const method = document.querySelector('input[name="method"]:checked').value;
            const link = document.getElementById('ussdLink');
            if (method === 'MTN') {
                const code = `*126*14*654250688*${PRICE}#`;
                link.innerText = code;
                link.href = `tel:${code.replace(/#/g, '%23')}`;
            } else {
                const code = `#150*1*1*659197506*${PRICE}#`;
                link.innerText = code;
                link.href = `tel:${code.replace(/#/g, '%23')}`;
            }
        }
        updateUSSD();

        // Submit
        document.getElementById('camtelForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('submitBtn');
            const originalText = btn.innerText;
            btn.innerText = 'TRAITEMENT...';
            btn.disabled = true;

            const formData = new FormData();
            formData.append('name', document.getElementById('name').value);
            formData.append('city', document.getElementById('city').value);
            formData.append('whatsapp', document.getElementById('whatsapp').value);
            formData.append('paymentMethod', document.querySelector('input[name="method"]:checked').value);
            formData.append('transactionId', document.getElementById('transId').value || 'N/A');
            const proof = document.getElementById('proof').files[0];
            if(proof) formData.append('proofFile', proof);
            
            // Hardcoded Order Details
            formData.append('package_name', 'Pack Candidature Camtel');
            formData.append('amount', PRICE);

            try {
                const res = await fetch('save-order.php', { method: 'POST', body: formData });
                const json = await res.json();
                
                if (json.success) {
                    // Redirect to Camtel Merci Page with Name
                    const name = encodeURIComponent(document.getElementById('name').value);
                    window.location.href = `camtel-merci.php?name=${name}`;
                } else {
                    alert('Erreur: ' + (json.message || 'Inconnue'));
                    btn.disabled = false;
                    btn.innerText = originalText;
                }
            } catch (err) {
                console.error(err);
                alert('Erreur de connexion.');
                btn.disabled = false;
                btn.innerText = originalText;
            }
        });

        // --- Lead Capture ---
        function saveLead() {
            const data = {
                name: document.getElementById('name').value,
                whatsapp: document.getElementById('whatsapp').value,
                source: 'Camtel Promo Page',
                details: {
                    city: document.getElementById('city').value,
                    price: PRICE
                }
            };
            
            if (data.whatsapp.length > 5) {
                fetch('api/save_lead.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                }).catch(err => console.log('Lead silence', err));
            }
        }

        let debounceTimer;
        const captureFields = ['name', 'city', 'whatsapp'];

        function debouncedSave() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(saveLead, 2000); // Wait 2s after last keystroke
        }

        captureFields.forEach(id => {
            const el = document.getElementById(id);
            if(el) {
                el.addEventListener('blur', saveLead);
                el.addEventListener('input', debouncedSave);
            }
        });

    </script>
    <!-- Announcement Logic -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            fetch('api/get_active_announcement.php')
                .then(res => res.json())
                .then(result => {
                    if (result.success && result.data) {
                        const ann = result.data;
                        // Show if not seen in this session
                        if (!sessionStorage.getItem('seen_ann_' + ann.id)) {
                             setTimeout(() => {
                                showSpecialModal(ann);
                             }, 2000); // 2s delay
                        }
                    }
                })
                .catch(err => console.log("Announcement load fail"));
        });

        function showSpecialModal(ann) {
            const modalInfo = {
                'info': { emoji: '📢', title: 'Information', bg: 'bg-gradient-to-br from-blue-900 via-indigo-900 to-slate-900', text: 'text-blue-50', border: 'border-blue-400' },
                'danger': { emoji: '🚨', title: 'URGENCE', bg: 'bg-gradient-to-br from-red-900 via-red-800 to-black', text: 'text-white', border: 'border-red-500' },
                'success': { emoji: '✨', title: 'Succès', bg: 'bg-gradient-to-br from-emerald-900 via-teal-900 to-black', text: 'text-emerald-50', border: 'border-emerald-400' },
                'default': { emoji: '👋', title: 'Alerte', bg: 'bg-gradient-to-br from-gray-900 via-slate-800 to-black', text: 'text-white', border: 'border-gray-500' }
            };

            const theme = modalInfo[ann.type] || modalInfo['default'];
            const modal = document.createElement('div');
            modal.className = `fixed inset-0 z-[120] flex items-center justify-center p-4 transition-opacity duration-300 opacity-0`;
            modal.style.backgroundColor = 'rgba(0,0,0,0.85)';
            modal.style.backdropFilter = 'blur(10px)';
            modal.id = 'special-announcement-modal';

            modal.innerHTML = `
                <div class="relative w-full max-w-lg rounded-3xl p-1 shadow-2xl transform scale-90 transition-transform duration-300 ${theme.bg}" id="ann-modal-content">
                    <div class="bg-white/10 backdrop-blur-md rounded-[22px] p-8 text-center border-2 ${theme.border} relative overflow-hidden">
                        <div class="relative z-10">
                            <div class="text-6xl mb-4 animate-bounce">${theme.emoji}</div>
                            <h2 class="text-3xl font-black ${theme.text} mb-4 tracking-tight uppercase">${theme.title}</h2>
                            <p class="text-xl ${theme.text} opacity-95 mb-8 leading-relaxed font-bold">
                                ${ann.message.replace(/\n/g, '<br>')}
                            </p>
                            <button onclick="closeSpecialModal(${ann.id})" class="w-full bg-white text-black font-black py-4 px-8 rounded-2xl hover:bg-gray-100 transition-all transform hover:scale-[1.02] shadow-xl">
                                COMPRIS ! 🚀
                            </button>
                        </div>
                    </div>
                </div>
            `;

            document.body.appendChild(modal);
            requestAnimationFrame(() => {
                modal.classList.remove('opacity-0');
                modal.querySelector('#ann-modal-content').classList.remove('scale-90');
                modal.querySelector('#ann-modal-content').classList.add('scale-100');
            });
        }

        function closeSpecialModal(id) {
            const modal = document.getElementById('special-announcement-modal');
            if (modal) {
                modal.classList.add('opacity-0');
                setTimeout(() => {
                    modal.remove();
                    sessionStorage.setItem('seen_ann_' + id, 'true');
                }, 300);
            }
        }
    </script>
    <!-- Stat Tracking -->
    <img src="api/track.php" alt="" style="display:none;">
</body>
</html>
