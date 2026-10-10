<?php
// linkedin/commander.php
// Multi-Step Order Flow with Smart Upsell & Cross-Sell Engine (MonCvPro Redesign Theme)

require_once __DIR__ . '/../admin/config/db.php';

// Pre-selected package or profile type from URL query string
$selectedPkgId = (int)($_GET['package'] ?? 0);
$selectedProfileType = trim($_GET['profile'] ?? '');

// Fetch Packages
$stmtPkg = $pdo->query("SELECT id, slug, name, price, tagline, deliverables_json, is_popular FROM linkedin_packages WHERE is_active = 1 ORDER BY sort_order ASC");
$packages = $stmtPkg->fetchAll(PDO::FETCH_ASSOC);

// If no package selected, default to Pack Pro
if ($selectedPkgId <= 0 && !empty($packages)) {
    foreach ($packages as $p) {
        if ($p['slug'] === 'pro') {
            $selectedPkgId = $p['id'];
            break;
        }
    }
    if ($selectedPkgId <= 0) {
        $selectedPkgId = $packages[0]['id'];
    }
}

// Fetch Addons
$stmtAdd = $pdo->query("SELECT id, slug, name, description, price FROM linkedin_addons WHERE is_active = 1 ORDER BY sort_order ASC");
$addons = $stmtAdd->fetchAll(PDO::FETCH_ASSOC);

// Fetch Upsell Products
$stmtProd = $pdo->query("SELECT id, slug, name, description, price, promo_price, is_digital, cta_text, highlight_badge, target_objectives_json, sort_order FROM linkedin_products WHERE is_active = 1 ORDER BY sort_order ASC");
$products = $stmtProd->fetchAll(PDO::FETCH_ASSOC);

// Fetch Bundles
$stmtBundle = $pdo->query("SELECT id, slug, name, description, bundle_price FROM linkedin_bundles WHERE is_active = 1");
$bundles = $stmtBundle->fetchAll(PDO::FETCH_ASSOC);
$visibiliteBundle = !empty($bundles) ? $bundles[0] : null;

