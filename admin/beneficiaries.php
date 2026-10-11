<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}
require_once 'config/db.php';

$message = '';
$messageType = '';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'realize') {
        $id = $_POST['beneficiary_id'];
        $link = $_POST['recovery_link'];
        
        $stmt = $pdo->prepare("UPDATE sponsorship_requests SET recovery_link = ?, is_realized = 1, realized_at = NOW() WHERE id = ?");
        if ($stmt->execute([$link, $id])) {
            $message = "CV marqué comme réalisé avec succès !";
            $messageType = "success";
        } else {
            $message = "Erreur lors de la mise à jour.";
            $messageType = "error";
        }
    }
}

// Fetch Beneficiaries (Approved or Realized)
$beneficiaries = $pdo->query("
    SELECT r.*, p.donor_name, p.package_name, o.whatsapp as donor_whatsapp
    FROM sponsorship_requests r
    LEFT JOIN sponsorship_pool p ON r.allocated_pool_id = p.id
    LEFT JOIN orders o ON p.order_id = o.id
    WHERE r.status = 'approved'
    ORDER BY r.is_realized ASC, r.created_at DESC
")->fetchAll();

?>
<!DOCTYPE html>
<html lang="fr">
<?php include 'includes/header.php'; ?>
<body class="bg-gray-50 font-sans text-gray-800">

    <div class="flex h-screen overflow-hidden">
        <?php include 'includes/nav.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6 md:p-10">
            <div class="mb-10">
                <h1 class="text-3xl font-black text-gray-900 tracking-tight">Suivi des Bénéficiaires 🎓</h1>
                <p class="text-gray-500 mt-1">Gérez la production des CV pour les talents parrainés.</p>
            </div>

            <?php if ($message): ?>
                <div class="p-4 mb-8 text-sm text-white rounded-2xl <?= $messageType === 'success' ? 'bg-green-600' : 'bg-red-600' ?> animate-fade-in">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 gap-8">
                <div class="bg-white rounded-[2rem] shadow-sm border border-gray-100 overflow-hidden">
                    <div class="overflow-x-auto text-sm">
                        <table class="w-full text-left">
                            <thead class="bg-gray-50 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">
                                <tr>
                                    <th class="p-6">Bénéficiaire</th>
                                    <th class="p-6">Infos Contact</th>
                                    <th class="p-6">Parrain / Pack</th>
                                    <th class="p-6">Statut / Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <?php if (empty($beneficiaries)): ?>
                                    <tr>
                                        <td colspan="4" class="p-12 text-center text-gray-400 italic">Aucun bénéficiaire à traiter pour le moment.</td>
                                    </tr>
                                <?php endif; ?>
                                <?php foreach ($beneficiaries as $b): ?>
                                    <tr class="hover:bg-gray-50/50 transition-colors">
                                        <td class="p-6">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center font-black text-blue-600 text-sm shadow-inner">
                                                    <?= strtoupper(substr($b['name'], 0, 1)) ?>
                                                </div>
                                                <div>
                                                    <div class="font-black text-gray-900 leading-tight"><?= htmlspecialchars($b['name']) ?></div>
                                                    <div class="text-[10px] text-gray-400 mt-1"><?= date('d/m/Y', strtotime($b['created_at'])) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="p-6">
                                            <div class="space-y-1">
                                                <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $b['whatsapp']) ?>" target="_blank" class="text-xs font-bold text-green-600 hover:underline flex items-center gap-1">
                                                    📱 <?= htmlspecialchars($b['whatsapp']) ?>
                                                </a>
                                                <div class="text-xs text-gray-500">📧 <?= htmlspecialchars($b['email']) ?></div>
                                            </div>
                                        </td>
                                        <td class="p-6">
                                            <div class="font-bold text-gray-800 leading-tight"><?= htmlspecialchars($b['donor_name'] ?: 'Anonyme') ?></div>
                                            <div class="text-[9px] font-black text-blue-500 uppercase tracking-tighter mt-1"><?= $b['package_name'] ?></div>
                                        </td>
                                        <td class="p-6">
                                            <?php if ($b['is_realized']): ?>
                                                <div class="flex flex-col gap-2">
                                                    <span class="bg-green-100 text-green-700 px-3 py-1.5 rounded-xl font-black text-[10px] uppercase tracking-wide w-fit">REALIZED</span>
                                                    <a href="<?= htmlspecialchars($b['recovery_link']) ?>" target="_blank" class="text-[10px] text-blue-500 underline truncate max-w-[150px]">Voir le lien</a>
                                                </div>
                                            <?php else: ?>
                                                <form method="POST" class="flex items-center gap-2">
                                                    <input type="hidden" name="action" value="realize">
                                                    <input type="hidden" name="beneficiary_id" value="<?= $b['id'] ?>">
                                                    <input type="url" name="recovery_link" required placeholder="Lien du CV (Google Drive...)" 
                                                        class="border border-gray-200 p-2 rounded-xl text-xs w-64 outline-none focus:ring-2 focus:ring-blue-100 transition">
                                                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-xl font-black text-[10px] uppercase tracking-wide hover:bg-blue-700 transition">Valider</button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
