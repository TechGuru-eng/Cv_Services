<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}
require_once 'config/db.php';

$id = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$id]);
$order = $stmt->fetch();

if (!$order) {
    die("Commande introuvable.");
}

$isPaid = ($order['payment_status'] === 'verified');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reçu #<?= htmlspecialchars($order['order_ref'] ?: $order['id']) ?> — MonCv</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background-color: #f1f5f9;
            margin: 0;
            padding: 0;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        @media print {
            body { background-color: white !important; }
            .no-print { display: none !important; }
            .print-shadow-none { box-shadow: none !important; border: none !important; }
            @page { 
                margin: 0.5cm; /* Safe margin to prevent clipping while hiding most browser headers */
                size: A4 portrait;
            }
            .receipt-container {
                margin: 0 auto !important;
                padding: 1cm !important;
                width: 100% !important;
                max-width: none !important;
                border: 1px solid #f1f5f9 !important;
                height: auto !important;
                box-shadow: none !important;
            }
        }
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-35deg);
            font-size: 5rem;
            font-weight: 900;
            opacity: 0.03;
            pointer-events: none;
            text-transform: uppercase;
            white-space: nowrap;
        }
        .receipt-container {
            max-width: 750px;
            margin: 2rem auto;
            position: relative;
            background: white;
            transition: all 0.3s;
        }
    </style>
</head>
<body class="p-0 md:p-10">

    <div class="no-print mb-8 max-w-[800px] mx-auto flex justify-between items-center bg-white p-4 rounded-2xl shadow-sm border border-slate-200 mt-4">
        <a href="order-details.php?id=<?= $id ?>" class="text-slate-500 hover:text-slate-800 font-bold text-sm flex items-center gap-2">
            ← Retour aux détails
        </a>
        <button onclick="window.print()" class="bg-blue-600 text-white px-6 py-2 rounded-xl font-bold shadow-lg shadow-blue-200 hover:bg-blue-700 transition">
            Imprimer / Enregistrer PDF
        </button>
    </div>

    <div class="receipt-container p-8 md:p-12 rounded-[2rem] shadow-2xl shadow-slate-200 border border-slate-100 print-shadow-none overflow-hidden relative">
        
        <?php if($isPaid): ?>
            <div class="watermark text-green-600">VALIDÉ</div>
        <?php endif; ?>

        <!-- Header -->
        <div class="flex flex-col md:flex-row justify-between items-start mb-8 gap-8">
            <div>
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center text-white font-black text-lg shadow-lg shadow-blue-200">M</div>
                    <span class="text-xl font-black tracking-tighter text-slate-900">MonCv <span class="text-blue-600">Pro</span></span>
                </div>
                <div class="text-[11px] text-slate-400 space-y-0.5 font-medium">
                    <p class="font-bold text-slate-500 uppercase">Agence Digitale de Carrière</p>
                    <p>Sangmelima, Cameroun</p>
                    <p>moncvpro@hiremeguide.com</p>
                </div>
            </div>
            <div class="text-right">
                <h1 class="text-3xl font-black text-slate-900 mb-1">REÇU</h1>
                <p class="text-slate-400 font-bold uppercase tracking-widest text-[9px]">Référence: #<?= htmlspecialchars($order['order_ref'] ?: $order['id']) ?></p>
                <p class="text-slate-400 text-[10px] mt-1">Date: <?= date('d/m/Y', strtotime($order['created_at'])) ?></p>
            </div>
        </div>

        <div class="h-px bg-slate-100 w-full mb-8"></div>

        <!-- Client Info -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-12 mb-8">
            <div>
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-3">Facturé à</p>
                <div class="space-y-1">
                    <p class="text-base font-black text-slate-900"><?= htmlspecialchars($order['name']) ?></p>
                    <p class="text-[11px] text-slate-500 font-medium"><?= htmlspecialchars($order['email'] ?: 'N/A') ?></p>
                    <p class="text-[11px] text-slate-500 font-medium"><?= htmlspecialchars($order['whatsapp']) ?></p>
                    <p class="text-[11px] text-slate-500 font-medium"><?= htmlspecialchars($order['country'] ?: 'Cameroun') ?></p>
                </div>
            </div>
            <div class="md:text-right">
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-3">Détails Paiement</p>
                <div class="space-y-1">
                    <p class="text-slate-900 font-black italic text-sm"><?= htmlspecialchars($order['payment_method']) ?></p>
                    <p class="text-slate-500 text-[10px]">ID Transaction:</p>
                    <p class="text-slate-900 font-mono text-[10px] max-w-[200px] md:ml-auto break-all"><?= htmlspecialchars($order['transaction_number'] ?: 'N/A') ?></p>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="mb-8">
            <table class="w-full text-left">
                <thead class="bg-slate-50 rounded-xl">
                    <tr>
                        <th class="p-3 text-[9px] font-black text-slate-400 uppercase tracking-widest rounded-l-xl">Désignation</th>
                        <th class="p-3 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Quantité</th>
                        <th class="p-3 text-[9px] font-black text-slate-400 uppercase tracking-widest md:text-right rounded-r-xl">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <tr>
                        <td class="p-4">
                            <p class="font-black text-slate-800 text-sm"><?= htmlspecialchars($order['package_name']) ?></p>
                            <p class="text-[10px] text-slate-400 mt-0.5">Services de rédaction de CV professionnel & Optimisation</p>
                        </td>
                        <td class="p-4 font-bold text-slate-600 text-center text-sm">1</td>
                        <td class="p-4 font-black text-slate-900 md:text-right text-sm"><?= number_format($order['amount'], 0, ',', ' ') ?> FCFA</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Footer Sum -->
        <div class="flex justify-end mb-8">
            <div class="w-full md:w-64 space-y-2">
                <div class="flex justify-between text-slate-400 font-bold text-xs">
                    <span>Sous-total</span>
                    <span><?= number_format($order['amount'], 0, ',', ' ') ?> FCFA</span>
                </div>
                <div class="flex justify-between text-slate-400 font-bold text-xs">
                    <span>Taxe (0%)</span>
                    <span>0 FCFA</span>
                </div>
                <div class="h-px bg-slate-100 w-full my-1"></div>
                <div class="flex justify-between items-center">
                    <span class="text-sm font-black text-slate-900 uppercase italic">TOTAL</span>
                    <span class="text-xl font-black text-blue-600"><?= number_format($order['amount'], 0, ',', ' ') ?> FCFA</span>
                </div>
            </div>
        </div>

        <!-- Note -->
        <div class="bg-slate-50 p-6 rounded-2xl border border-slate-100">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Note Importante</p>
            <p class="text-[11px] text-slate-500 leading-relaxed font-medium italic">
                Ce document fait office de reçu officiel pour votre commande. Nos services commencent dès validation du paiement. Pour toute réclamation, veuillez mentionner votre référence de commande #<?= htmlspecialchars($order['order_ref'] ?: $order['id']) ?>.
            </p>
        </div>

        <div class="mt-12 text-center">
            <p class="text-slate-300 text-[10px] font-bold">Généré par MonCv Admin Cloud Engine &copy; <?= date('Y') ?></p>
        </div>

    </div>

</body>
</html>