// Fetch Payment Settings
$stmtSet = $pdo->query("SELECT setting_key, setting_value FROM linkedin_settings");
$settings = $stmtSet->fetchAll(PDO::FETCH_KEY_PAIR);
$mtnPhone = $settings['mtn_phone'] ?? '670000000';
$mtnUssd = $settings['mtn_ussd'] ?? '*126#';
$orangePhone = $settings['orange_phone'] ?? '690000000';
$orangeUssd = $settings['orange_ussd'] ?? '*150#';
?>
<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commande — Optimisation Profil LinkedIn | HireMe</title>
    
    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Tailwind CSS (MonCvPro Theme) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'Inter', 'sans-serif'],
                    },
                    colors: {
                        primary: '#0ea5e9',   // Sky Blue (MonCvPro)
                        primaryDark: '#0284c7',
                        accent: '#22c55e',    // Emerald Green
                        danger: '#ef4444',    // Red
                        warning: '#f59e0b',   // Gold/Amber
                        neutral: '#111827',   // Slate 900
                        bg: '#f8fafc'         // Light Gray Slate 50
                    },
                    boxShadow: {
                        'soft': '0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03)',
                        'glow': '0 0 20px rgba(14, 165, 233, 0.3)',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-bg text-neutral antialiased font-sans min-h-screen py-8 px-4">

    <!-- Toast Notification (MonCvPro Style) -->
    <div id="toastNotification" class="fixed top-4 left-1/2 -translate-x-1/2 z-50 hidden bg-neutral text-white font-extrabold text-xs px-6 py-3 rounded-full shadow-2xl flex items-center gap-2 border border-sky-400">
        <i class="fa-solid fa-circle-check text-primary text-base"></i>
        <span id="toastText">Ajouté à votre commande ✓</span>
    </div>

    <div class="max-w-6xl mx-auto space-y-8">
        
        <!-- Header Nav -->
        <div class="flex items-center justify-between border-b border-gray-200 pb-6">
            <a href="index.php" class="flex items-center gap-2 group">
                <div class="w-9 h-9 rounded-xl bg-primary text-white font-black flex items-center justify-center text-lg shadow-md shadow-sky-500/20">HM</div>
                <span class="text-xl font-extrabold text-primary tracking-tight">HireMe<span class="text-neutral">LinkedIn</span></span>
            </a>
            <div class="text-xs text-gray-500 font-bold flex items-center gap-2 bg-white px-3 py-1.5 rounded-full border border-gray-200 shadow-sm">
                <i class="fa-solid fa-shield-halved text-primary"></i>
                <span>Commande & Paiement Sécurisés</span>
            </div>
        </div>

        <!-- Progress Steps Bar (MonCvPro Multi-step Indicator) -->
        <div class="bg-white border border-gray-200 rounded-3xl p-4 sm:p-6 shadow-soft overflow-x-auto">
            <div class="flex items-center justify-between min-w-[600px] text-xs font-bold">
                
                <div class="flex items-center gap-2 step-indicator text-primary" id="stepInd1">
                    <span class="w-8 h-8 rounded-full bg-primary text-white flex items-center justify-center font-extrabold shadow-sm">1</span>
                    <span>Profil</span>
                </div>
                <div class="h-0.5 w-8 bg-gray-200"></div>

                <div class="flex items-center gap-2 step-indicator text-gray-400" id="stepInd2">
                    <span class="w-8 h-8 rounded-full bg-gray-100 text-gray-400 flex items-center justify-center font-extrabold">2</span>
                    <span>Objectif</span>
                </div>
                <div class="h-0.5 w-8 bg-gray-200"></div>

                <div class="flex items-center gap-2 step-indicator text-gray-400" id="stepInd3">
                    <span class="w-8 h-8 rounded-full bg-gray-100 text-gray-400 flex items-center justify-center font-extrabold">3</span>
                    <span>Documents</span>
                </div>
                <div class="h-0.5 w-8 bg-gray-200"></div>

                <div class="flex items-center gap-2 step-indicator text-gray-400" id="stepInd4">
                    <span class="w-8 h-8 rounded-full bg-gray-100 text-gray-400 flex items-center justify-center font-extrabold">4</span>
                    <span>Forfait & Options</span>
                </div>
                <div class="h-0.5 w-8 bg-gray-200"></div>

                <div class="flex items-center gap-2 step-indicator text-gray-400" id="stepInd5">
                    <span class="w-8 h-8 rounded-full bg-gray-100 text-gray-400 flex items-center justify-center font-extrabold">5</span>
                    <span>Paiement</span>
                </div>
                <div class="h-0.5 w-8 bg-gray-200"></div>

                <div class="flex items-center gap-2 step-indicator text-gray-400" id="stepInd6">
                    <span class="w-8 h-8 rounded-full bg-gray-100 text-gray-400 flex items-center justify-center font-extrabold">6</span>
                    <span>Confirmation</span>
                </div>

            </div>
        </div>

        <!-- Form Container -->
        <form id="orderForm" enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            
            <!-- Hidden input for selected Bundle -->
            <input type="hidden" name="bundle_id" id="bundleIdInput" value="0">

            <!-- MAIN FORM COLUMNS -->
            <div class="lg:col-span-8 bg-white border border-gray-200 rounded-3xl p-6 sm:p-10 shadow-soft relative">
                
                <!-- ALERT MESSAGES -->
                <div id="errorAlert" class="hidden mb-6 p-4 rounded-2xl bg-red-50 border border-red-200 text-red-700 text-xs font-semibold flex items-center gap-3">
                    <i class="fa-solid fa-triangle-exclamation text-lg shrink-0"></i>
                    <span id="errorMessage">Une erreur est survenue.</span>
                </div>

                <!-- STEP 1: PROFIL -->
                <div class="step-panel space-y-6" id="stepPanel1">
                    <div class="border-b border-gray-200 pb-4">
                        <h2 class="text-xl font-extrabold text-neutral">Étape 1 sur 6 : Vos coordonnées personnelles</h2>
                        <p class="text-xs text-gray-500 font-medium">Renseignez vos informations de contact pour recevoir vos livrables.</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5">Nom *</label>
                            <input type="text" name="name" required class="w-full bg-white border border-gray-300 rounded-xl px-4 py-3 text-sm text-neutral focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20" placeholder="Ex: Ndombé">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5">Prénom *</label>
                            <input type="text" name="first_name" required class="w-full bg-white border border-gray-300 rounded-xl px-4 py-3 text-sm text-neutral focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20" placeholder="Ex: Emmanuel">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5">Téléphone / WhatsApp *</label>
                            <input type="tel" name="phone" required class="w-full bg-white border border-gray-300 rounded-xl px-4 py-3 text-sm text-neutral focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20" placeholder="Ex: +237 670 00 00 00">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5">Adresse Email *</label>
                            <input type="email" name="email" required class="w-full bg-white border border-gray-300 rounded-xl px-4 py-3 text-sm text-neutral focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20" placeholder="Ex: emmanuel@email.cm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5">Ville</label>
                            <input type="text" name="city" class="w-full bg-white border border-gray-300 rounded-xl px-4 py-3 text-sm text-neutral focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20" placeholder="Ex: Douala, Yaoundé...">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5">Pays</label>
                            <input type="text" name="country" value="Cameroun" class="w-full bg-white border border-gray-300 rounded-xl px-4 py-3 text-sm text-neutral focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5">Situation professionnelle actuelle *</label>
                            <select name="position_status" required class="w-full bg-white border border-gray-300 rounded-xl px-4 py-3 text-sm text-neutral focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                                <option value="Étudiant">Étudiant / Jeune diplômé</option>
                                <option value="En recherche active">En recherche d'emploi</option>
                                <option value="En poste (à l'écoute)">En poste (à l'écoute du marché)</option>
                                <option value="Freelance / Indépendant">Freelance / Indépendant</option>
                                <option value="Entrepreneur / Fondateur">Entrepreneur / Fondateur</option>
                                <option value="Manager / Cadre dirigeant">Manager / Cadre dirigeant</option>
                                <option value="En reconversion">En reconversion professionnelle</option>
                                <option value="Autre">Autre</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5">Profil recherché par les recruteurs</label>
                            <input type="text" name="target_profile_type" value="<?= htmlspecialchars($selectedProfileType) ?>" class="w-full bg-white border border-gray-300 rounded-xl px-4 py-3 text-sm text-neutral focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20" placeholder="Ex: Cadre RH, Développeur Web, Comptable...">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1.5">Lien de votre profil LinkedIn actuel *</label>
                        <div class="relative">
                            <span class="absolute left-4 top-3.5 text-gray-400 text-sm"><i class="fa-brands fa-linkedin"></i></span>
                            <input type="url" name="linkedin_url" required class="w-full bg-white border border-gray-300 rounded-xl pl-10 pr-4 py-3 text-sm text-neutral focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20" placeholder="https://www.linkedin.com/in/votre-nom">
                        </div>
                        <span class="text-[11px] text-gray-500 mt-1 block">Si vous n'avez pas encore de compte LinkedIn, indiquez : <code>https://linkedin.com/in/nouveau-profil</code></span>
                    </div>

                    <div class="flex justify-end pt-4">
                        <button type="button" onclick="nextStep(2)" class="bg-primary hover:bg-sky-600 text-white font-extrabold text-sm px-8 py-3.5 rounded-full shadow-glow">
                            Continuer vers Étape 2 <i class="fa-solid fa-arrow-right ml-2"></i>
                        </button>
                    </div>
                </div>

                <!-- STEP 2: OBJECTIF -->
                <div class="step-panel hidden space-y-6" id="stepPanel2">
                    <div class="border-b border-gray-200 pb-4">
                        <h2 class="text-xl font-extrabold text-neutral">Étape 2 sur 6 : Votre objectif professionnel</h2>
                        <p class="text-xs text-gray-500 font-medium">Dites-nous ce que vous cherchez à accomplir sur LinkedIn.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-2">Quel est votre objectif principal sur LinkedIn ? *</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <?php
                            $objectives = [
                                "Trouver un emploi",
                                "Être contacté par des recruteurs",
                                "Changer de secteur",
                                "Trouver des clients",
                                "Développer mon réseau",
                                "Construire ma marque personnelle",
                                "Rechercher un stage",
                                "Autre"
                            ];
                            foreach ($objectives as $idx => $obj):
                            ?>
                            <label class="cursor-pointer flex items-center gap-3 p-3.5 rounded-xl bg-gray-50 border border-gray-200 hover:border-primary has-[:checked]:border-primary has-[:checked]:bg-sky-50/70 transition-colors">
                                <input type="radio" name="main_objective" value="<?= htmlspecialchars($obj) ?>" <?= $idx === 0 ? 'checked' : '' ?> onchange="applySmartRecommendations()" class="accent-sky-500">
                                <span class="text-xs font-bold text-neutral"><?= htmlspecialchars($obj) ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5">Poste visé / recherché *</label>
                            <input type="text" name="target_job" required class="w-full bg-white border border-gray-300 rounded-xl px-4 py-3 text-sm text-neutral focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20" placeholder="Ex: Responsable Marketing Digital">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5">Secteur d'activité ciblé *</label>
                            <input type="text" name="target_sector" required class="w-full bg-white border border-gray-300 rounded-xl px-4 py-3 text-sm text-neutral focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20" placeholder="Ex: Télécoms, Banque, BTP, Santé...">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5">Entreprises ciblées (Optionnel)</label>
                            <input type="text" name="target_companies" class="w-full bg-white border border-gray-300 rounded-xl px-4 py-3 text-sm text-neutral focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20" placeholder="Ex: MTN, Orange, Nestlé, Total...">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5">Pays / Villes ciblés</label>
                            <input type="text" name="target_locations" value="Cameroun / Afrique Francophone" class="w-full bg-white border border-gray-300 rounded-xl px-4 py-3 text-sm text-neutral focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1.5">Niveau d'expérience global</label>
                        <select name="experience_level" class="w-full bg-white border border-gray-300 rounded-xl px-4 py-3 text-sm text-neutral focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                            <option value="Débutant (0 - 2 ans)">Débutant (0 - 2 ans)</option>
                            <option value="Intermédiaire (3 - 5 ans)" selected>Intermédiaire (3 - 5 ans)</option>
                            <option value="Confirmé / Sénior (6 - 10 ans)">Confirmé / Sénior (6 - 10 ans)</option>
                            <option value="Executive / Expert (10+ ans)">Executive / Expert (10+ ans)</option>
                        </select>
                    </div>

                    <div class="flex justify-between pt-4">
                        <button type="button" onclick="prevStep(1)" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-sm px-6 py-3.5 rounded-full border border-gray-300">
                            <i class="fa-solid fa-arrow-left mr-2"></i> Précédent
                        </button>
                        <button type="button" onclick="nextStep(3)" class="bg-primary hover:bg-sky-600 text-white font-extrabold text-sm px-8 py-3.5 rounded-full shadow-glow">
                            Continuer vers Étape 3 <i class="fa-solid fa-arrow-right ml-2"></i>
                        </button>
                    </div>
                </div>

                <!-- STEP 3: DOCUMENTS -->
                <div class="step-panel hidden space-y-6" id="stepPanel3">
                    <div class="border-b border-gray-200 pb-4">
                        <h2 class="text-xl font-extrabold text-neutral">Étape 3 sur 6 : Transmettre votre CV</h2>
                        <p class="text-xs text-gray-500 font-medium">Votre CV nous sert de base pour rédiger un profil LinkedIn parfaitement cohérent.</p>
                    </div>

                    <!-- Security Box -->
                    <div class="p-4 rounded-2xl bg-sky-50 border border-sky-200 flex items-start gap-3">
                        <i class="fa-solid fa-shield-halved text-primary text-xl shrink-0 mt-0.5"></i>
                        <div class="text-xs text-gray-700 font-medium">
                            <strong class="text-neutral block font-bold mb-0.5">Garantie de Sécurité HireMe</strong>
                            Pour votre sécurité, HireMe ne vous demandera <strong class="text-primary">jamais votre mot de passe LinkedIn</strong>. Nous préparons et vous livrons l'ensemble des éléments optimisés prêts à copier-coller.
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-2">Télécharger votre CV (PDF, DOC, DOCX, JPG, PNG - Max 10 Mo) *</label>
                        <div class="border-2 border-dashed border-gray-300 hover:border-primary rounded-3xl p-8 text-center bg-bg transition-colors">
                            <i class="fa-solid fa-cloud-arrow-up text-3xl text-primary mb-3"></i>
                            <p class="text-xs font-bold text-neutral mb-1">Cliquez ou glissez votre fichier CV ici</p>
                            <p class="text-[11px] text-gray-500 mb-4">Formats acceptés : .pdf, .doc, .docx, .png, .jpg</p>
                            <input type="file" name="cv_file" id="cvFileInput" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required onchange="handleCvFileChange(this)" class="hidden">
                            <button type="button" onclick="document.getElementById('cvFileInput').click()" class="bg-white hover:bg-gray-100 text-neutral font-bold text-xs px-5 py-2.5 rounded-full border border-gray-300 shadow-sm">
                                Parcourir mes fichiers
                            </button>
                        </div>
                        
                        <div id="cvFilePreview" class="hidden mt-3 p-3 rounded-2xl bg-white border border-gray-200 flex items-center justify-between shadow-sm">
                            <div class="flex items-center gap-3">
                                <i class="fa-solid fa-file-pdf text-primary text-xl"></i>
                                <div>
                                    <span class="text-xs font-bold text-neutral block" id="cvFileName">cv.pdf</span>
                                    <span class="text-[10px] text-gray-400" id="cvFileSize">1.2 MB</span>
                                </div>
                            </div>
                            <button type="button" onclick="removeCvFile()" class="text-danger hover:text-red-700 text-xs font-bold px-2 py-1">
                                Supprimer
                            </button>
                        </div>
                    </div>

                    <div class="flex justify-between pt-4">
                        <button type="button" onclick="prevStep(2)" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-sm px-6 py-3.5 rounded-full border border-gray-300">
                            <i class="fa-solid fa-arrow-left mr-2"></i> Précédent
                        </button>
                        <button type="button" onclick="nextStep(4)" class="bg-primary hover:bg-sky-600 text-white font-extrabold text-sm px-8 py-3.5 rounded-full shadow-glow">
                            Continuer vers Étape 4 <i class="fa-solid fa-arrow-right ml-2"></i>
                        </button>
                    </div>
                </div>

                <!-- STEP 4: OPTIONS, BUNDLES & UPSELLS -->
                <div class="step-panel hidden space-y-8" id="stepPanel4">
                    <div class="border-b border-gray-200 pb-4">
                        <h2 class="text-xl font-extrabold text-neutral">Étape 4 sur 6 : Formule & Outils complémentaires</h2>
                        <p class="text-xs text-gray-500 font-medium">Complétez votre profil avec les ressources clés pour accélérer vos résultats.</p>
                    </div>

                    <!-- FEATURED BUNDLE: PACK VISIBILITÉ PRO -->
                    <?php if ($visibiliteBundle): ?>
                    <div class="p-6 rounded-3xl bg-gradient-to-r from-sky-50 via-blue-50 to-indigo-50 border-2 border-primary relative overflow-hidden shadow-soft">
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-gray-200 pb-4">
                            <div>
                                <span class="bg-primary text-white font-extrabold text-[10px] uppercase tracking-widest px-3 py-1 rounded-full shadow">
                                    Combo recommandé HireMe
                                </span>
                                <h3 class="text-xl font-black text-neutral mt-2"><?= htmlspecialchars($visibiliteBundle['name']) ?></h3>
                                <p class="text-xs text-gray-600 font-medium mt-0.5"><?= htmlspecialchars($visibiliteBundle['description']) ?></p>
                            </div>
                            <div class="text-left sm:text-right">
                                <span class="text-xs text-gray-400 line-through block">35 600 FCFA</span>
                                <strong class="text-2xl font-black text-primary block"><?= number_format($visibiliteBundle['bundle_price'], 0, ',', ' ') ?> FCFA</strong>
                                <span class="text-[11px] font-bold text-accent bg-emerald-100 px-2.5 py-0.5 rounded-full border border-emerald-200">
                                    Vous économisez 3 600 FCFA
                                </span>
                            </div>
                        </div>

                        <div class="pt-4 flex flex-col sm:flex-row items-center justify-between gap-4">
                            <ul class="text-xs text-gray-700 font-medium space-y-1.5">
                                <li class="flex items-center gap-2"><i class="fa-solid fa-check text-accent font-black"></i> <span>Optimisation Profil LinkedIn Pro</span></li>
                                <li class="flex items-center gap-2"><i class="fa-solid fa-check text-accent font-black"></i> <span>Optimisation CV ATS Ciblée (+10 000 FCFA)</span></li>
                                <li class="flex items-center gap-2"><i class="fa-solid fa-check text-accent font-black"></i> <span>100 Messages pour contacter et relancer recruteurs (+600 FCFA)</span></li>
                            </ul>
                            <button type="button" id="selectBundleBtn" onclick="toggleBundle(<?= $visibiliteBundle['id'] ?>, <?= $visibiliteBundle['bundle_price'] ?>)" class="w-full sm:w-auto bg-primary hover:bg-sky-600 text-white font-extrabold text-xs px-6 py-3.5 rounded-full shadow-glow transition-all shrink-0">
                                Choisir le Pack Visibilité Pro
                            </button>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Standard Package Selector -->
                    <div id="packageSelectorBox">
                        <label class="block text-xs font-extrabold text-neutral mb-3 uppercase tracking-wider">Choisissez votre forfait d'optimisation :</label>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <?php foreach ($packages as $pkg): 
                                $deliverables = json_decode($pkg['deliverables_json'], true) ?: [];
                                $isPopular = (bool)($pkg['is_popular'] ?? 0);
                            ?>
                            <div class="rounded-2xl bg-white border <?= $isPopular ? 'border-2 border-primary bg-sky-50/30' : 'border-gray-200' ?> p-5 flex flex-col justify-between relative transition-all shadow-soft">
                                <?php if ($isPopular): ?>
                                <div class="mb-2">
                                    <span class="bg-primary text-white font-extrabold text-[10px] uppercase tracking-widest px-2.5 py-0.5 rounded-full inline-flex items-center gap-1 shadow-sm">
                                        <i class="fa-solid fa-star text-[9px]"></i> LE PLUS CHOISI
                                    </span>
                                </div>
                                <?php endif; ?>

                                <div>
                                    <label class="cursor-pointer flex items-center justify-between gap-2 mb-2">
                                        <div class="flex items-center gap-2">
                                            <input type="radio" name="package_id" value="<?= $pkg['id'] ?>" data-price="<?= $pkg['price'] ?>" data-name="<?= htmlspecialchars($pkg['name']) ?>" <?= $pkg['id'] == $selectedPkgId ? 'checked' : '' ?> onchange="calculateTotal()" class="accent-sky-500 w-4 h-4">
                                            <strong class="text-sm font-extrabold text-neutral"><?= htmlspecialchars($pkg['name']) ?></strong>
                                        </div>
                                        <span class="text-xs font-black text-primary shrink-0"><?= number_format($pkg['price'], 0, ',', ' ') ?> FCFA</span>
                                    </label>

                                    <!-- Dominant Promise -->
                                    <div class="p-3 rounded-xl bg-gray-50 border border-gray-200 mb-3">
                                        <span class="text-[9px] uppercase font-extrabold text-primary block tracking-wider mb-0.5">Promesse :</span>
                                        <p class="text-xs font-bold text-neutral leading-snug">"<?= htmlspecialchars($pkg['tagline']) ?>"</p>
                                    </div>

                                    <!-- Expandable Deliverables -->
                                    <?php if (!empty($deliverables)): ?>
                                    <div class="mb-2">
                                        <button type="button" onclick="toggleDeliverables(this)" class="w-full py-1.5 px-2.5 rounded-lg bg-gray-100 hover:bg-gray-200 border border-gray-200 text-[11px] font-bold text-gray-600 flex items-center justify-between transition-colors">
                                            <span>Voir ce qui est inclus (<?= count($deliverables) ?>)</span>
                                            <i class="fa-solid fa-chevron-down text-[9px] text-primary transition-transform duration-300"></i>
                                        </button>
                                        <ul class="deliverables-full-list hidden space-y-1 text-[11px] text-gray-600 mt-2 pt-2 border-t border-gray-200">
                                            <?php foreach ($deliverables as $deliv): ?>
                                            <li class="flex items-start gap-1.5">
                                                <i class="fa-solid fa-check text-accent text-[9px] mt-1 shrink-0 font-black"></i>
                                                <span><?= htmlspecialchars($deliv) ?></span>
                                            </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- MAIN CHECKOUT UPSELL SECTION -->
                    <div class="space-y-4 pt-4 border-t border-gray-200">
                        <div>
                            <h3 class="text-lg font-extrabold text-neutral">Complétez votre optimisation professionnelle</h3>
                            <p class="text-xs text-gray-500 font-medium mt-0.5">Ajoutez les outils dont vous avez besoin pour transformer votre profil optimisé en candidatures et prises de contact plus efficaces.</p>
                        </div>

                        <!-- Product Cards Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" id="upsellProductsContainer">
                            <?php foreach ($products as $index => $prod): 
                                $objectives = json_decode($prod['target_objectives_json'], true) ?: [];
                                $isExtra = ($index >= 2);
                            ?>
                            <div class="upsell-card p-5 rounded-2xl bg-white border border-gray-200 hover:border-primary shadow-soft transition-all flex flex-col justify-between <?= $isExtra ? 'extra-upsell hidden' : '' ?>" data-objectives='<?= json_encode($objectives) ?>'>
                                <div>
                                    <div class="flex justify-between items-start gap-2 mb-1.5">
                                        <?php if ($prod['highlight_badge']): ?>
                                        <span class="bg-sky-100 text-sky-800 font-extrabold text-[10px] px-2.5 py-0.5 rounded-full border border-sky-200">
                                            <?= htmlspecialchars($prod['highlight_badge']) ?>
                                        </span>
                                        <?php else: ?>
                                        <span></span>
                                        <?php endif; ?>
                                        <strong class="text-xs font-black text-primary shrink-0">+<?= number_format($prod['price'], 0, ',', ' ') ?> FCFA</strong>
                                    </div>
                                    
                                    <h4 class="text-xs font-extrabold text-neutral leading-snug mb-1"><?= htmlspecialchars($prod['name']) ?></h4>
                                    <p class="text-[11px] text-gray-500 leading-relaxed font-medium"><?= htmlspecialchars($prod['description']) ?></p>
                                </div>

                                <div class="pt-3 flex items-center justify-between border-t border-gray-100 mt-3">
                                    <label class="cursor-pointer inline-flex items-center gap-2 text-xs font-bold text-gray-700">
                                        <input type="checkbox" name="product_ids[]" value="<?= $prod['id'] ?>" data-price="<?= $prod['price'] ?>" data-name="<?= htmlspecialchars($prod['name']) ?>" onchange="toggleProductBump(this)" class="accent-sky-500 w-4 h-4">
                                        <span class="bump-btn-label"><?= htmlspecialchars($prod['cta_text'] ?: 'Ajouter') ?></span>
                                    </label>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Progressive Disclosure Button -->
                        <div class="text-center pt-2">
                            <button type="button" id="showMoreUpsellsBtn" onclick="toggleMoreUpsells()" class="inline-flex items-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-xs px-5 py-2.5 rounded-full border border-gray-300">
                                <span>Voir plus de ressources HireMe</span>
                                <i class="fa-solid fa-chevron-down text-xs" id="moreUpsellsIcon"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Select Addons -->
                    <?php if (!empty($addons)): ?>
                    <div class="space-y-3 pt-4 border-t border-gray-200">
                        <label class="block text-xs font-extrabold text-neutral uppercase tracking-wider">Services additionnels sur-mesure</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <?php foreach ($addons as $ad): ?>
                            <label class="cursor-pointer p-4 rounded-2xl bg-white border border-gray-200 hover:border-primary has-[:checked]:border-primary has-[:checked]:bg-sky-50/50 shadow-soft transition-colors flex items-start gap-3">
                                <input type="checkbox" name="addon_ids[]" value="<?= $ad['id'] ?>" data-price="<?= $ad['price'] ?>" data-name="<?= htmlspecialchars($ad['name']) ?>" onchange="calculateTotal()" class="accent-sky-500 mt-1">
                                <div>
                                    <div class="flex justify-between items-center gap-2">
                                        <strong class="text-xs font-bold text-neutral"><?= htmlspecialchars($ad['name']) ?></strong>
                                        <span class="text-[11px] font-black text-primary shrink-0">+<?= number_format($ad['price'], 0, ',', ' ') ?> FCFA</span>
                                    </div>
                                    <span class="text-[11px] text-gray-500 mt-0.5 block font-medium"><?= htmlspecialchars($ad['description']) ?></span>
                                </div>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="flex justify-between pt-4">
                        <button type="button" onclick="prevStep(3)" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-sm px-6 py-3.5 rounded-full border border-gray-300">
                            <i class="fa-solid fa-arrow-left mr-2"></i> Précédent
                        </button>
                        <button type="button" onclick="nextStep(5)" class="bg-primary hover:bg-sky-600 text-white font-extrabold text-sm px-8 py-3.5 rounded-full shadow-glow">
                            Continuer vers le Paiement <i class="fa-solid fa-arrow-right ml-2"></i>
                        </button>
                    </div>
                </div>

                <!-- STEP 5: PAIEMENT -->
                <div class="step-panel hidden space-y-6" id="stepPanel5">
                    <div class="border-b border-gray-200 pb-4">
                        <h2 class="text-xl font-extrabold text-neutral">Étape 5 sur 6 : Récapitulatif & Paiement</h2>
                        <p class="text-xs text-gray-500 font-medium">Réglez votre commande via Mobile Money (MTN / Orange Money).</p>
                    </div>

                    <!-- Payment Method Selection -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-3">Choisissez votre mode de paiement Mobile Money *</label>
                        <div class="grid grid-cols-2 gap-4">
                            <label class="cursor-pointer p-4 rounded-2xl bg-white border border-gray-200 hover:border-yellow-400 has-[:checked]:border-yellow-500 has-[:checked]:bg-yellow-50 shadow-soft flex items-center gap-3">
                                <input type="radio" name="payment_method" value="MTN" checked onchange="switchPaymentMethod('MTN')" class="accent-yellow-500">
                                <div>
                                    <strong class="text-sm font-extrabold text-neutral block">MTN Mobile Money</strong>
                                    <span class="text-[11px] text-yellow-600 font-bold">Cameroun &bull; <?= htmlspecialchars($mtnPhone) ?></span>
                                </div>
                            </label>
                            <label class="cursor-pointer p-4 rounded-2xl bg-white border border-gray-200 hover:border-orange-500 has-[:checked]:border-orange-500 has-[:checked]:bg-orange-50 shadow-soft flex items-center gap-3">
                                <input type="radio" name="payment_method" value="ORANGE" onchange="switchPaymentMethod('ORANGE')" class="accent-orange-500">
                                <div>
                                    <strong class="text-sm font-extrabold text-neutral block">Orange Money</strong>
                                    <span class="text-[11px] text-orange-600 font-bold">Cameroun &bull; <?= htmlspecialchars($orangePhone) ?></span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- USSD Instructions Box -->
                    <div class="p-5 rounded-2xl bg-sky-50 border border-sky-200 space-y-3" id="ussdInstructionBox">
                        <strong class="text-xs font-bold text-neutral block">Instructions de Paiement Mobile :</strong>
                        <ol class="text-xs text-gray-700 space-y-1.5 list-decimal pl-4 font-medium">
                            <li>Composez le code USSD <code class="text-primary font-extrabold bg-white px-2 py-0.5 rounded border border-gray-200" id="ussdCodeDisplay"><?= htmlspecialchars($mtnUssd) ?></code> sur votre téléphone.</li>
                            <li>Effectuez le transfert du montant exact (<strong class="text-neutral font-black" id="ussdAmountDisplay">0 FCFA</strong>) vers le numéro marchand : <strong class="text-primary font-black" id="ussdPhoneDisplay"><?= htmlspecialchars($mtnPhone) ?></strong></li>
                            <li>Saisissez ci-dessous le numéro de téléphone émetteur et la référence / ID de la transaction reçue par SMS.</li>
                        </ol>
                        
                        <div class="pt-2">
                            <a id="ussdDialBtn" href="tel:*126%23" class="inline-flex items-center gap-2 bg-white hover:bg-gray-50 text-primary font-bold text-xs px-4 py-2 rounded-full border border-sky-200 shadow-sm">
                                <i class="fa-solid fa-phone"></i>
                                <span>Composer automatiquement le code USSD</span>
                            </a>
                        </div>
                    </div>

                    <!-- Payment Form Inputs -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5">Numéro de téléphone émetteur du paiement *</label>
                            <input type="tel" name="payment_phone" required class="w-full bg-white border border-gray-300 rounded-xl px-4 py-3 text-sm text-neutral focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20" placeholder="Ex: 670000000">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5">Référence / ID de transaction *</label>
                            <input type="text" name="transaction_ref" required class="w-full bg-white border border-gray-300 rounded-xl px-4 py-3 text-sm text-neutral focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20" placeholder="Ex: Tx123456789 ou Ref SMS">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1.5">Capture / Preuve de paiement (Optionnel)</label>
                        <input type="file" name="payment_proof" accept="image/*,.pdf" class="w-full text-xs text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-gray-100 file:text-neutral hover:file:bg-gray-200">
                    </div>

                    <div class="flex justify-between pt-4">
                        <button type="button" onclick="prevStep(4)" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-sm px-6 py-3.5 rounded-full border border-gray-300">
                            <i class="fa-solid fa-arrow-left mr-2"></i> Précédent
                        </button>
                        <button type="submit" id="submitOrderBtn" class="bg-primary hover:bg-sky-600 text-white font-extrabold text-sm px-8 py-3.5 rounded-full shadow-glow">
                            <i class="fa-solid fa-check-circle mr-2"></i> J'ai effectué le paiement & Valider
                        </button>
                    </div>
                </div>

                <!-- STEP 6: CONFIRMATION -->
                <div class="step-panel hidden space-y-6 text-center py-8" id="stepPanel6">
                    <div class="w-20 h-20 rounded-full bg-sky-50 text-primary border-4 border-primary flex items-center justify-center font-black text-3xl mx-auto shadow-glow">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>

                    <div class="space-y-2">
                        <h2 class="text-2xl sm:text-3xl font-black text-neutral">Votre commande a bien été enregistrée !</h2>
                        <p class="text-xs text-gray-500 font-medium">Référence unique générée :</p>
                        <div class="inline-block bg-white border-2 border-primary text-primary text-2xl font-mono font-black px-6 py-2 rounded-2xl shadow-sm" id="confOrderRef">
                            HM-LI-XXXXXX
                        </div>
                    </div>

                    <div class="max-w-md mx-auto p-5 rounded-2xl bg-bg border border-gray-200 text-left space-y-2 text-xs text-gray-700">
                        <div class="flex justify-between"><span>Statut du paiement :</span><strong class="text-warning font-bold" id="confPaymentStatus">PAIEMENT EN VÉRIFICATION</strong></div>
                        <div class="flex justify-between"><span>Statut de la commande :</span><strong class="text-neutral font-bold" id="confOrderStatus">COMMANDE REÇUE</strong></div>
                        <div class="flex justify-between pt-2 border-t border-gray-200"><span>Montant total :</span><strong class="text-neutral font-black text-sm" id="confTotalAmount">0 FCFA</strong></div>
                    </div>

                    <p class="text-xs text-gray-600 max-w-lg mx-auto leading-relaxed font-medium">
                        Notre équipe vérifie votre règlement et commence l'optimisation de votre profil. Vous pouvez suivre l'avancement en temps réel ou contacter le support HireMe.
                    </p>

                    <div class="flex flex-col sm:flex-row items-center justify-center gap-4 pt-4">
                        <a id="confWhatsAppBtn" href="#" target="_blank" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-accent hover:bg-emerald-600 text-white font-extrabold text-sm px-6 py-3.5 rounded-full shadow-md">
                            <i class="fa-brands fa-whatsapp text-lg"></i>
                            <span>Confirmer sur WhatsApp</span>
                        </a>
                        <a id="confTrackBtn" href="#" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-white hover:bg-gray-100 text-neutral font-bold text-sm px-6 py-3.5 rounded-full border border-gray-300 shadow-sm">
                            <i class="fa-solid fa-eye text-primary"></i>
                            <span>Suivre ma commande</span>
                        </a>
                    </div>
                </div>

            </div>

            <!-- RIGHT COLUMN: STICKY ORDER SUMMARY (MonCvPro Style) -->
            <div class="lg:col-span-4 lg:sticky lg:top-24 space-y-6">
                <div class="bg-white border border-gray-200 rounded-3xl p-6 shadow-soft space-y-4">
                    <div class="flex justify-between items-center border-b border-gray-200 pb-3">
                        <h3 class="text-xs font-extrabold uppercase tracking-wider text-primary">VOTRE COMMANDE</h3>
                        <span class="text-[10px] text-gray-500 bg-gray-100 px-2 py-0.5 rounded font-mono font-bold">EN DIRECT</span>
                    </div>

                    <!-- Itemized Sub-totals -->
                    <div class="space-y-2 text-xs text-gray-600 font-medium" id="summaryItemsList">
                        <div class="flex justify-between">
                            <span id="sumPackageName">Pack Pro</span>
                            <strong class="text-neutral font-bold" id="sumPackagePrice">10 000 FCFA</strong>
                        </div>
                    </div>

                    <div id="sumBundleDiscountRow" class="hidden flex justify-between text-xs text-accent pt-2 border-t border-gray-200 font-bold">
                        <span>Réduction Pack Visibilité</span>
                        <span>-3 600 FCFA</span>
                    </div>

                    <!-- Total Row -->
                    <div class="pt-4 border-t border-gray-200 flex justify-between items-center">
                        <div>
                            <span class="text-xs text-gray-500 block font-bold">TOTAL À PAYER</span>
                            <span class="text-[10px] text-gray-400">Toutes taxes incluses</span>
                        </div>
                        <strong class="text-2xl font-black text-primary" id="sumTotalPrice">10 000 FCFA</strong>
                    </div>
                </div>
            </div>

        </form>

    </div>

    <script>
        let currentStep = 1;
        let isBundleActive = false;
        let activeBundlePrice = 32000;
        const mtnPhone = "<?= htmlspecialchars($mtnPhone) ?>";
        const mtnUssd = "<?= htmlspecialchars($mtnUssd) ?>";
        const orangePhone = "<?= htmlspecialchars($orangePhone) ?>";
        const orangeUssd = "<?= htmlspecialchars($orangeUssd) ?>";

        function updateStepIndicators(step) {
            for (let i = 1; i <= 6; i++) {
                const ind = document.getElementById(`stepInd${i}`);
                if (!ind) continue;
                const badge = ind.querySelector('span');
                if (!badge) continue;
                if (i < step) {
                    ind.className = "flex items-center gap-2 text-accent font-bold";
                    badge.className = "w-8 h-8 rounded-full bg-accent text-white flex items-center justify-center font-extrabold shadow-sm";
                    badge.innerHTML = '<i class="fa-solid fa-check text-xs"></i>';
                } else if (i === step) {
                    ind.className = "flex items-center gap-2 text-primary font-bold";
                    badge.className = "w-8 h-8 rounded-full bg-primary text-white flex items-center justify-center font-extrabold shadow-sm";
                    badge.innerText = i;
                } else {
                    ind.className = "flex items-center gap-2 text-gray-400 font-bold";
                    badge.className = "w-8 h-8 rounded-full bg-gray-100 text-gray-400 flex items-center justify-center font-extrabold";
                    badge.innerText = i;
                }
            }
        }

        function showStep(step) {
            document.querySelectorAll('.step-panel').forEach(p => p.classList.add('hidden'));
            const panel = document.getElementById(`stepPanel${step}`);
            if (panel) panel.classList.remove('hidden');
            const errAlert = document.getElementById('errorAlert');
            if (errAlert) errAlert.classList.add('hidden');
            currentStep = step;
            updateStepIndicators(step);
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function nextStep(targetStep) {
            const currentPanel = document.getElementById(`stepPanel${currentStep}`);
            if (currentPanel) {
                const requiredInputs = currentPanel.querySelectorAll('input[required], select[required]');
                let valid = true;
                requiredInputs.forEach(input => {
                    if (!input.value.trim()) {
                        valid = false;
                        input.classList.add('border-red-500');
                    } else {
                        input.classList.remove('border-red-500');
                    }
                });

                if (!valid) {
                    showError('Veuillez remplir tous les champs obligatoires avant de continuer.');
                    return;
                }
            }

            if (currentStep === 2) {
                applySmartRecommendations();
            }

            if (currentStep === 4) {
                calculateTotal();
            }

            showStep(targetStep);
        }

        function prevStep(targetStep) {
            showStep(targetStep);
        }

        function showError(msg) {
            const elMsg = document.getElementById('errorMessage');
            const elAlert = document.getElementById('errorAlert');
            if (elMsg) elMsg.innerText = msg;
            if (elAlert) elAlert.classList.remove('hidden');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function showToast(text) {
            const toast = document.getElementById('toastNotification');
            const toastText = document.getElementById('toastText');
            if (toast && toastText) {
                toastText.innerText = text;
                toast.classList.remove('hidden');
                setTimeout(() => toast.classList.add('hidden'), 2500);
            }
        }

        function toggleDeliverables(btn) {
            const list = btn.nextElementSibling;
            const icon = btn.querySelector('i');
            if (list) {
                list.classList.toggle('hidden');
                if (icon) {
                    icon.classList.toggle('rotate-180');
                }
            }
        }

        function handleCvFileChange(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const fn = document.getElementById('cvFileName');
                const fs = document.getElementById('cvFileSize');
                const fp = document.getElementById('cvFilePreview');
                if (fn) fn.innerText = file.name;
                if (fs) fs.innerText = (file.size / (1024 * 1024)).toFixed(2) + ' MB';
                if (fp) fp.classList.remove('hidden');
            }
        }

        function removeCvFile() {
            const fi = document.getElementById('cvFileInput');
            const fp = document.getElementById('cvFilePreview');
            if (fi) fi.value = '';
            if (fp) fp.classList.add('hidden');
        }

        // Smart Recommendation Engine sorting algorithm
        function applySmartRecommendations() {
            const selectedObjRadio = document.querySelector('input[name="main_objective"]:checked');
            if (!selectedObjRadio) return;
            const obj = selectedObjRadio.value;

            const cards = Array.from(document.querySelectorAll('.upsell-card'));
            
            cards.forEach(card => {
                const targets = JSON.parse(card.getAttribute('data-objectives') || '[]');
                if (targets.includes(obj)) {
                    card.classList.add('priority-recommended');
                } else {
                    card.classList.remove('priority-recommended');
                }
            });

            // Sort cards: priority-recommended first
            cards.sort((a, b) => {
                const aPrio = a.classList.contains('priority-recommended') ? 1 : 0;
                const bPrio = b.classList.contains('priority-recommended') ? 1 : 0;
                return bPrio - aPrio;
            });

            const container = document.getElementById('upsellProductsContainer');
            if (container) {
                cards.forEach(card => container.appendChild(card));
            }
        }

        function toggleMoreUpsells() {
            const extraCards = document.querySelectorAll('.extra-upsell');
            const icon = document.getElementById('moreUpsellsIcon');
            const btnText = document.querySelector('#showMoreUpsellsBtn span');

            extraCards.forEach(c => c.classList.toggle('hidden'));
            if (icon && btnText) {
                if (icon.classList.contains('rotate-180')) {
                    icon.classList.remove('rotate-180');
                    btnText.innerText = "Voir plus de ressources HireMe";
                } else {
                    icon.classList.add('rotate-180');
                    btnText.innerText = "Réduire la liste";
                }
            }
        }

        function toggleBundle(bundleId, bundlePrice) {
            const btn = document.getElementById('selectBundleBtn');
            const bundleInput = document.getElementById('bundleIdInput');

            if (!isBundleActive) {
                isBundleActive = true;
                if (bundleInput) bundleInput.value = bundleId;
                activeBundlePrice = bundlePrice;
                if (btn) {
                    btn.innerText = "Pack Visibilité Pro Sélectionné ✓";
                    btn.className = "w-full sm:w-auto bg-accent text-white font-extrabold text-xs px-6 py-3.5 rounded-full shadow-md transition-all shrink-0";
                }
                showToast("Pack Visibilité Pro appliqué à votre commande !");
            } else {
                isBundleActive = false;
                if (bundleInput) bundleInput.value = 0;
                if (btn) {
                    btn.innerText = "Choisir le Pack Visibilité Pro";
                    btn.className = "w-full sm:w-auto bg-primary hover:bg-sky-600 text-white font-extrabold text-xs px-6 py-3.5 rounded-full shadow-glow transition-all shrink-0";
                }
            }
            calculateTotal();
        }

        function toggleProductBump(checkbox) {
            const card = checkbox.closest('.upsell-card');
            const label = card ? card.querySelector('.bump-btn-label') : null;
            if (checkbox.checked) {
                if (label) {
                    label.innerText = "Ajouté ✓";
                    label.className = "bump-btn-label text-accent font-bold";
                }
                showToast(`Ajouté : ${checkbox.getAttribute('data-name')} ✓`);
            } else {
                if (label) {
                    label.innerText = "Ajouter";
                    label.className = "bump-btn-label text-gray-700 font-bold";
                }
            }
            calculateTotal();
        }

        function calculateTotal() {
            let total = 0;
            let pkgName = '';
            let pkgPrice = 0;

            const summaryContainer = document.getElementById('summaryItemsList');
            if (!summaryContainer) return;
            summaryContainer.innerHTML = '';

            if (isBundleActive) {
                pkgName = "PACK VISIBILITÉ PRO";
                pkgPrice = activeBundlePrice;
                total += pkgPrice;

                const item = document.createElement('div');
                item.className = 'flex justify-between font-bold text-neutral';
                item.innerHTML = `<span>${pkgName}</span><span>${pkgPrice.toLocaleString('fr-FR')} FCFA</span>`;
                summaryContainer.appendChild(item);

                const discountRow = document.getElementById('sumBundleDiscountRow');
                if (discountRow) discountRow.classList.remove('hidden');
            } else {
                const discountRow = document.getElementById('sumBundleDiscountRow');
                if (discountRow) discountRow.classList.add('hidden');

                const selectedPkgRadio = document.querySelector('input[name="package_id"]:checked');
                if (selectedPkgRadio) {
                    pkgPrice = parseFloat(selectedPkgRadio.getAttribute('data-price')) || 0;
                    pkgName = selectedPkgRadio.getAttribute('data-name');
                    total += pkgPrice;

                    const item = document.createElement('div');
                    item.className = 'flex justify-between font-bold text-neutral';
                    item.innerHTML = `<span>${pkgName}</span><span>${pkgPrice.toLocaleString('fr-FR')} FCFA</span>`;
                    summaryContainer.appendChild(item);
                }
            }

            // Upsell Products
            const selectedProds = document.querySelectorAll('input[name="product_ids[]"]:checked');
            selectedProds.forEach(pr => {
                const p = parseFloat(pr.getAttribute('data-price')) || 0;
                const n = pr.getAttribute('data-name');
                total += p;

                const item = document.createElement('div');
                item.className = 'flex justify-between text-xs text-gray-600 font-medium';
                item.innerHTML = `<span>+ ${n}</span><span class="text-neutral font-bold">+ ${p.toLocaleString('fr-FR')} FCFA</span>`;
                summaryContainer.appendChild(item);
            });

            // Addons
            const selectedAddons = document.querySelectorAll('input[name="addon_ids[]"]:checked');
            selectedAddons.forEach(ad => {
                const p = parseFloat(ad.getAttribute('data-price')) || 0;
                const n = ad.getAttribute('data-name');
                total += p;

                const item = document.createElement('div');
                item.className = 'flex justify-between text-xs text-gray-600 font-medium';
                item.innerHTML = `<span>+ ${n}</span><span class="text-neutral font-bold">+ ${p.toLocaleString('fr-FR')} FCFA</span>`;
                summaryContainer.appendChild(item);
            });

            const formattedTotal = total.toLocaleString('fr-FR') + ' FCFA';
            
            const elPkgName = document.getElementById('sumPackageName');
            if (elPkgName) elPkgName.innerText = pkgName;

            const elPkgPrice = document.getElementById('sumPackagePrice');
            if (elPkgPrice) elPkgPrice.innerText = pkgPrice.toLocaleString('fr-FR') + ' FCFA';

            const elTotalPrice = document.getElementById('sumTotalPrice');
            if (elTotalPrice) elTotalPrice.innerText = formattedTotal;

            const elUssdAmount = document.getElementById('ussdAmountDisplay');
            if (elUssdAmount) elUssdAmount.innerText = formattedTotal;
        }

        function switchPaymentMethod(method) {
            const codeDisp = document.getElementById('ussdCodeDisplay');
            const phoneDisp = document.getElementById('ussdPhoneDisplay');
            const dialBtn = document.getElementById('ussdDialBtn');

            if (method === 'MTN') {
                if (codeDisp) codeDisp.innerText = mtnUssd;
                if (phoneDisp) phoneDisp.innerText = mtnPhone;
                if (dialBtn) dialBtn.href = `tel:${encodeURIComponent(mtnUssd)}`;
            } else {
                if (codeDisp) codeDisp.innerText = orangeUssd;
                if (phoneDisp) phoneDisp.innerText = orangePhone;
                if (dialBtn) dialBtn.href = `tel:${encodeURIComponent(orangeUssd)}`;
            }
        }

        // Submit via AJAX
        const orderFormEl = document.getElementById('orderForm');
        if (orderFormEl) {
            orderFormEl.addEventListener('submit', function(e) {
                e.preventDefault();
                const btn = document.getElementById('submitOrderBtn');
                if (btn) {
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> Traitement de la commande...';
                }

                const formData = new FormData(this);
                const apiUrl = '/api/linkedin/submit_order.php';

                fetch(apiUrl, {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const refEl = document.getElementById('confOrderRef');
                        const totEl = document.getElementById('confTotalAmount');
                        const waBtn = document.getElementById('confWhatsAppBtn');
                        const trkBtn = document.getElementById('confTrackBtn');
                        const sumTot = document.getElementById('sumTotalPrice');

                        if (refEl) refEl.innerText = data.reference;
                        if (totEl && sumTot) totEl.innerText = sumTot.innerText;
                        
                        const waText = encodeURIComponent(`Bonjour HireMe, je viens de passer une commande d'optimisation LinkedIn. Ma référence est ${data.reference}.`);
                        if (waBtn) waBtn.href = `https://wa.me/237654250688?text=${waText}`;
                        if (trkBtn) trkBtn.href = data.redirect_url;

                        showStep(6);
                    } else {
                        showError(data.message || 'Une erreur est survenue lors de l\'enregistrement.');
                        if (btn) {
                            btn.disabled = false;
                            btn.innerHTML = '<i class="fa-solid fa-check-circle mr-2"></i> J\'ai effectué le paiement & Valider';
                        }
                    }
                })
                .catch(err => {
                    showError('Erreur réseau ou serveur. Veuillez réessayer.');
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fa-solid fa-check-circle mr-2"></i> J\'ai effectué le paiement & Valider';
                    }
                });
            });
        }

        // Initial Calculations
        calculateTotal();
    </script>
</body>
</html>
