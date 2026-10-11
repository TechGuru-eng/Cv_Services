<?php
// reservation-payment.php
require_once 'admin/config/db.php';
require_once 'admin/includes/security_helper.php';

$ref = $_GET['ref'] ?? '';

if (empty($ref)) {
    die("Référence de réservation requise.");
}

// Fetch reservation order
$stmt = $pdo->prepare("SELECT * FROM orders WHERE order_ref = ? AND order_type = 'advance' LIMIT 1");
$stmt->execute([$ref]);
$order = $stmt->fetch();

if (!$order) {
    die("Réservation introuvable ou invalide.");
}

// Calculate the 50% deposit amount
$totalAmount = $order['amount'];
$depositAmount = $totalAmount / 2;

// Security Check: Blacklist check for safety
if (isBlacklisted($order['whatsapp'], $pdo) || isBlacklisted($order['email'], $pdo)) {
    die("Désolé, votre accès aux services HireMe a été restreint. Veuillez contacter le support.");
}

$errorMsg = '';
$success = false;

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['proofFile'])) {
    $transactionId = htmlspecialchars(trim($_POST['transactionId'] ?? ''));
    $paymentMethod = htmlspecialchars(trim($_POST['paymentMethod'] ?? ''));

    if (empty($transactionId) || empty($paymentMethod)) {
        $errorMsg = "Veuillez remplir tous les champs obligatoires.";
    } else {
        // Validate File Upload
        $file = $_FILES['proofFile'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errorMsg = "Erreur lors du téléversement du fichier. Code: " . $file['error'];
        } else {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
            $maxFileSize = 5 * 1024 * 1024; // 5MB

            if (!in_array($ext, $allowedExtensions)) {
                $errorMsg = "Format de fichier invalide. Seuls les formats JPG, JPEG, PNG, GIF et PDF sont autorisés.";
            } elseif ($file['size'] > $maxFileSize) {
                $errorMsg = "Le fichier est trop volumineux. La taille maximale autorisée est de 5 Mo.";
            } else {
                // Secure Directory Creation
                $uploadDir = 'uploads/proofs/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                // Generate Unique Secure Filename
                $newFilename = 'RSV_PROOF_' . $order['order_ref'] . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
                $destPath = $uploadDir . $newFilename;

                if (move_uploaded_file($file['tmp_name'], $destPath)) {
                    // Update Order details
                    $stmtUpd = $pdo->prepare("UPDATE orders SET 
                        payment_method = ?, 
                        transaction_number = ?, 
                        payment_proof = ?, 
                        payment_status = 'pending' 
                        WHERE id = ?");
                    $stmtUpd->execute([$paymentMethod, $transactionId, $destPath, $order['id']]);

                    // Redirect to final thank-you page
                    header("Location: merci-reservation.html?name=" . urlencode($order['name']));
                    exit;
                } else {
                    $errorMsg = "Erreur lors du déplacement du fichier vers le dossier de destination.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">

<head>
<!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-0WXT4Q08R2"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());

      gtag('config', 'G-0WXT4Q08R2');
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paiement de l'Acompte – HireMe CV</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] },
                    colors: { primary: '#0ea5e9', accent: '#22c55e', danger: '#ef4444', neutral: '#111827', bg: '#f8fafc' }
                }
            }
        }
    </script>
    <script src="assets/js/site-language.js?v=4" defer></script>

    <!-- Shared HireMe favicon -->
    <link rel="icon" type="image/png" sizes="48x48" href="assets/favicons/favicon-48.png?v=20261011">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicons/favicon-32.png?v=20261011">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/favicons/favicon-16.png?v=20261011">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/favicons/apple-touch-icon-180.png?v=20261011">
</head>

<body class="bg-bg text-neutral antialiased font-sans flex flex-col min-h-screen">
    <header class="bg-white border-b border-gray-100 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 h-16 flex items-center justify-between">
            <a href="index.html" class="text-2xl font-extrabold text-primary tracking-tight">
                HireMe<span class="text-neutral">CV</span>
            </a>
            <div class="hidden md:flex items-center space-x-4 text-sm font-medium text-gray-500">
                <span>Besoin d'aide ?</span>
                <a href="https://wa.me/237654250688" class="text-accent font-bold">WhatsApp: 654 250 688</a>
            </div>
        </div>
    </header>

    <main class="flex-grow py-12 px-4 sm:px-6">
        <div class="max-w-4xl mx-auto">
            
            <div class="text-center mb-12">
                <span class="inline-block bg-blue-50 text-primary font-bold px-3 py-1 rounded-full text-xs mb-3">
                    Réservation #<?= htmlspecialchars($order['order_ref']) ?>
                </span>
                <h1 class="text-3xl sm:text-4xl font-extrabold text-neutral mb-4">Paiement de l'Acompte (50%)</h1>
                <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                    Pour valider et démarrer le traitement de votre réservation, veuillez payer l'acompte de 50% ci-dessous, puis soumettre votre preuve de paiement.
                </p>
                <div class="mt-6 bg-blue-50 text-blue-800 px-6 py-4 rounded-2xl inline-block border border-blue-100 shadow-sm">
                    <div class="text-xs uppercase tracking-wider font-semibold opacity-75">Acompte à payer :</div>
                    <div class="text-3xl font-black mt-1"><?= number_format($depositAmount, 0, ',', ' ') ?> FCFA</div>
                    <div class="text-[10px] text-gray-500 mt-1">Montant total dû à terme : <?= number_format($totalAmount, 0, ',', ' ') ?> FCFA</div>
                </div>
            </div>

            <!-- Error Banner -->
            <?php if (!empty($errorMsg)): ?>
                <div class="bg-red-50 border border-red-200 text-danger px-4 py-3 rounded-xl mb-8 text-sm font-semibold max-w-2xl mx-auto flex items-center gap-2">
                    <span>⚠️</span> <?= htmlspecialchars($errorMsg) ?>
                </div>
            <?php endif; ?>

            <!-- Payment Methods -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-12 max-w-3xl mx-auto">
                <!-- MTN -->
                <div class="bg-white p-6 sm:p-8 rounded-[2rem] shadow-2xl shadow-yellow-500/5 border border-gray-50 border-t-8 border-t-yellow-400 hover:shadow-yellow-500/10 transition-all duration-300 transform hover:-translate-y-1">
                    <div class="flex flex-col items-center text-center">
                        <img src="assets/img/mtn_momo_logo.png" alt="MTN" class="h-12 mx-auto object-contain mb-4">
                        <h3 class="text-xl font-black text-slate-800 mb-1">Paiement MTN MoMo</h3>
                        <p class="text-[10px] font-bold text-yellow-600 uppercase tracking-widest mb-6">Instantané • 0 Frais</p>
                        
                        <div class="bg-slate-50 border border-slate-100 p-4 rounded-2xl mb-4 w-full">
                            <p class="text-xs text-slate-500 mb-3">Cliquez ci-dessous pour composer :</p>
                            <a href="tel:*126*14*654250688*<?= $depositAmount ?>%23" class="group relative block w-full bg-white border-2 border-yellow-400 rounded-xl p-4 shadow-lg hover:shadow-xl hover:scale-[1.02] transition-all cursor-pointer overflow-hidden text-center">
                                <div class="absolute top-0 right-0 bg-yellow-400 text-yellow-900 text-[10px] font-black px-3 py-1 rounded-bl-lg animate-pulse tracking-tighter">
                                    COMPOSER MAINTENANT
                                </div>
                                <p class="font-mono font-black text-lg text-slate-800 break-all group-hover:text-yellow-700 transition-colors mt-2">
                                    *126*14*654250688*<span class="text-primary"><?= $depositAmount ?></span>#
                                </p>
                            </a>
                        </div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-tighter">Bénéficiaire : NJAI NDINTEK HANDSON</p>
                    </div>
                </div>

                <!-- Orange -->
                <div class="bg-white p-6 sm:p-8 rounded-[2rem] shadow-2xl shadow-orange-500/5 border border-gray-50 border-t-8 border-t-orange-500 hover:shadow-orange-500/10 transition-all duration-300 transform hover:-translate-y-1">
                    <div class="flex flex-col items-center text-center">
                        <img src="assets/img/orange_money_logo.png" alt="Orange" class="h-12 mx-auto object-contain mb-4">
                        <h3 class="text-xl font-black text-slate-800 mb-1">Paiement Orange Money</h3>
                        <p class="text-[10px] font-bold text-orange-600 uppercase tracking-widest mb-6">Simple • Sécurisé</p>
                        
                        <div class="bg-slate-50 border border-slate-100 p-4 rounded-2xl mb-4 w-full">
                            <p class="text-xs text-slate-500 mb-3">Cliquez ci-dessous pour composer :</p>
                            <a href="tel:%23150*1*1*659197506*<?= $depositAmount ?>%23" class="group relative block w-full bg-white border-2 border-orange-500 rounded-xl p-4 shadow-lg hover:shadow-xl hover:scale-[1.02] transition-all cursor-pointer overflow-hidden text-center">
                                <div class="absolute top-0 right-0 bg-orange-500 text-white text-[10px] font-black px-3 py-1 rounded-bl-lg animate-pulse tracking-tighter">
                                    COMPOSER MAINTENANT
                                </div>
                                <p class="font-mono font-black text-lg text-slate-800 break-all group-hover:text-orange-600 transition-colors mt-2">
                                    #150*1*1*659197506*<span class="text-primary"><?= $depositAmount ?></span>#
                                </p>
                            </a>
                        </div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-tighter">Bénéficiaire : NJAI NDINTEK HANDSON</p>
                    </div>
                </div>
            </div>

            <!-- Confirmation Form -->
            <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100 max-w-2xl mx-auto">
                <div class="bg-neutral py-6 px-8">
                    <h2 class="text-xl font-bold text-white flex items-center">
                        <svg class="w-6 h-6 mr-3 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Confirmer mon paiement d'acompte
                    </h2>
                </div>
                <form method="POST" enctype="multipart/form-data" class="p-8 space-y-6">
                    <div>
                        <span class="block text-sm font-medium text-gray-700 mb-2">Méthode de paiement utilisée</span>
                        <div class="grid grid-cols-2 gap-4">
                            <label class="flex items-center space-x-3 p-4 border border-gray-200 rounded-xl cursor-pointer hover:bg-gray-50 transition-colors">
                                <input type="radio" name="paymentMethod" value="MTN" required class="h-5 w-5 text-primary focus:ring-primary border-gray-300">
                                <span class="font-bold text-gray-900">MTN MoMo</span>
                            </label>
                            <label class="flex items-center space-x-3 p-4 border border-gray-200 rounded-xl cursor-pointer hover:bg-gray-50 transition-colors">
                                <input type="radio" name="paymentMethod" value="Orange" class="h-5 w-5 text-primary focus:ring-primary border-gray-300">
                                <span class="font-bold text-gray-900">Orange Money</span>
                            </label>
                        </div>
                    </div>
                    
                    <div>
                        <label for="transactionId" class="block text-sm font-medium text-gray-700 mb-1">Numéro de transaction (ID)</label>
                        <input type="text" id="transactionId" name="transactionId" required placeholder="Ex: 2356894125" class="w-full rounded-lg border-gray-300 border p-3 focus:ring-primary focus:border-primary">
                    </div>

                    <div>
                        <label for="proofFile" class="block text-sm font-medium text-gray-700 mb-1">Preuve de paiement (Capture d'écran)</label>
                        <input type="file" id="proofFile" name="proofFile" accept="image/*,application/pdf" required class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-primary hover:file:bg-blue-100 transition-colors">
                        <p class="text-xs text-gray-400 mt-1">Formats acceptés : JPG, JPEG, PNG, GIF, PDF. Max 5 Mo.</p>
                    </div>

                    <label class="flex items-center p-4 bg-slate-50 rounded-xl border border-slate-100 group cursor-pointer active:bg-slate-100 transition-colors">
                        <input id="confirmPay" type="checkbox" required class="h-6 w-6 text-accent focus:ring-accent border-gray-300 rounded cursor-pointer">
                        <span class="ml-4 block text-sm font-bold text-slate-700 select-none">
                            Je confirme avoir payé l'acompte de <?= number_format($depositAmount, 0, ',', ' ') ?> FCFA.
                        </span>
                    </label>

                    <button type="submit" class="w-full mt-6 bg-accent text-white font-bold py-4 rounded-xl shadow-lg hover:bg-green-600 transition-all transform hover:scale-[1.01] flex justify-center items-center text-lg">
                        <span>Soumettre mon paiement</span>
                        <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </button>
                </form>
            </div>
            
        </div>
    </main>

    <footer class="bg-white border-t border-gray-100 py-8 mt-auto">
        <div class="max-w-7xl mx-auto px-4 text-center text-sm text-gray-400">
            <p>HireMe CV Services — L'excellence au service de votre carrière.</p>
            <p class="text-xs text-gray-300 mt-2">&copy; <?= date('Y') ?> Tous droits réservés.</p>
        </div>
    </footer>
</body>

</html>
