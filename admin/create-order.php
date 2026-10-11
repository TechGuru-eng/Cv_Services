<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}
require_once 'config/db.php';
require_once 'includes/security_helper.php';

$error = null;
$warning = null;
$success = null;

// AJAX check for blacklist (Simple implementation for now)
if (isset($_GET['check_blacklist'])) {
    $id = $_GET['check_blacklist'];
    if (isBlacklisted($id, $pdo)) {
        echo json_encode(['blacklisted' => true]);
    } else {
        echo json_encode(['blacklisted' => false]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Gather Input
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $whatsapp = trim($_POST['whatsapp'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $package = $_POST['package'] ?? 'pro';
    $amount = floatval($_POST['amount'] ?? 0);
    $paymentMethod = $_POST['payment_method'] ?? 'Manuelle';
    $paymentStatus = $_POST['payment_status'] ?? 'pending';
    $transactionId = trim($_POST['transaction_id'] ?? '');

    // Validation
    if (empty($name) || empty($email)) {
        $error = "Nom et Email sont obligatoires.";
    } else {
        // 2. Generate Reference
        $orderRef = 'CMD-' . strtoupper(substr(uniqid(), -6));
        
        // Map package to human name if needed, though we store the code usually or the name. 
        // In this DB schema, it seems to store the display name or code. Let's store the display name for consistency with index.html or what save-order.php does.
        // Actually save-order.php likely stores the readable name. 
        $packageNames = [
            'etudiant' => 'Pack Étudiant',
            'pro' => 'Pack Pro',
            'senior' => 'Pack Senior',
            'expert' => 'Pack Expert',
            'templates40' => 'Pack 40 Modèles'
        ];
        $packageName = $packageNames[$package] ?? $package;

        $promoCode = trim($_POST['promo_code'] ?? '');
        $affiliateCode = trim($_POST['affiliate_code'] ?? '');

        $pdo->beginTransaction();
        try {
            // 3. Insert Order
            $stmt = $pdo->prepare("INSERT INTO orders (order_ref, name, email, whatsapp, country, package_name, amount, original_amount, payment_method, payment_status, transaction_number, created_at, promo_code_used, affiliate_code) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?)");
            $stmt->execute([
                $orderRef, 
                $name, 
                $email, 
                $whatsapp, 
                $country, 
                $packageName, 
                $amount, 
                $amount, 
                $paymentMethod, 
                $paymentStatus, 
                $transactionId,
                $promoCode,
                $affiliateCode
            ]);
            $orderId = $pdo->lastInsertId();

            // 4. Handle Revenue (if verified)
            if ($paymentStatus === 'verified') {
                $stmtRev = $pdo->prepare("INSERT INTO revenue (order_id, amount, payment_method, created_at) VALUES (?, ?, ?, NOW())");
                $stmtRev->execute([$orderId, $amount, $paymentMethod]);

                // 4b. Handle Affiliate Commission (New Logic)
                if (!empty($affiliateCode)) {
                    $stmtAff = $pdo->prepare("SELECT id FROM affiliates WHERE referral_code = ?");
                    $stmtAff->execute([$affiliateCode]);
                    $affiliate = $stmtAff->fetch();

                    if ($affiliate) {
                        $affId = $affiliate['id'];
                        // Add 500 FCFA Commission
                        $pdo->prepare("UPDATE affiliates SET balance = balance + 500, total_earned = total_earned + 500 WHERE id = ?")->execute([$affId]);
                        $pdo->prepare("INSERT INTO affiliate_referrals (affiliate_id, order_id, commission_amount, status) VALUES (?, ?, 500, 'verified')")->execute([$affId, $orderId]);
                    }
                }
            }

            // 5. Create empty CV Details to avoid errors in details view
            $stmtCv = $pdo->prepare("INSERT INTO cv_details (order_id, personal_info, created_at) VALUES (?, ?, NOW())");
            // Create a basic JSON for personal info
            $jsonInfo = json_encode([
                'name' => $name,
                'email' => $email,
                'phone' => $whatsapp,
                'city' => $country
            ]);
            $stmtCv->execute([$orderId, $jsonInfo]);

            $pdo->commit();
            header("Location: order-details.php?id=$orderId&msg=created");
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Erreur: " . $e->getMessage();
        }
    }
}
?>
<?php include 'includes/header.php'; ?>
<body class="bg-gray-50">
    <div class="flex h-screen overflow-hidden">
        <?php include 'includes/nav.php'; ?>
        
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
            <div class="max-w-3xl mx-auto">
                <a href="orders.php" class="text-gray-500 hover:text-gray-700 mb-4 inline-block">← Retour Commandes</a>
                
                <div class="bg-white shadow-md rounded-lg p-6">
                    <h1 class="text-2xl font-bold text-gray-800 mb-6">Créer une Commande Manuelle</h1>
                    
                    <?php if ($error): ?>
                        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                            <p><?= htmlspecialchars($error) ?></p>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="space-y-6">
                        <!-- Client Info -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Nom Complet</label>
                                <input type="text" name="name" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 bg-gray-50 focus:bg-white focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Email</label>
                                <input type="email" name="email" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 bg-gray-50 focus:bg-white focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">WhatsApp</label>
                                <input type="text" name="whatsapp" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 bg-gray-50 focus:bg-white focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Pays / Ville</label>
                                <input type="text" name="country" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 bg-gray-50 focus:bg-white focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>

                        <hr>

                        <!-- Order Details -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Pack</label>
                                <select name="package" id="package" onchange="updateAmount()" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 bg-white focus:ring-blue-500 focus:border-blue-500">
                                    <option value="etudiant" data-price="5000">Pack Étudiant</option>
                                    <option value="pro" data-price="12000" selected>Pack Pro</option>
                                    <option value="senior" data-price="30000">Pack Senior</option>
                                    <option value="expert" data-price="90000">Pack Expert</option>
                                    <option value="templates40" data-price="3999">Pack 40 Modèles</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Montant (FCFA)</label>
                                <input type="number" name="amount" id="amount" value="12000" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 bg-yellow-50 focus:bg-white focus:ring-blue-500 focus:border-blue-500 font-bold text-gray-900">
                                <p class="text-xs text-gray-500 mt-1">Modifiable manuellement</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Méthode de Paiement</label>
                                <select name="payment_method" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 bg-white focus:ring-blue-500 focus:border-blue-500">
                                    <option value="WhatsApp">WhatsApp (Direct)</option>
                                    <option value="Om">Orange Money</option>
                                    <option value="Mtn">MTN Mobile Money</option>
                                    <option value="Cash">Espèces</option>
                                    <option value="Virement">Virement Bancaire</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Statut Paiement</label>
                                <select name="payment_status" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 bg-white focus:ring-blue-500 focus:border-blue-500">
                                    <option value="verified" selected>✅ Payé (Vérifié)</option>
                                    <option value="pending">⏳ En attente</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                 <label class="block text-sm font-medium text-gray-700">Code Promo (Optionnel)</label>
                                 <input type="text" name="promo_code" placeholder="Ex: HIREME60" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 bg-gray-50 focus:bg-white uppercase">
                            </div>
                            <div>
                                 <label class="block text-sm font-medium text-gray-700">Code Parrain / Ambassadeur</label>
                                 <input type="text" name="affiliate_code" placeholder="Ex: AMB-JEAN" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 bg-pink-50 focus:bg-white border-pink-200 text-pink-700 font-bold uppercase">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">ID Transaction (Optionnel)</label>
                            <input type="text" name="transaction_id" placeholder="Ex: TX-123456" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 bg-gray-50 focus:bg-white focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <div class="flex justify-end pt-4">
                            <button type="submit" id="submitBtn" class="bg-blue-600 text-white px-6 py-2 rounded shadow hover:bg-blue-700 transition font-bold">
                                Créer la Commande
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        function updateAmount() {
            const select = document.getElementById('package');
            const amountInput = document.getElementById('amount');
            const price = select.options[select.selectedIndex].getAttribute('data-price');
            amountInput.value = price;
        }

        // Blacklist Check
        async function checkBlacklist(val, type) {
            if (!val || val.length < 5) return;
            try {
                const response = await fetch(`create-order.php?check_blacklist=${val}`);
                const data = await response.json();
                const warningId = `warning-${type}`;
                let warningEl = document.getElementById(warningId);
                
                if (data.blacklisted) {
                    if (!warningEl) {
                        warningEl = document.createElement('div');
                        warningEl.id = warningId;
                        warningEl.className = 'text-red-600 text-[10px] font-bold mt-1 animate-pulse';
                        warningEl.innerHTML = '⚠️ ATTENTION : Cet identifiant est sur liste noire (Non-payeur) !';
                        document.querySelector(`input[name="${type}"]`).parentNode.appendChild(warningEl);
                    }
                } else {
                    if (warningEl) warningEl.remove();
                }
            } catch (e) {
                console.error("Check failed", e);
            }
        }

        document.querySelector('input[name="whatsapp"]').addEventListener('blur', (e) => checkBlacklist(e.target.value, 'whatsapp'));
        document.querySelector('input[name="email"]').addEventListener('blur', (e) => checkBlacklist(e.target.value, 'email'));
    </script>
</body>
</html>
