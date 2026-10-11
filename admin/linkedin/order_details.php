<?php
// admin/linkedin/order_details.php
// Admin Order Details & Deliverables Management

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../includes/order-notifications.php';
require_once __DIR__ . '/../includes/csrf.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') verify_csrf();

$orderId = (int)($_GET['id'] ?? 0);
if ($orderId <= 0) {
    header('Location: commandes.php');
    exit;
}

$message = '';
$error = '';

// Handle Actions (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_GET['action'] ?? $_POST['action'] ?? '';

    // 1. Confirm Payment
    if ($action === 'confirm_payment') {
        $pdo->beginTransaction();
        try {
        $locked = $pdo->prepare('SELECT * FROM linkedin_orders WHERE id = ? FOR UPDATE');
        $locked->execute([$orderId]);
        $approvalOrder = $locked->fetch();
        if (!$approvalOrder) throw new RuntimeException('Commande introuvable.');
        $stmt = $pdo->prepare("UPDATE linkedin_orders SET payment_status = 'CONFIRMED', order_status = 'PAIEMENT_CONFIRME' WHERE id = ?");
        $stmt->execute([$orderId]);

        $stmtPay = $pdo->prepare("UPDATE linkedin_payments SET status = 'CONFIRMED' WHERE order_id = ?");
        $stmtPay->execute([$orderId]);

        $stmtHist = $pdo->prepare("INSERT INTO linkedin_order_history (order_id, action, note, created_by) VALUES (?, 'PAIEMENT_VALIDE', 'Paiement validé par l\'administrateur', 'Admin')");
        $stmtHist->execute([$orderId]);

        queue_order_event($pdo, 'linkedin', $approvalOrder, 'approved');
        $pdo->commit();
        send_order_notifications($pdo);
        $message = "Paiement confirmé avec succès !";
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $error = 'La confirmation a échoué. Veuillez réessayer.';
        }
    }

    // 2. Reject Payment
    elseif ($action === 'reject_payment') {
        $reason = trim($_POST['reject_reason'] ?? 'Preuve de paiement non valide ou introuvable');
        $stmt = $pdo->prepare("UPDATE linkedin_orders SET payment_status = 'REJECTED' WHERE id = ?");
        $stmt->execute([$orderId]);

        $stmtPay = $pdo->prepare("UPDATE linkedin_payments SET status = 'REJECTED', notes = ? WHERE order_id = ?");
        $stmtPay->execute([$reason, $orderId]);

        $stmtHist = $pdo->prepare("INSERT INTO linkedin_order_history (order_id, action, note, created_by) VALUES (?, 'PAIEMENT_REJETE', ?, 'Admin')");
        $stmtHist->execute([$orderId, 'Paiement rejeté: ' . $reason]);

        $message = "Paiement marqué comme rejeté.";
    }

    // 3. Update Order Status
    elseif ($action === 'update_status') {
        $newStatus = trim($_POST['order_status'] ?? '');
        if ($newStatus) {
            $stmt = $pdo->prepare("UPDATE linkedin_orders SET order_status = ? WHERE id = ?");
            $stmt->execute([$newStatus, $orderId]);

            $stmtHist = $pdo->prepare("INSERT INTO linkedin_order_history (order_id, action, note, created_by) VALUES (?, 'CHANGEMENT_STATUT', ?, 'Admin')");
            $stmtHist->execute([$orderId, "Statut changé pour : $newStatus"]);

            $message = "Statut de la commande mis à jour ($newStatus).";
        }
    }

    // 4. Save Internal Note
    elseif ($action === 'save_notes') {
        $notes = trim($_POST['admin_notes'] ?? '');
        $stmt = $pdo->prepare("UPDATE linkedin_orders SET admin_notes = ? WHERE id = ?");
        $stmt->execute([$notes, $orderId]);
        $message = "Notes internes enregistrées.";
    }

    // 5. Upload Deliverable File
    elseif ($action === 'upload_deliverable') {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if (empty($title)) {
            $error = "Le titre du livrable est obligatoire.";
        } elseif (isset($_FILES['deliverable_file']) && $_FILES['deliverable_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['deliverable_file'];
            $uploadDir = __DIR__ . '/../../uploads/linkedin/deliverables/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowedExts = ['pdf', 'doc', 'docx', 'txt', 'png', 'jpg', 'jpeg', 'zip'];
            if (!in_array($ext, $allowedExts)) {
                $error = "Format de fichier non autorisé pour les livrables.";
            } elseif ($file['size'] > 25 * 1024 * 1024) {
                $error = "Taille maximale dépassée (25 Mo).";
            } else {
                $safeName = 'DELIV_' . $orderId . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                $destPath = $uploadDir . $safeName;
                if (move_uploaded_file($file['tmp_name'], $destPath)) {
                    $relPath = 'uploads/linkedin/deliverables/' . $safeName;
                    $stmtDeliv = $pdo->prepare("INSERT INTO linkedin_deliverables (order_id, title, description, file_path, file_type, file_size) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmtDeliv->execute([$orderId, $title, $description, $relPath, $ext, (int)$file['size']]);

                    // Automatically update order status to TERMINE if first deliverable
                    $stmtAuto = $pdo->prepare("UPDATE linkedin_orders SET order_status = 'TERMINE' WHERE id = ?");
                    $stmtAuto->execute([$orderId]);

                    $stmtHist = $pdo->prepare("INSERT INTO linkedin_order_history (order_id, action, note, created_by) VALUES (?, 'LIVRABLE_DEPOSE', ?, 'Admin')");
                    $stmtHist->execute([$orderId, "Livrable ajouté : $title ($ext)"]);

                    $message = "Livrable ajouté avec succès ! La commande est désormais marquée comme Terminée.";
                } else {
                    $error = "Erreur lors du transfert du fichier.";
                }
            }
        } else {
            $error = "Veuillez sélectionner un fichier à télécharger.";
        }
    }
}

// Fetch Order Details
$stmt = $pdo->prepare("SELECT o.*, p.name as package_name, p.price as package_price 
                       FROM linkedin_orders o 
                       LEFT JOIN linkedin_packages p ON o.package_id = p.id 
                       WHERE o.id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    die("Commande non trouvée.");
}

// Fetch Order Line Items (Historical price snapshots)
$stmtItems = $pdo->prepare("SELECT * FROM linkedin_order_items WHERE order_id = ?");
$stmtItems->execute([$orderId]);
$orderItems = $stmtItems->fetchAll();

// Fetch Addons (Legacy fallback)
$stmtAdd = $pdo->prepare("SELECT a.name, a.price FROM linkedin_order_addons oa JOIN linkedin_addons a ON oa.addon_id = a.id WHERE oa.order_id = ?");
$stmtAdd->execute([$orderId]);
$addons = $stmtAdd->fetchAll();

// Fetch Payment Details
$stmtPay = $pdo->prepare("SELECT * FROM linkedin_payments WHERE order_id = ? ORDER BY id DESC LIMIT 1");
$stmtPay->execute([$orderId]);
$payment = $stmtPay->fetch();

// Fetch Uploads (CV)
$stmtUp = $pdo->prepare("SELECT * FROM linkedin_uploads WHERE order_id = ?");
$stmtUp->execute([$orderId]);
$uploads = $stmtUp->fetchAll();

// Fetch Deliverables
$stmtDeliv = $pdo->prepare("SELECT * FROM linkedin_deliverables WHERE order_id = ? ORDER BY id DESC");
$stmtDeliv->execute([$orderId]);
$deliverables = $stmtDeliv->fetchAll();

// Fetch History
$stmtHist = $pdo->prepare("SELECT * FROM linkedin_order_history WHERE order_id = ? ORDER BY id ASC");
$stmtHist->execute([$orderId]);
$history = $stmtHist->fetchAll();

$waMsg = urlencode("Bonjour " . $order['first_name'] . ", nous avons bien reçu votre commande d'optimisation LinkedIn (Réf: " . $order['reference'] . "). Notre équipe traite votre dossier.");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<base href="<?= htmlspecialchars(rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/') . '/', ENT_QUOTES, 'UTF-8') ?>">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commande <?= htmlspecialchars($order['reference']) ?> | Admin HireMe</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../assets/css/linkedin-admin.css?v=20261010">
    <!-- Shared HireMe favicon -->
    <link rel="icon" type="image/png" sizes="48x48" href="../../assets/favicons/favicon-48.png?v=20261011">
    <link rel="icon" type="image/png" sizes="32x32" href="../../assets/favicons/favicon-32.png?v=20261011">
    <link rel="icon" type="image/png" sizes="16x16" href="../../assets/favicons/favicon-16.png?v=20261011">
    <link rel="apple-touch-icon" sizes="180x180" href="../../assets/favicons/apple-touch-icon-180.png?v=20261011">
</head>
<body class="bg-slate-50 text-slate-900 font-sans antialiased min-h-screen">

    <div class="flex">
        <!-- Sidebar -->
        <?php require __DIR__ . '/navigation.php'; ?>

        <!-- Main Content -->
        <main class="flex-1 p-6 md:p-10 space-y-8 overflow-x-hidden">
            
            <!-- Alert Notifications -->
            <?php if ($message): ?>
            <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-700 text-xs font-bold flex items-center gap-2">
                <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($message) ?>
            </div>
            <?php endif; ?>
            <?php if ($error): ?>
            <div class="p-4 rounded-xl bg-red-500/10 border border-red-500/30 text-red-400 text-xs font-bold flex items-center gap-2">
                <i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center border-b border-slate-200 pb-6 gap-4">
                <div>
                    <span class="text-xs text-slate-500">Fiche de Commande LinkedIn</span>
                    <h1 class="text-3xl font-mono font-black text-amber-700"><?= htmlspecialchars($order['reference']) ?></h1>
                </div>

                <div class="flex items-center gap-3">
                    <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $order['phone']) ?>?text=<?= $waMsg ?>" target="_blank" class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs px-4 py-2.5 rounded-xl flex items-center gap-2">
                        <i class="fa-brands fa-whatsapp text-base"></i> Contacter le Client sur WhatsApp
                    </a>
                </div>
            </div>

            <!-- Details Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- Left Column (Client & Objective Info) -->
                <div class="lg:col-span-2 space-y-6">
                    
                    <!-- Client Details Card -->
                    <div class="bg-white border border-slate-200 rounded-2xl p-6 space-y-4">
                        <h2 class="text-sm font-bold text-amber-700 uppercase tracking-wider">Profil Client</h2>
                        <div class="grid grid-cols-2 gap-4 text-xs">
                            <div><span class="text-gray-500 block">Nom complet:</span><strong class="text-slate-900 text-sm"><?= htmlspecialchars($order['first_name'] . ' ' . $order['name']) ?></strong></div>
                            <div><span class="text-gray-500 block">Téléphone / WhatsApp:</span><strong class="text-slate-900 text-sm"><?= htmlspecialchars($order['phone']) ?></strong></div>
                            <div><span class="text-gray-500 block">Email:</span><strong class="text-slate-900"><?= htmlspecialchars($order['email']) ?></strong></div>
                            <div><span class="text-gray-500 block">Ville & Pays:</span><strong class="text-slate-900"><?= htmlspecialchars($order['city'] . ', ' . $order['country']) ?></strong></div>
                            <div><span class="text-gray-500 block">Situation pro actuelle:</span><strong class="text-slate-900"><?= htmlspecialchars($order['position_status']) ?></strong></div>
                            <div><span class="text-gray-500 block">Profil visé:</span><strong class="text-slate-900"><?= htmlspecialchars($order['target_profile_type'] ?: 'Non spécifié') ?></strong></div>
                        </div>

                        <div class="pt-4 border-t border-slate-200">
                            <span class="text-gray-500 text-xs block mb-1">Profil LinkedIn Transmis :</span>
                            <a href="<?= htmlspecialchars($order['linkedin_url']) ?>" target="_blank" class="inline-flex items-center gap-2 bg-blue-600/20 text-blue-700 font-bold text-xs px-4 py-2 rounded-xl border border-blue-500/30 hover:bg-blue-600/30">
                                <i class="fa-brands fa-linkedin text-lg"></i>
                                <span><?= htmlspecialchars($order['linkedin_url']) ?></span>
                                <i class="fa-solid fa-arrow-up-right-from-square text-xs ml-1"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Career Objective Card -->
                    <div class="bg-white border border-slate-200 rounded-2xl p-6 space-y-4">
                        <h2 class="text-sm font-bold text-amber-700 uppercase tracking-wider">Objectif Professionnel & Candidature</h2>
                        <div class="grid grid-cols-2 gap-4 text-xs">
                            <div><span class="text-gray-500 block">Objectif principal:</span><strong class="text-slate-900"><?= htmlspecialchars($order['main_objective']) ?></strong></div>
                            <div><span class="text-gray-500 block">Poste recherché:</span><strong class="text-slate-900"><?= htmlspecialchars($order['target_job']) ?></strong></div>
                            <div><span class="text-gray-500 block">Secteur d'activité:</span><strong class="text-slate-900"><?= htmlspecialchars($order['target_sector']) ?></strong></div>
                            <div><span class="text-gray-500 block">Niveau d'expérience:</span><strong class="text-slate-900"><?= htmlspecialchars($order['experience_level']) ?></strong></div>
                            <div><span class="text-gray-500 block">Entreprises ciblées:</span><strong class="text-slate-900"><?= htmlspecialchars($order['target_companies'] ?: 'Aucune') ?></strong></div>
                            <div><span class="text-gray-500 block">Villes ciblées:</span><strong class="text-slate-900"><?= htmlspecialchars($order['target_locations']) ?></strong></div>
                        </div>

                        <?php if ($order['career_goals']): ?>
                        <div class="pt-4 border-t border-slate-200 text-xs">
                            <span class="text-gray-500 block font-bold mb-1">Notes complémentaires du client :</span>
                            <p class="p-3 bg-slate-50 rounded-xl text-slate-700 italic border border-slate-200"><?= nl2br(htmlspecialchars($order['career_goals'])) ?></p>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Uploads (CV Transmis) -->
                    <div class="bg-white border border-slate-200 rounded-2xl p-6 space-y-4">
                        <h2 class="text-sm font-bold text-amber-700 uppercase tracking-wider">CV Transmis par le Client</h2>
                        <?php if (empty($uploads)): ?>
                        <p class="text-xs text-gray-500 italic">Aucun CV téléchargé.</p>
                        <?php else: ?>
                        <div class="space-y-2">
                            <?php foreach ($uploads as $up): ?>
                            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 flex justify-between items-center text-xs">
                                <div class="flex items-center gap-3">
                                    <i class="fa-solid fa-file-pdf text-amber-700 text-lg"></i>
                                    <div>
                                        <strong class="text-slate-900 block"><?= htmlspecialchars($up['file_name']) ?></strong>
                                        <span class="text-[10px] text-gray-500"><?= number_format($up['file_size']/1024, 1) ?> KB</span>
                                    </div>
                                </div>
                                <a href="../../api/linkedin/download_file.php?type=upload&id=<?= $up['id'] ?>" class="bg-slate-100 hover:bg-gray-700 text-amber-700 font-bold px-3 py-1.5 rounded text-xs">
                                    Télécharger CV
                                </a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- DELIVERABLES MANAGER (Upload final output for customer) -->
                    <div class="bg-white border border-slate-200 rounded-2xl p-6 space-y-6">
                        <div class="flex justify-between items-center border-b border-slate-200 pb-4">
                            <h2 class="text-sm font-bold text-amber-700 uppercase tracking-wider flex items-center gap-2">
                                <i class="fa-solid fa-upload"></i> Déposer un Livrable Optimisé pour le Client
                            </h2>
                        </div>

                        <!-- Add Deliverable Form -->
                        <form action="order_details.php?id=<?= $orderId ?>&action=upload_deliverable" method="POST" enctype="multipart/form-data" class="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-4"><?php csrf_field(); ?>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Titre du livrable *</label>
                                <input type="text" name="title" required placeholder="Ex: Rapport d'optimisation LinkedIn + Textes réécrits" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-xs text-slate-900 focus:outline-none focus:border-yellow-400">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Description / Consignes</label>
                                <input type="text" name="description" placeholder="Ex: Veuillez trouver ci-joint vos textes optimisés et votre bannière HD." class="w-full bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-xs text-slate-900 focus:outline-none focus:border-yellow-400">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Fichier (PDF, DOCX, PNG, ZIP - Max 25 Mo) *</label>
                                <input type="file" name="deliverable_file" required class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-gray-800 file:text-yellow-400 hover:file:bg-gray-700">
                            </div>
                            <button type="submit" class="bg-yellow-400 hover:bg-yellow-300 text-gray-950 font-black text-xs px-6 py-2.5 rounded-xl shadow">
                                Enregistrer et transmettre au client
                            </button>
                        </form>

                        <!-- Deliverables List -->
                        <div class="space-y-3">
                            <h3 class="text-xs font-bold text-slate-500">Livrables déjà déposés :</h3>
                            <?php if (empty($deliverables)): ?>
                            <p class="text-xs text-gray-500 italic">Aucun livrable déposé pour l'instant.</p>
                            <?php else: ?>
                            <div class="space-y-2">
                                <?php foreach ($deliverables as $deliv): ?>
                                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 flex justify-between items-center text-xs">
                                    <div>
                                        <strong class="text-slate-900 block"><?= htmlspecialchars($deliv['title']) ?></strong>
                                        <span class="text-[10px] text-gray-500"><?= htmlspecialchars(basename($deliv['file_path'])) ?> &bull; <?= date('d/m/Y H:i', strtotime($deliv['created_at'])) ?></span>
                                    </div>
                                    <a href="../../api/linkedin/download_file.php?type=deliverable&id=<?= $deliv['id'] ?>" class="text-amber-700 hover:underline font-bold text-xs">
                                        Télécharger
                                    </a>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>

                    </div>

                </div>

                <!-- Right Column (Payment Verification & Status Manager) -->
                <div class="space-y-6">
                    
                    <!-- Status & Payment Actions Card -->
                    <div class="bg-white border border-slate-200 rounded-2xl p-6 space-y-6">
                        
                        <!-- Order Status Form -->
                        <form action="order_details.php?id=<?= $orderId ?>&action=update_status" method="POST" class="space-y-3"><?php csrf_field(); ?>
                            <label class="block text-xs font-bold text-amber-700 uppercase tracking-wider">Statut Général de la Commande</label>
                            <select name="order_status" class="w-full bg-slate-50 border border-slate-200 text-xs text-slate-900 rounded-xl px-4 py-3 focus:outline-none focus:border-yellow-400">
                                <option value="COMMANDE_RECUE" <?= $order['order_status'] === 'COMMANDE_RECUE' ? 'selected' : '' ?>>COMMANDE_RECUE</option>
                                <option value="PAIEMENT_EN_VERIFICATION" <?= $order['order_status'] === 'PAIEMENT_EN_VERIFICATION' ? 'selected' : '' ?>>PAIEMENT_EN_VERIFICATION</option>
                                <option value="PAIEMENT_CONFIRME" <?= $order['order_status'] === 'PAIEMENT_CONFIRME' ? 'selected' : '' ?>>PAIEMENT_CONFIRME</option>
                                <option value="INFORMATIONS_A_COMPLETER" <?= $order['order_status'] === 'INFORMATIONS_A_COMPLETER' ? 'selected' : '' ?>>INFORMATIONS_A_COMPLETER</option>
                                <option value="EN_COURS_D_OPTIMISATION" <?= $order['order_status'] === 'EN_COURS_D_OPTIMISATION' ? 'selected' : '' ?>>EN_COURS_D_OPTIMISATION</option>
                                <option value="EN_REVISION" <?= $order['order_status'] === 'EN_REVISION' ? 'selected' : '' ?>>EN_REVISION</option>
                                <option value="TERMINE" <?= $order['order_status'] === 'TERMINE' ? 'selected' : '' ?>>TERMINE / LIVRÉ</option>
                            </select>
                            <button type="submit" class="w-full bg-slate-100 hover:bg-gray-700 text-slate-900 font-bold text-xs py-2.5 rounded-xl border border-slate-300">
                                Mettre à jour le statut
                            </button>
                        </form>

                        <!-- Payment Verification Box -->
                        <div class="pt-6 border-t border-slate-200 space-y-4">
                            <span class="text-xs font-bold text-amber-700 uppercase tracking-wider block">Vérification du Paiement Mobile</span>
                            
                            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-xs space-y-2">
                                <div class="flex justify-between"><span>Méthode:</span><strong class="text-slate-900"><?= htmlspecialchars($payment['payment_method'] ?? 'MTN') ?></strong></div>
                                <div class="flex justify-between"><span>Tél émetteur:</span><strong class="text-slate-900"><?= htmlspecialchars($payment['phone_number'] ?? $order['phone']) ?></strong></div>
                                <div class="flex justify-between"><span>Réf / ID Tx:</span><strong class="text-amber-700 font-mono font-bold"><?= htmlspecialchars($payment['transaction_ref'] ?? 'Non fournie') ?></strong></div>
                                <div class="flex justify-between border-t border-gray-900 pt-1"><span>Montant attendu:</span><strong class="text-slate-900 font-bold"><?= number_format($order['total_amount'], 0, ',', ' ') ?> FCFA</strong></div>
                                <div class="flex justify-between"><span>Statut actuel:</span><strong class="text-amber-700 font-bold"><?= htmlspecialchars($order['payment_status']) ?></strong></div>
                                
                                <?php if (!empty($payment['proof_file'])): ?>
                                <div class="pt-2">
                                    <a href="../../<?= htmlspecialchars($payment['proof_file']) ?>" target="_blank" class="text-xs text-blue-700 hover:underline flex items-center gap-1 font-bold">
                                        <i class="fa-solid fa-image"></i> Voir la preuve de paiement
                                    </a>
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex gap-2">
                                <form action="order_details.php?id=<?= $orderId ?>&action=confirm_payment" method="POST" class="flex-1"><?php csrf_field(); ?>
                                    <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs py-3 rounded-xl shadow">
                                        <i class="fa-solid fa-check mr-1"></i> Valider le paiement
                                    </button>
                                </form>
                                <form action="order_details.php?id=<?= $orderId ?>&action=reject_payment" method="POST" class="flex-1"><?php csrf_field(); ?>
                                    <button type="submit" onclick="return confirm('Confirmer le rejet du paiement ?')" class="w-full bg-red-600 hover:bg-red-500 text-white font-bold text-xs py-3 rounded-xl shadow">
                                        <i class="fa-solid fa-xmark mr-1"></i> Rejeter
                                    </button>
                                </form>
                            </div>
                        </div>

                    </div>

                    <!-- Internal Admin Notes -->
                    <div class="bg-white border border-slate-200 rounded-2xl p-6 space-y-4">
                        <h2 class="text-sm font-bold text-amber-700 uppercase tracking-wider">Notes Internes</h2>
                        <form action="order_details.php?id=<?= $orderId ?>&action=save_notes" method="POST" class="space-y-3"><?php csrf_field(); ?>
                            <textarea name="admin_notes" rows="4" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs text-slate-900 focus:outline-none focus:border-yellow-400" placeholder="Notes visibles uniquement par l'équipe HireMe..."><?= htmlspecialchars($order['admin_notes'] ?? '') ?></textarea>
                            <button type="submit" class="bg-slate-100 hover:bg-gray-700 text-slate-900 font-bold text-xs px-4 py-2 rounded-xl">
                                Enregistrer les notes
                            </button>
                        </form>
                    </div>

                    <!-- Activity History Timeline -->
                    <div class="bg-white border border-slate-200 rounded-2xl p-6 space-y-4">
                        <h2 class="text-sm font-bold text-amber-700 uppercase tracking-wider">Historique d'Activité</h2>
                        <div class="space-y-3 text-xs">
                            <?php foreach ($history as $h): ?>
                            <div class="border-l-2 border-yellow-400/40 pl-3 py-0.5">
                                <span class="font-bold text-slate-900 block"><?= htmlspecialchars($h['action']) ?></span>
                                <span class="text-slate-500 block"><?= htmlspecialchars($h['note']) ?></span>
                                <span class="text-[10px] text-gray-500"><?= date('d/m/Y H:i', strtotime($h['created_at'])) ?> &bull; <?= htmlspecialchars($h['created_by']) ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                </div>

            </div>

        </main>
    </div>

</body>
</html>
