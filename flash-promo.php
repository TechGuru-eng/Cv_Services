<?php
require_once 'admin/config/db.php';
date_default_timezone_set('Africa/Douala'); // Set Timezone to User Local

// 1. Check Global Status
$stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'flash_status'");
$status = $stmt->fetchColumn();

// Check Time Expiry
$stmtTime = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'flash_end_time'");
$endTimeStr = $stmtTime->fetchColumn();

// Check Secret Access Token
$stmtToken = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'flash_access_token'");
$validToken = $stmtToken->fetchColumn();
$userToken = $_GET['access'] ?? '';

// If a token is set in Admin, enforce it!
if (!empty($validToken) && $validToken !== $userToken) {
    // Invalid or missing token -> Redirect to Home
    header("Location: index.html");
    exit;
}

if ($status === 'OPEN' && $endTimeStr && time() > strtotime($endTimeStr)) {
    $status = 'EXPIRED'; // Force close
}

if ($status !== 'OPEN') {
    // CLOSED STATE UI
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
<meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Vente Flash Terminée</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&display=swap" rel="stylesheet">
    <!-- Favicons -->
    <script src="assets/js/site-language.js?v=4" defer></script>

    <!-- Shared HireMe favicon -->
    <link rel="icon" type="image/png" sizes="48x48" href="assets/favicons/favicon-48.png?v=20261011">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicons/favicon-32.png?v=20261011">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/favicons/favicon-16.png?v=20261011">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/favicons/apple-touch-icon-180.png?v=20261011">
</head>
    <body class="bg-gray-900 text-white flex flex-col items-center justify-center min-h-screen w-full font-[Inter]">
        <div class="text-center p-8 max-w-lg mx-auto">
            <div class="mb-6 text-6xl">🔒</div>
            <h1 class="text-4xl font-black mb-4 uppercase tracking-tighter">Session Fermée</h1>
            <p class="text-gray-400 text-lg mb-8">
                La vente flash est terminée. <br>
                Mais ne vous inquiétez pas ! Vous pouvez toujours commander votre CV Professionnel sur notre site principal à d'excellents prix.
            </p>
            <a href="index.html" class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-8 py-4 rounded-full text-sm font-bold tracking-widest transition-all transform hover:scale-105 shadow-lg shadow-blue-900/50">
                VOIR NOS OFFRES STANDARDS &rarr;
            </a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// 2. Fetch Active Products
$products = $pdo->query("SELECT * FROM flash_products WHERE is_active = 1 ORDER BY min_price ASC")->fetchAll(PDO::FETCH_ASSOC);

// 3. Fetch Countdown End Time
$stmtTime = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'flash_end_time'");
$countDownEnd = $stmtTime->fetchColumn();
?>
<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>⚡ VENTE FLASH EXCLUSIVE - Payez ce que vous pouvez !</title>
    <meta name="robots" content="noindex, nofollow">
    
    <!-- Social Media Preview -->
    <meta property="og:title" content="⚡ VENTE FLASH : Payez ce que vous pouvez !">
    <meta property="og:description" content="Accès exclusif. Quantités limitées. Obtenez votre CV Professionnel à un prix imbattable.">
    <meta property="og:image" content="https://moncvpro.hiremeguide.com/assets/flash-preview.jpg">
    
    <!-- Dynamic OG URL to pass Security Check -->
    <meta property="og:url" content="https://moncvpro.hiremeguide.com/flash-promo?access=<?= $validToken ?>">
    <meta property="og:type" content="website">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">
    <!-- Favicons -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: {
                        gold: '#FFD700',
                        dark: '#111827',
                        accent: '#FF0055'
                    },
                    animation: {
                        'pulse-fast': 'pulse 1.5s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                    }
                }
            }
        }
    </script>
    <style>
        .glass {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .selected-card {
            border-color: #FFD700; /* Gold */
            background: rgba(255, 215, 0, 0.1);
        }
    </style>
    <script src="assets/js/site-language.js?v=4" defer></script>
</head>
    <body class="bg-dark text-white font-[Inter] antialiased">
        
        <!-- Top Sticky Banner -->
        <div class="bg-[#FF0055] text-white text-xs font-bold text-center py-2 px-4 shadow-lg z-50 sticky top-0 uppercase tracking-widest relative">
            <span class="animate-pulse">🔥 FLASH SALE EN COURS — <span id="liveVisitors" class="bg-white text-[#FF0055] px-1 rounded mx-1">...</span> PERSONNES SUR CETTE PAGE !</span>
            
            <!-- Live Counter Script -->
            <script>
                function updateVisitors() {
                    fetch('api/get-flash-stats.php?page=flash')
                        .then(response => response.json())
                        .then(data => {
                            const counter = document.getElementById('liveVisitors');
                            if(counter) counter.innerText = data.count;
                        })
                        .catch(err => console.error(err));
                }
                
                // Poll every 5 seconds
                setInterval(updateVisitors, 5000);
                updateVisitors(); // Initial call
            </script>
        </div>

    <div class="max-w-4xl mx-auto px-4 py-12">
        
        <!-- Header -->
        <div class="text-center mb-12">
            <h1 class="text-4xl sm:text-6xl font-black mb-4 text-transparent bg-clip-text bg-gradient-to-r from-gold to-yellow-200">
                MEGA VENTE SECRET MODE
            </h1>
            
            <?php if ($countDownEnd): ?>
            <!-- Countdown Timer -->
            <div id="countdownContainer" class="flex justify-center items-center gap-4 mb-6 bg-gray-800/50 p-4 rounded-xl border border-gray-700 w-fit mx-auto">
                <div class="text-center">
                    <span id="cd_h" class="block text-3xl font-black text-white font-mono">00</span>
                    <span class="text-[10px] text-gray-400 uppercase tracking-widest">Heures</span>
                </div>
                <div class="text-3xl font-black text-gray-600">:</div>
                <div class="text-center">
                    <span id="cd_m" class="block text-3xl font-black text-white font-mono">00</span>
                    <span class="text-[10px] text-gray-400 uppercase tracking-widest">Minutes</span>
                </div>
                <div class="text-3xl font-black text-gray-600">:</div>
                <div class="text-center">
                    <span id="cd_s" class="block text-3xl font-black text-accent font-mono">00</span>
                    <span class="text-[10px] text-gray-400 uppercase tracking-widest">Secondes</span>
                </div>
            </div>
            <script>
                const endTime = new Date("<?= $countDownEnd ?>").getTime();
                
                function updateTimer() {
                    const now = new Date().getTime();
                    const diff = endTime - now;
                    
                    if (diff < 0) {
                        // Timer expired, reload to trigger server-side lock
                        window.location.reload();
                        return;
                    }
                    
                    const h = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)); // Allow > 24h hours display? Or days? Let's stick to simple H:M:S for now, or add days if needed. Assuming < 24h sales usually. If > 24h, let's include days in hours.
                    const totalHours = Math.floor(diff / (1000 * 60 * 60)); // Show total hours e.g. 48h
                    const m = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                    const s = Math.floor((diff % (1000 * 60)) / 1000);
                    
                    document.getElementById('cd_h').innerText = totalHours < 10 ? '0' + totalHours : totalHours;
                    document.getElementById('cd_m').innerText = m < 10 ? '0' + m : m;
                    document.getElementById('cd_s').innerText = s < 10 ? '0' + s : s;
                }
                setInterval(updateTimer, 1000);
                updateTimer();
            </script>
            <?php endif; ?>

            <p class="text-gray-400 text-lg max-w-2xl mx-auto">
                Accès exclusif. Choisissez votre pack, payez le montant de votre choix (selon conditions) et obtenez votre CV Pro.
            </p>
        </div>

        <!-- 1. Product Selection -->
        <h2 class="text-2xl font-bold mb-6 flex items-center gap-2">
            <span class="bg-gold text-black rounded-full w-8 h-8 flex items-center justify-center text-sm font-black">1</span>
            Choisissez votre Pack
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-12">
            <?php foreach ($products as $p): 
                $remaining = $p['max_stock'] - $p['sold_count'];
                $isSoldOut = $remaining <= 0;
            ?>
            <div onclick="<?= $isSoldOut ? '' : "selectProduct(this, '{$p['name']}', {$p['min_price']}, {$p['reference_price']})" ?>" 
                 class="product-card glass rounded-xl p-6 cursor-pointer transition-all hover:scale-[1.02] border border-transparent relative <?= $isSoldOut ? 'opacity-50 cursor-not-allowed pointer-events-none' : '' ?>">
                
                <h3 class="text-xl font-bold text-white mb-1"><?= htmlspecialchars($p['name']) ?></h3>
                <div class="flex justify-between items-end">
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-widest">VALEUR RÉELLE</p>
                        <p class="text-lg text-gray-400 line-through decoration-red-500 font-mono"><?= number_format($p['reference_price'], 0, ',', ' ') ?> XAF</p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-gold uppercase tracking-widest font-bold">MINIMUM ACCEPTÉ</p>
                        <p class="text-2xl font-black text-white font-mono"><?= number_format($p['min_price'], 0, ',', ' ') ?> <small class="text-sm text-gray-400">XAF</small></p>
                    </div>
                </div>
                
                <!-- Hidden Radio -->
                <input type="radio" name="selected_product" value="<?= $p['name'] ?>" class="hidden">
                
                <?php 
                $remaining = $p['max_stock'] - $p['sold_count'];
                if ($remaining <= 0): 
                ?>
                    <!-- Sold Out Overlay -->
                    <div class="absolute inset-0 bg-black/80 flex items-center justify-center rounded-xl z-10">
                        <span class="text-red-500 font-black text-2xl -rotate-12 border-4 border-red-500 px-4 py-2 uppercase">Épuisé</span>
                    </div>
                <?php elseif ($remaining <= 5): ?>
                    <!-- Urgency Badge -->
                    <div class="absolute -top-3 -right-3 bg-red-600 text-white text-[10px] font-bold px-3 py-1 rounded-full animate-pulse shadow-lg z-20">
                        🔥 PLUS QUE <?= $remaining ?> PLACES DISPONIBLES
                    </div>
                <?php endif; ?>
                
                <!-- Stock Progress Bar -->
                <div class="mt-4">
                    <div class="flex justify-between text-[10px] font-bold text-gray-400 mb-1 uppercase tracking-wider">
                        <span>Stock Écoulé</span>
                        <span><?= $p['sold_count'] ?> / <?= $p['max_stock'] ?></span>
                    </div>
                    <?php 
                        $percent = ($p['sold_count'] / $p['max_stock']) * 100;
                        if($percent > 100) $percent = 100;
                        
                        // Color Logic
                        $barColor = 'bg-green-500';
                        if($percent > 50) $barColor = 'bg-yellow-500';
                        if($percent > 80) $barColor = 'bg-red-600 animate-pulse';
                    ?>
                    <div class="w-full bg-gray-700/50 rounded-full h-2 overflow-hidden">
                        <div class="<?= $barColor ?> h-2 rounded-full transition-all duration-1000 ease-out" style="width: <?= $percent ?>%"></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- 2. Pay What You Want -->
        <div id="paymentSection" class="opacity-50 pointer-events-none transition-opacity duration-500">
            <h2 class="text-2xl font-bold mb-6 flex items-center gap-2">
                <span class="bg-gold text-black rounded-full w-8 h-8 flex items-center justify-center text-sm font-black">2</span>
                Définissez votre Prix
            </h2>

            <div class="glass rounded-2xl p-8 mb-12 text-center">
                <p class="text-gray-400 mb-2">Combien souhaitez-vous payer ?</p>
                <div class="relative max-w-xs mx-auto mb-4">
                    <input type="number" id="userAmount" 
                           class="w-full bg-transparent border-b-2 border-gray-600 text-center text-4xl font-black text-white focus:outline-none focus:border-gold py-2 transition-colors placeholder-gray-700"
                           placeholder="0">
                    <span class="absolute right-0 bottom-4 text-gray-500 font-bold">FCFA</span>
                </div>
                
                <!-- Feedback Badge -->
                <div id="priceBadge" class="inline-block px-4 py-1 rounded-full text-xs font-black uppercase tracking-widest hidden">
                    ...
                </div>
                
                <p id="errorMsg" class="text-red-500 text-sm mt-3 font-bold hidden">
                    ⚠️ Le montant minimum pour ce pack est de <span id="minAmountDisplay">...</span> FCFA.
                </p>
            </div>

            <!-- 3. Payment Form -->
            <h2 class="text-2xl font-bold mb-6 flex items-center gap-2">
                <span class="bg-gold text-black rounded-full w-8 h-8 flex items-center justify-center text-sm font-black">3</span>
                Finalisez le Paiement
            </h2>

            <!-- Imported Form Logic from paiement.html (Simplified & Dark Mode) -->
            <form id="flashForm" class="glass rounded-2xl p-8 space-y-6">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Nom complet</label>
                        <input type="text" id="name" required class="w-full bg-gray-800 border-none rounded-lg p-3 text-white focus:ring-2 focus:ring-gold">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Email</label>
                        <input type="email" id="email" required class="w-full bg-gray-800 border-none rounded-lg p-3 text-white focus:ring-2 focus:ring-gold">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-1">WhatsApp</label>
                    <input type="tel" id="whatsapp" required class="w-full bg-gray-800 border-none rounded-lg p-3 text-white focus:ring-2 focus:ring-gold" placeholder="6...">
                </div>

                <!-- Payment Methods (Compact) -->
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-3">Moyen de paiement</label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="cursor-pointer">
                            <input type="radio" name="method" value="MTN" class="peer hidden" checked>
                            <div class="bg-gray-800 p-3 rounded-lg border border-transparent peer-checked:border-yellow-400 peer-checked:bg-yellow-900/20 text-center transition-all">
                                <span class="block text-xs font-bold text-gray-300">MTN Mobile Money</span>
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="method" value="Orange" class="peer hidden">
                            <div class="bg-gray-800 p-3 rounded-lg border border-transparent peer-checked:border-orange-500 peer-checked:bg-orange-900/20 text-center transition-all">
                                <span class="block text-xs font-bold text-gray-300">Orange Money</span>
                            </div>
                        </label>
                    </div>
                    
                    <!-- Dynamic USSD Info -->
                    <div class="mt-4 bg-gray-800/50 p-4 rounded-lg text-center text-sm border border-gray-700">
                        <p class="text-gray-400 mb-2">Cliquez ci-dessous pour composer :</p>
                        
                        <a id="ussdLink" href="#" class="block bg-gray-700/50 hover:bg-gray-700 border border-gray-600 rounded-lg p-3 transition-colors group">
                            <div class="flex items-center justify-center gap-2">
                                <svg class="w-5 h-5 text-gray-400 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                <span id="ussdCode" class="font-mono text-lg font-bold text-white tracking-wider">...</span>
                            </div>
                            <span class="text-[10px] text-gold uppercase font-bold tracking-widest mt-1 block">Appuyer pour appeler</span>
                        </a>

                        <p class="text-xs text-gray-500 mt-2">Bénéficiaire : NJAI NDINTEK HANDSON</p>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-1">ID Transaction / Preuve</label>
                    <input type="text" id="transId" placeholder="ID de la transaction" required class="w-full bg-gray-800 border-none rounded-lg p-3 text-white mb-2 focus:ring-2 focus:ring-gold">
                    <input type="file" id="proof" accept="image/*" required class="w-full text-xs text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-gray-700 file:text-white hover:file:bg-gray-600">
                </div>

                <div id="submitError" class="hidden bg-red-900/50 text-red-200 p-3 rounded text-sm text-center"></div>

                <button type="submit" id="submitBtn" class="w-full bg-gradient-to-r from-gold to-yellow-500 text-black font-black py-4 rounded-xl shadow-lg hover:scale-[1.02] transition-transform text-lg uppercase tracking-wide disabled:opacity-50 disabled:cursor-not-allowed">
                    CONFIRMER MON OFFRE
                </button>
            </form>

        </div>

    </div>

    <!-- Script Logic -->
    <script>
        // State
        let currentMin = 0;
        let selectedProduct = '';
        
        // Count Fetcher
        async function updateCounter() {
            try {
                const res = await fetch('api/get-flash-stats.php');
                const data = await res.json();
                document.getElementById('liveCounter').innerText = data.count || '0';
            } catch(e) { console.log(e); }
        }
        updateCounter();
        setInterval(updateCounter, 10000); // 10s refresh

        // Product Selection
        window.selectProduct = function(el, name, min, ref) {
            // UI
            document.querySelectorAll('.product-card').forEach(c => c.classList.remove('selected-card'));
            el.classList.add('selected-card');
            
            // State
            selectedProduct = name;
            currentMin = parseInt(min);
            
            // Unlock Section 2
            const paySec = document.getElementById('paymentSection');
            paySec.classList.remove('opacity-50', 'pointer-events-none');
            
            // Focus Input
            document.getElementById('userAmount').focus();
            
            // Reset validation
            validateAmount();
        }

        // Amount Validation Logic
        const amountInput = document.getElementById('userAmount');
        const badge = document.getElementById('priceBadge');
        const errorMsg = document.getElementById('errorMsg');
        const ussdDisplay = document.getElementById('ussdCode');
        const ussdLink = document.getElementById('ussdLink');

        function validateAmount() {
            const val = parseInt(amountInput.value) || 0;
            const minDisp = document.getElementById('minAmountDisplay');
            
            // Update USSD
            const payMethod = document.querySelector('input[name="method"]:checked').value;
            if (val > 0) {
                if (payMethod === 'MTN') {
                    ussdDisplay.innerText = `*126*14*654250688*${val}#`;
                    ussdLink.href = `tel:*126*14*654250688*${val}%23`;
                } else if (payMethod === 'Orange') {
                    ussdDisplay.innerText = `#150*1*1*659197506*${val}#`;
                    ussdLink.href = `tel:%23150*1*1*659197506*${val}%23`;
                }
            } else {
                ussdDisplay.innerText = "Saisissez un montant...";
                ussdLink.href = "#";
            }

            if (val < currentMin) {
                // Error State
                badge.classList.add('hidden');
                errorMsg.classList.remove('hidden');
                minDisp.innerText = currentMin;
                document.getElementById('submitBtn').disabled = true;
                return false;
            } else {
                // Valid State
                errorMsg.classList.add('hidden');
                badge.classList.remove('hidden');
                document.getElementById('submitBtn').disabled = false;

                // Adjective Logic
                badge.className = "inline-block px-4 py-1 rounded-full text-xs font-black uppercase tracking-widest";
                if (val >= currentMin * 5) {
                    badge.innerText = "👑 CLIENT VIP";
                    badge.classList.add('bg-purple-500', 'text-white');
                } else if (val >= currentMin * 2.5) {
                    badge.innerText = "🔥 OFFRE INCROYABLE";
                    badge.classList.add('bg-red-500', 'text-white');
                } else if (val >= currentMin * 1.5) {
                    badge.innerText = "✨ TRÈS GÉNÉREUX";
                    badge.classList.add('bg-green-500', 'text-white');
                } else {
                    badge.innerText = "👍 OFFRE ACCEPTÉE";
                    badge.classList.add('bg-blue-500', 'text-white');
                }
                return true;
            }
        }

        amountInput.addEventListener('input', validateAmount);
        document.querySelectorAll('input[name="method"]').forEach(r => {
            r.addEventListener('change', validateAmount);
        });

        // Abandoned Cart Capture
        function saveLead() {
            const data = {
                name: document.getElementById('name').value,
                email: document.getElementById('email').value,
                whatsapp: document.getElementById('whatsapp').value,
                source: 'Flash Promo',
                details: {
                    pack: selectedProduct || 'Not Selected',
                    amount: amountInput.value
                }
            };
            
            if (data.whatsapp.length > 5 || data.email.length > 5) {
                fetch('api/save_lead.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                }).catch(err => console.log('Lead save fail', err));
            }
        }

        ['name', 'email', 'whatsapp'].forEach(id => {
            document.getElementById(id).addEventListener('blur', saveLead);
        });
        document.getElementById('userAmount').addEventListener('blur', saveLead);

        // Submission
        document.getElementById('flashForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!validateAmount()) return;
            
            const btn = document.getElementById('submitBtn');
            const originalText = btn.innerText;
            btn.disabled = true;
            btn.innerText = "TRAITEMENT EN COURS...";
            
            try {
                const formData = new FormData();
                formData.append('name', document.getElementById('name').value);
                formData.append('email', document.getElementById('email').value);
                formData.append('whatsapp', document.getElementById('whatsapp').value);
                formData.append('paymentMethod', document.querySelector('input[name="method"]:checked').value);
                formData.append('transactionId', document.getElementById('transId').value);
                formData.append('proofFile', document.getElementById('proof').files[0]);
                
                // FLASH SPECIFIC
                formData.append('package_name', selectedProduct + ' (FLASH)');
                formData.append('amount', amountInput.value);
                formData.append('flash_access', <?= json_encode($userToken, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>);
                
                const res = await fetch('save-order.php', { method: 'POST', body: formData });
                const json = await res.json();
                
                if (json.success) {
                     // Redirect to Upsell Page FIRST
                     const params = new URLSearchParams({
                        name: document.getElementById('name').value,
                        pack: selectedProduct, // e.g., "Pack Etudiant"
                        amount: amountInput.value
                     });
                     window.location.href = 'flash-upsell.php?' + params.toString();
                } else {
                    throw new Error(json.message || "Erreur inconnue");
                }

            } catch (err) {
                alert("Erreur: " + err.message);
                btn.disabled = false;
                btn.innerText = originalText;
            }
        });

    </script>
    <!-- Social Proof Notifications -->
    <div id="socialProof" class="fixed bottom-4 left-4 z-50 transform translate-y-20 opacity-0 transition-all duration-500 pointer-events-none">
        <div class="bg-gray-800/90 backdrop-blur border border-gray-700 p-4 rounded-xl shadow-2xl flex items-center gap-4 max-w-xs">
            <div class="bg-gradient-to-br from-gold to-yellow-600 w-10 h-10 rounded-full flex items-center justify-center text-black font-black text-xs shrink-0">
                ✓
            </div>
            <div>
                <p class="text-xs text-gray-400 font-bold uppercase tracking-widest">À l'instant</p>
                <p class="text-sm font-medium text-white">
                    <span id="spName" class="font-bold text-gold">...</span> de <span id="spCity">...</span> a commandé <span id="spPack" class="text-gray-300">...</span>
                </p>
            </div>
        </div>
    </div>
    <script>
        let salesQueue = [];
        const fallbackSales = [
            {name: 'Jean D.', city: 'Douala', pack: 'Pack Pro', time: "À l'instant"},
            {name: 'Marie A.', city: 'Yaoundé', pack: 'Pack Etudiant', time: 'Il y a 2 min'},
            {name: 'Paul K.', city: 'Bafoussam', pack: 'Pack Senior', time: 'Il y a 5 min'},
            {name: 'Sarah M.', city: 'Douala', pack: 'Pack Expert', time: 'Il y a 10 min'},
        ];

        async function fetchRecentSales() {
            try {
                const res = await fetch('api/get-recent-sales.php');
                const data = await res.json();
                if (Array.isArray(data) && data.length > 0) {
                    salesQueue = data;
                } else {
                    salesQueue = fallbackSales;
                }
            } catch (e) {
                console.error("Sales fetch error", e);
                salesQueue = fallbackSales;
            }
        }

        function showNotification() {
            if (salesQueue.length === 0) {
                // Fetch if empty, then retry shortly
                fetchRecentSales().then(() => {
                    setTimeout(showNotification, 2000);
                });
                return;
            }

            // Pick random or next? Let's pick random for variety
            const sale = salesQueue[Math.floor(Math.random() * salesQueue.length)];
            
            document.getElementById('spName').innerText = sale.name;
            document.getElementById('spCity').innerText = sale.city;
            document.getElementById('spPack').innerText = sale.pack;
            
            // Add Time if available
            const timeEl = document.querySelector('#socialProof p.text-xs');
            if(timeEl) timeEl.innerText = sale.time || "À l'instant";

            const el = document.getElementById('socialProof');
            el.classList.remove('translate-y-20', 'opacity-0');
            
            // Hide after 5 seconds
            setTimeout(() => {
                el.classList.add('translate-y-20', 'opacity-0');
            }, 5000);

            // Re-trigger random time (10-30s)
            setTimeout(showNotification, Math.random() * 20000 + 10000);
        }
        
        // Initial Fetch and Start
        fetchRecentSales();
        setTimeout(showNotification, 5000);
    </script>
    
    <!-- Stat Tracking -->
    <img src="api/track.php" alt="" style="display:none;">
</body>
</html>
