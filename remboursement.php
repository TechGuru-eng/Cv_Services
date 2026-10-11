<?php
// remboursement.php
session_start();
require_once 'admin/config/db.php';

$msg = '';
$error = '';

// Track Visit
$ip = $_SERVER['REMOTE_ADDR'];
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$isBot = preg_match('/bot|crawl|slurp|spider|mediapartners/i', $userAgent) ? 1 : 0;
if (!$isBot) {
    try {
        $stmt = $pdo->prepare("INSERT INTO stats_visits (page_url, ip_address, user_agent, is_bot) VALUES (?, ?, ?, ?)");
        $stmt->execute(['/remboursement.php', $ip, $userAgent, $isBot]);
    } catch (Exception $e) {}
}

// Check if upload dir exists
$uploadDir = 'uploads/refunds/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Fetch Transparency Stats
$totalDelivered = $pdo->query("SELECT COUNT(*) FROM orders WHERE payment_status = 'verified'")->fetchColumn() ?: 0;
// Add a base number to make it look realistic if it's too low, or use real only
$totalRefunds = $pdo->query("SELECT COUNT(*) FROM refund_requests WHERE is_hidden = 0")->fetchColumn() ?: 0;

// Fetch Recent Refund Requests
$recentRefunds = $pdo->query("SELECT name, status, created_at FROM refund_requests WHERE is_hidden = 0 ORDER BY created_at DESC LIMIT 5")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier']);
    $amount = intval($_POST['amount']);
    $reason = trim($_POST['reason']);
    $contacted_admin = isset($_POST['contacted_admin']) ? 1 : 0;
    $accept_ban = isset($_POST['accept_ban']) ? 1 : 0;

    if (!$contacted_admin || !$accept_ban) {
        $error = "Vous devez cocher les conditions avant de soumettre.";
    } else {
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        // Find order
        $stmt = $pdo->prepare("SELECT id, name, email FROM orders WHERE id = ? AND (email = ? OR whatsapp = ?) AND payment_status = 'verified' LIMIT 1");
        $stmt->execute([$order_id, $identifier, $identifier]);
        $order = $stmt->fetch();

        if (!$order) {
            $error = "Commande non trouvée ou non éligible au remboursement. Veuillez chercher et sélectionner une commande valide.";
        } else {
            // Check if refund already requested
            $checkRefund = $pdo->prepare("SELECT id FROM refund_requests WHERE order_id = ?");
            $checkRefund->execute([$order['id']]);
            if ($checkRefund->fetch()) {
                $error = "Vous avez déjà soumis une demande de remboursement pour cette commande.";
            } else {
                // Handle file upload
                if (isset($_FILES['proof']) && $_FILES['proof']['error'] === UPLOAD_ERR_OK) {
                    $ext = pathinfo($_FILES['proof']['name'], PATHINFO_EXTENSION);
                    $allowedExts = ['jpg', 'jpeg', 'png', 'pdf'];
                    if (in_array(strtolower($ext), $allowedExts)) {
                        $filename = 'refund_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                        $filepath = $uploadDir . $filename;
                        if (move_uploaded_file($_FILES['proof']['tmp_name'], $filepath)) {
                            // Insert
                            $insert = $pdo->prepare("INSERT INTO refund_requests (order_id, name, email, amount, reason, proof_path) VALUES (?, ?, ?, ?, ?, ?)");
                            if ($insert->execute([$order['id'], $order['name'], $order['email'], $amount, $reason, $filepath])) {
                                $msg = "Votre demande a été soumise avec succès. Elle sera traitée sous 48h ouvrées.";
                                
                                // Mark as completed in abandonment table
                                try {
                                    $pdo->prepare("UPDATE refund_abandoned SET status = 'completed' WHERE identifier = ?")->execute([$identifier]);
                                } catch (Exception $e) {}
                            } else {
                                $error = "Erreur lors de l'enregistrement de la demande.";
                            }
                        } else {
                            $error = "Erreur lors du téléchargement de la preuve.";
                        }
                    } else {
                        $error = "Format de fichier non autorisé. (JPG, PNG, PDF uniquement)";
                    }
                } else {
                    $error = "Veuillez joindre une preuve de paiement.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demande de Remboursement | HireMe CV Services</title>
    <meta name="description" content="Garantie 100% transparente. Demandez un remboursement si vous n'êtes pas entièrement satisfait de nos services de rédaction de CV.">
    <meta property="og:title" content="HireMe CV Services – Garantie Satisfait ou Remboursé" />
    <meta property="og:description" content="Notre politique de remboursement transparente et automatisée. Votre satisfaction est notre priorité absolue." />
    <meta property="og:url" content="https://moncvpro.hiremeguide.com/remboursement.php" />
    <meta property="og:type" content="website" />
    <meta property="og:image" content="https://moncvpro.hiremeguide.com/assets/remboursement-preview.png" />
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:image" content="https://moncvpro.hiremeguide.com/assets/remboursement-preview.png" />
    <link rel="canonical" href="https://moncvpro.hiremeguide.com/remboursement.php" />

    <!-- Favicons -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    boxShadow: {
                        'soft': '0 10px 40px -10px rgba(0,0,0,0.05)',
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;700;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; background-color: #f8fafc; }
        .glass-panel { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); }
    </style>
    <script src="assets/js/site-language.js?v=4" defer></script>

    <!-- Shared HireMe favicon -->
    <link rel="icon" type="image/png" sizes="48x48" href="assets/favicons/favicon-48.png?v=20261011">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicons/favicon-32.png?v=20261011">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/favicons/favicon-16.png?v=20261011">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/favicons/apple-touch-icon-180.png?v=20261011">
</head>
<body class="text-gray-800 antialiased min-h-screen flex flex-col items-center py-10 px-4">

    <!-- Header -->
    <div class="w-full max-w-4xl text-center mb-8 md:mb-12 mt-4 md:mt-8">
        <a href="index.html" class="inline-block text-2xl font-black tracking-tighter text-gray-900 mb-6 hover:scale-105 transition-transform duration-300">
            HireMe<span class="text-blue-600">CV</span>
        </a>
        <h1 class="text-4xl md:text-5xl font-black text-gray-900 tracking-tight mb-4">
            Garantie <span class="bg-clip-text text-transparent bg-gradient-to-r from-blue-600 to-indigo-600">100% Transparente.</span>
        </h1>
        <p class="text-gray-500 text-lg max-w-2xl mx-auto px-4">Vous n'êtes pas satisfait ? Nous avons mis en place un processus de remboursement automatisé et transparent.</p>
    </div>

    <!-- Transparency Dashboard -->
    <div class="w-full max-w-4xl grid grid-cols-1 sm:grid-cols-2 gap-4 md:gap-6 mb-10">
        <!-- Stats -->
        <div class="bg-white rounded-3xl p-6 md:p-8 shadow-soft border border-gray-100 flex items-center justify-between hover:-translate-y-1 transition-transform duration-300">
            <div>
                <p class="text-[10px] md:text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">CV Réalisés & Approuvés</p>
                <p class="text-3xl md:text-4xl font-black text-green-500"><?= number_format($totalDelivered, 0, ',', ' ') ?></p>
            </div>
            <div class="w-14 h-14 md:w-16 md:h-16 bg-green-50 rounded-full flex items-center justify-center text-2xl md:text-3xl shadow-inner">✅</div>
        </div>
        <div class="bg-white rounded-3xl p-6 md:p-8 shadow-soft border border-gray-100 flex items-center justify-between hover:-translate-y-1 transition-transform duration-300">
            <div>
                <p class="text-[10px] md:text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Remboursements Demandés</p>
                <p class="text-3xl md:text-4xl font-black text-red-500"><?= number_format($totalRefunds, 0, ',', ' ') ?></p>
            </div>
            <div class="w-14 h-14 md:w-16 md:h-16 bg-red-50 rounded-full flex items-center justify-center text-2xl md:text-3xl shadow-inner">💸</div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="w-full max-w-4xl grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Form Section -->
        <div class="lg:col-span-2 bg-white rounded-3xl p-6 md:p-8 shadow-soft border border-gray-100 glass-panel">
            <h2 class="text-2xl font-bold mb-6 flex items-center gap-3 text-gray-800">
                <span class="bg-blue-50 text-blue-600 p-2 rounded-xl text-xl">📝</span> Formulaire de Demande
            </h2>

            <?php if($msg): ?>
                <div class="bg-green-50 border border-green-200 text-green-700 px-6 py-4 rounded-2xl font-bold mb-6 shadow-sm">
                    ✅ <?= $msg ?>
                </div>
            <?php endif; ?>

            <?php if($error): ?>
                <div class="bg-red-50 border border-red-200 text-red-600 px-6 py-4 rounded-2xl font-bold mb-6 shadow-sm">
                    ⚠️ <?= $error ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                
                <div>
                    <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-2 ml-1">Email ou WhatsApp de commande <span class="text-red-500">*</span></label>
                    <div class="flex flex-col sm:flex-row gap-2">
                        <input type="text" id="identifierInput" name="identifier" required placeholder="ex: jean@email.com ou 6xxxxxxxx" 
                            class="flex-1 bg-gray-50 border-2 border-gray-100 p-4 rounded-2xl text-sm outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/10 transition-all">
                        <button type="button" onclick="searchOrders()" class="w-full sm:w-auto bg-blue-100 text-blue-700 font-bold px-8 py-4 sm:py-0 rounded-2xl hover:bg-blue-600 hover:text-white transition-colors duration-300 text-sm shadow-sm">Chercher</button>
                    </div>
                    <p class="text-[10px] text-gray-400 mt-2 ml-1 font-medium" id="searchFeedback">Cliquez sur Chercher pour afficher vos commandes éligibles.</p>
                </div>

                <div id="ordersContainer" class="hidden space-y-3 p-5 bg-gray-50 rounded-2xl border-2 border-gray-100">
                    <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-3">Sélectionnez la commande à rembourser <span class="text-red-500">*</span></p>
                    <div id="ordersList" class="space-y-2"></div>
                </div>

                <input type="hidden" name="order_id" id="hiddenOrderId" required>
                <input type="hidden" name="amount" id="hiddenAmount" required>

                <div id="proofContainer" class="hidden space-y-6">
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-2 ml-1">Preuve de paiement <span class="text-red-500">*</span></label>
                        <input type="file" name="proof" id="proofInput" accept="image/*,.pdf" 
                            class="w-full bg-gray-50 border-2 border-gray-100 p-3 rounded-2xl text-xs outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/10 transition-all file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer">
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-2 ml-1">Motif du Remboursement <span class="text-red-500">*</span></label>
                        <textarea name="reason" required rows="3" placeholder="Expliquez-nous pourquoi vous n'êtes pas satisfait..." 
                            class="w-full bg-gray-50 border-2 border-gray-100 p-4 rounded-2xl text-sm outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/10 transition-all resize-none"></textarea>
                    </div>
                </div>

                <div class="bg-orange-50/80 border border-orange-100 p-5 rounded-2xl space-y-4 shadow-sm">
                    <label class="flex items-start gap-3 cursor-pointer group">
                        <input type="checkbox" name="contacted_admin" required class="mt-0.5 w-5 h-5 text-orange-600 rounded border-gray-300 focus:ring-orange-500 transition-all">
                        <span class="text-[13px] text-orange-900 font-medium group-hover:text-orange-700 transition-colors">J'ai d'abord contacté le service client sur WhatsApp pour tenter de résoudre mon problème avant de faire cette demande.</span>
                    </label>
                    <div class="h-px w-full bg-orange-100/50"></div>
                    <label class="flex items-start gap-3 cursor-pointer group">
                        <input type="checkbox" name="accept_ban" required class="mt-0.5 w-5 h-5 text-orange-600 rounded border-gray-300 focus:ring-orange-500 transition-all">
                        <span class="text-[13px] text-orange-900 font-medium group-hover:text-orange-700 transition-colors">J'accepte qu'en demandant un remboursement, je serai définitivement banni(e) des services HireMe.</span>
                    </label>
                </div>

                <button type="submit" class="w-full bg-gray-900 text-white font-black py-4 rounded-2xl shadow-xl hover:bg-blue-600 hover:shadow-blue-500/25 transition-all duration-300 transform hover:-translate-y-0.5 uppercase tracking-widest text-sm flex justify-center items-center gap-2">
                    Soumettre la demande
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                </button>

            </form>
        </div>

        <!-- Live Feed Section -->
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white rounded-3xl p-6 md:p-8 shadow-soft border border-gray-100 glass-panel">
                <h3 class="font-bold text-gray-800 mb-6 flex items-center gap-3 text-lg">
                    <span class="relative flex h-3 w-3">
                      <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                      <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
                    </span>
                    Activité en Direct
                </h3>
                
                <div class="space-y-4">
                    <?php if(empty($recentRefunds)): ?>
                        <p class="text-xs text-gray-500 italic text-center py-4">Aucune demande récente. Nos clients sont satisfaits ! 🎉</p>
                    <?php else: ?>
                        <?php foreach($recentRefunds as $req): 
                            $nameParts = explode(' ', trim($req['name']));
                            $maskedName = htmlspecialchars($nameParts[0]) . (isset($nameParts[1]) ? ' ' . substr($nameParts[1], 0, 1) . '.' : '');
                            $timeAgo = round((time() - strtotime($req['created_at'])) / 60);
                            $timeStr = $timeAgo < 60 ? "Il y a $timeAgo min" : "Il y a " . round($timeAgo/60) . "h";
                        ?>
                            <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-xl border border-gray-100">
                                <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center text-xs">👤</div>
                                <div>
                                    <?php if($req['status'] === 'accepted'): ?>
                                        <p class="text-xs font-bold text-green-600"><?= $maskedName ?> a été remboursé(e).</p>
                                    <?php elseif($req['status'] === 'rejected'): ?>
                                        <p class="text-xs font-bold text-red-600"><?= $maskedName ?> a vu sa demande refusée.</p>
                                    <?php else: ?>
                                        <p class="text-xs font-bold text-gray-800"><?= $maskedName ?> a demandé un remboursement.</p>
                                    <?php endif; ?>
                                    <p class="text-[10px] text-gray-400 mt-0.5"><?= $timeStr ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-3xl p-8 text-center border border-blue-100/50 shadow-sm relative overflow-hidden">
                <div class="absolute -top-10 -right-10 w-32 h-32 bg-blue-500/5 rounded-full blur-2xl"></div>
                <div class="absolute -bottom-10 -left-10 w-32 h-32 bg-indigo-500/5 rounded-full blur-2xl"></div>
                
                <div class="relative z-10">
                    <div class="w-16 h-16 mx-auto bg-white rounded-full flex items-center justify-center text-3xl shadow-sm mb-4">💬</div>
                    <h4 class="font-black text-gray-900 text-lg mb-2">Besoin d'aide ?</h4>
                    <p class="text-sm text-gray-600 mb-6 leading-relaxed">La plupart des problèmes peuvent être résolus en quelques minutes par notre équipe d'assistance.</p>
                    <a href="https://wa.me/237654250688" target="_blank" class="inline-flex w-full items-center justify-center bg-white text-gray-900 font-bold py-3.5 px-4 rounded-2xl border-2 border-gray-100 hover:border-blue-500 hover:text-blue-600 transition-all shadow-sm group">
                        Contacter le Support
                        <svg class="w-4 h-4 ml-2 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </a>
                </div>
            </div>
        </div>

    </div>

    <script>
    function searchOrders() {
        const identifier = document.getElementById('identifierInput').value.trim();
        const feedback = document.getElementById('searchFeedback');
        const ordersContainer = document.getElementById('ordersContainer');
        const ordersList = document.getElementById('ordersList');
        const proofContainer = document.getElementById('proofContainer');
        
        if(!identifier) {
            feedback.textContent = "Veuillez entrer un email ou numéro valide.";
            feedback.className = "text-[10px] text-red-500 mt-1 font-bold";
            return;
        }
        
        feedback.textContent = "Recherche en cours...";
        feedback.className = "text-[10px] text-blue-500 mt-1 font-bold";
        
        // Log the search for abandonment tracking
        fetch('api/log_refund_search.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ identifier: identifier })
        }).catch(e => console.log('Tracking error', e));
        
        fetch('api/get_orders_for_refund.php?identifier=' + encodeURIComponent(identifier))
        .then(res => res.json())
        .then(data => {
            if(!data.success) {
                feedback.textContent = data.error;
                feedback.className = "text-[10px] text-red-500 mt-1 font-bold";
                ordersContainer.classList.add('hidden');
                proofContainer.classList.add('hidden');
            } else {
                feedback.textContent = "Commandes trouvées !";
                feedback.className = "text-[10px] text-green-500 mt-1 font-bold";
                
                ordersList.innerHTML = '';
                data.orders.forEach((order, index) => {
                    const date = new Date(order.created_at).toLocaleDateString('fr-FR');
                    const html = `
                        <label class="flex items-center p-4 border-2 border-gray-100 rounded-2xl cursor-pointer hover:border-blue-400 transition-all bg-white has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50 has-[:checked]:shadow-sm group">
                            <input type="radio" name="order_selection" value="${order.id}" data-amount="${order.amount}" class="w-5 h-5 text-blue-600 border-gray-300 focus:ring-blue-500" required onchange="selectOrder(this)">
                            <div class="ml-4 w-full">
                                <div class="flex justify-between items-center w-full">
                                    <p class="text-[15px] font-bold text-gray-800 group-hover:text-blue-700 transition-colors">${order.package_name}</p>
                                    <span class="font-black text-blue-600 bg-blue-100 px-3 py-1 rounded-lg text-xs">${order.amount} FCFA</span>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Commandé le ${date}</p>
                            </div>
                        </label>
                    `;
                    ordersList.innerHTML += html;
                });
                
                // Show containers
                ordersContainer.classList.remove('hidden');
                proofContainer.classList.remove('hidden');
                // Make proof required only when shown
                document.getElementById('proofInput').required = true;
            }
        })
        .catch(err => {
            feedback.textContent = "Erreur de connexion au serveur.";
            feedback.className = "text-[10px] text-red-500 mt-1 font-bold";
        });
    }

    function selectOrder(radio) {
        document.getElementById('hiddenOrderId').value = radio.value;
        document.getElementById('hiddenAmount').value = radio.getAttribute('data-amount');
    }
    </script>
</body>
</html>
