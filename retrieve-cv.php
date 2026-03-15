<?php
require_once 'admin/config/db.php';

$result = null;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $enteredName = trim($_POST['full_name']);

    if (empty($email) || empty($enteredName)) {
        $error = "Veuillez remplir tous les champs.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM cv_packs WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $pack = $stmt->fetch();

        if ($pack) {
            if ($pack['status'] !== 'verified') {
                $error = "Paiement non vérifié.";
            } else {
                $storedParts = array_filter(explode(' ', strtolower($pack['full_name'])));
                $enteredParts = array_filter(explode(' ', strtolower($enteredName)));
                
                $matches = 0;
                foreach ($enteredParts as $ePart) {
                    if (in_array($ePart, $storedParts)) {
                        $matches++;
                    }
                }

                if ($matches >= 2) {
                    $result = $pack;
                } else {
                    $error = "Aucun enregistrement correspondant trouvé.";
                }
            }
        } else {
            $error = "Aucun enregistrement correspondant trouvé.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Récupérer mon Pack CV — HireMe</title>
    
    <!-- Favicons -->
    <link rel="icon" href="https://moncvpro.hiremeguide.com/assets/favicons/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="https://moncvpro.hiremeguide.com/assets/favicons/favicon-16.png">
    <link rel="icon" type="image/png" sizes="32x32" href="https://moncvpro.hiremeguide.com/assets/favicons/favicon-32.png">
    <link rel="apple-touch-icon" sizes="180x180" href="https://moncvpro.hiremeguide.com/assets/favicons/apple-touch-icon-180.png">

    <!-- Premium Preview Meta Tags -->
    <meta property="og:title" content="HireMe CV — Portail de Récupération" />
    <meta property="og:description" content="Accédez à vos documents professionnels personnalisés en toute sécurité." />
    <meta property="og:image" content="https://moncvpro.hiremeguide.com/assets/cv-retrieval-preview.png" />
    <meta property="og:type" content="website" />
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:image" content="https://moncvpro.hiremeguide.com/assets/cv-retrieval-preview.png" />
    
    <link rel="canonical" href="https://moncvpro.hiremeguide.com/retrieve-cv.php" />
    <meta name="theme-color" content="#0ea5e9">

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: {
                        primary: '#0ea5e9',
                        accent: '#22c55e',
                        neutral: '#111827',
                        bg: '#f8fafc'
                    }
                }
            }
        }
    </script>
    <style>
        body { background-color: #f8fafc; color: #111827; }
        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-6">
    <div class="max-w-md w-full">
        <!-- Logo -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-extrabold tracking-tight text-neutral">
                HireMe <span class="text-primary">CV</span>
            </h1>
            <p class="text-gray-500 mt-2 text-sm font-medium">Portail de récupération sécurisé</p>
        </div>

        <div class="glass-effect border border-gray-200 p-8 rounded-3xl shadow-xl">
            <?php if ($result): ?>
                <div class="text-center">
                    <div class="w-16 h-16 bg-blue-50 text-primary rounded-full flex items-center justify-center mx-auto mb-6 text-3xl">✓</div>
                    <h2 class="text-xl font-bold text-neutral mb-2">Bonjour <?= htmlspecialchars(explode(' ', $result['full_name'])[0]) ?>,</h2>
                    <p class="text-gray-600 mb-8 text-sm leading-relaxed">
                        Votre pack de CV est prêt. Cliquez sur le bouton ci-dessous pour lancer le téléchargement.
                    </p>
                    
                    <a href="<?= htmlspecialchars($result['pack_link']) ?>" target="_blank" class="block w-full bg-primary text-white font-bold py-4 rounded-xl shadow-lg shadow-primary/20 hover:bg-sky-600 transition text-center uppercase tracking-tight">
                        TÉLÉCHARGER MON PACK CV 🚀
                    </a>
                </div>
            <?php else: ?>
                <form method="POST" class="space-y-5">
                    <?php if ($error): ?>
                        <div class="bg-red-50 border border-red-100 text-red-600 px-4 py-3 rounded-xl text-xs font-bold flex items-center gap-2">
                           <span>⚠️</span> <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <div class="space-y-2">
                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1">Email de commande</label>
                        <input type="email" name="email" required placeholder="votre@email.com" 
                            class="w-full bg-white border border-gray-200 p-3.5 rounded-xl outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition text-sm">
                    </div>

                    <div class="space-y-2">
                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1">Nom Complet</label>
                        <input type="text" name="full_name" required placeholder="Ex: Jean Paul" 
                            class="w-full bg-white border border-gray-200 p-3.5 rounded-xl outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition text-sm">
                    </div>

                    <button type="submit" class="w-full bg-primary text-white font-bold py-4 rounded-xl shadow-lg shadow-primary/20 hover:bg-sky-600 transition uppercase tracking-tight text-sm">
                        RÉCUPÉRER MON PACK
                    </button>
                    
                    <div class="pt-6 border-t border-gray-100 text-center">
                        <a href="https://wa.me/237654250688" class="text-gray-400 hover:text-primary transition-colors text-xs font-medium">
                            Besoin d'aide ? Contactez-nous sur WhatsApp
                        </a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
        
        <div class="mt-8 text-center text-gray-400 text-[10px] font-bold uppercase tracking-widest">
            &copy; <?= date('Y') ?> HireMe CV Services
        </div>
    </div>
</body>
</html>
