<?php
// linkedin/diagnostic.php
// Standalone LinkedIn Profile Assessment Page

require_once __DIR__ . '/../admin/config/db.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<?php $linkedinBase = preg_replace('~/(?:linkedin(?:/.*)?|suivi-linkedin)/?$~', '/linkedin/', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)); ?>
    <base href="<?= htmlspecialchars($linkedinBase, ENT_QUOTES, 'UTF-8') ?>">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <script src="../assets/js/site-language.js?v=6" defer></script>
<title>Diagnostic gratuit de profil LinkedIn | HireMe</title>
    <meta name="description" content="Évaluez gratuitement votre profil LinkedIn en 7 questions. Découvrez votre score de visibilité et les points à améliorer pour présenter votre parcours.">
    <meta name="robots" content="index, follow, max-image-preview:large">
    <link rel="canonical" href="https://moncvpro.hiremeguide.com/linkedin/diagnostic">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="fr_FR">
    <meta property="og:site_name" content="HireMe">
    <meta property="og:title" content="Diagnostic gratuit de profil LinkedIn | HireMe">
    <meta property="og:description" content="Évaluez gratuitement votre profil LinkedIn en 7 questions. Découvrez votre score de visibilité et les points à améliorer pour présenter votre parcours.">
    <meta property="og:image" content="https://moncvpro.hiremeguide.com/assets/images/linkedin/linkedin-workspace.jpg">
    <meta property="og:image:alt" content="Espace de travail professionnel — HireMe LinkedIn">
    <meta property="og:url" content="https://moncvpro.hiremeguide.com/linkedin/diagnostic">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Diagnostic gratuit de profil LinkedIn | HireMe">
    <meta name="twitter:description" content="Évaluez gratuitement votre profil LinkedIn en 7 questions. Découvrez votre score de visibilité et les points à améliorer pour présenter votre parcours.">
    <meta name="twitter:image" content="https://moncvpro.hiremeguide.com/assets/images/linkedin/linkedin-workspace.jpg">
    <meta name="twitter:image:alt" content="Espace de travail professionnel — HireMe LinkedIn">
    <script type="application/ld+json">{"@context":"https://schema.org","@type":"WebPage","name":"Diagnostic gratuit de profil LinkedIn | HireMe","description":"Évaluez gratuitement votre profil LinkedIn en 7 questions. Découvrez votre score de visibilité et les points à améliorer pour présenter votre parcours.","url":"https://moncvpro.hiremeguide.com/linkedin/diagnostic","inLanguage":"fr","isPartOf":{"@type":"WebSite","name":"HireMe","url":"https://moncvpro.hiremeguide.com/"}}</script>

    <!-- Shared HireMe favicon -->
    <link rel="icon" type="image/png" sizes="48x48" href="../assets/favicons/favicon-48.png?v=20261011">
    <link rel="icon" type="image/png" sizes="32x32" href="../assets/favicons/favicon-32.png?v=20261011">
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/favicons/favicon-16.png?v=20261011">
    <link rel="apple-touch-icon" sizes="180x180" href="../assets/favicons/apple-touch-icon-180.png?v=20261011">
</head>
<body class="bg-slate-950 text-slate-100 font-sans min-h-screen py-12 px-4">
    <div class="max-w-3xl mx-auto space-y-8">
        <div class="text-center space-y-3">
            <a href="index.php" class="inline-flex items-center gap-2 font-black text-2xl text-white">
                <span class="w-9 h-9 rounded-xl bg-yellow-400 text-slate-950 flex items-center justify-center text-lg">H</span>
                Hire<span class="text-yellow-400">Me</span>
            </a>
            <h1 class="text-3xl font-black text-white">Évaluation de Profil LinkedIn</h1>
            <p class="text-xs text-slate-400">Répondez aux 7 questions pour découvrir votre score de visibilité et d'impact.</p>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 md:p-8">
            <form id="diagForm" class="space-y-6">
                <?php
                $questions = [
                    "1. Avez-vous une photo professionnelle nette et éclairée ?",
                    "2. Votre titre LinkedIn contient-il votre poste visé et vos mots-clés ?",
                    "3. Votre section 'Infos' est-elle rédigée et structurée en paragraphes ?",
                    "4. Vos expériences décrivent-elles vos résultats et accomplissements ?",
                    "5. Avez-vous listé au moins 10 compétences métier clés ?",
                    "6. Votre profil contient-il des termes utilisés par les recruteurs de votre secteur ?",
                    "7. Votre profil est-il parfaitement à jour avec vos compétences actuelles ?"
                ];
                foreach ($questions as $idx => $q):
                ?>
                <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 space-y-3">
                    <p class="text-sm font-semibold text-white"><?= $q ?></p>
                    <div class="flex gap-4">
                        <label class="cursor-pointer text-xs font-bold bg-slate-800 text-slate-300 px-4 py-2 rounded-lg has-[:checked]:bg-yellow-400 has-[:checked]:text-slate-950">
                            <input type="radio" name="q<?= $idx ?>" value="15" class="hidden" required> Oui
                        </label>
                        <label class="cursor-pointer text-xs font-bold bg-slate-800 text-slate-300 px-4 py-2 rounded-lg has-[:checked]:bg-red-500 has-[:checked]:text-white">
                            <input type="radio" name="q<?= $idx ?>" value="0" class="hidden"> Non / Incertain
                        </label>
                    </div>
                </div>
                <?php endforeach; ?>

                <button type="button" onclick="runDiagnostic()" class="w-full bg-yellow-400 hover:bg-yellow-300 text-slate-950 font-black py-4 rounded-xl shadow-lg">
                    Obtenir mon score
                </button>
            </form>

            <div id="resultBox" class="hidden mt-8 pt-8 border-t border-slate-800 text-center space-y-4">
                <div class="w-24 h-24 mx-auto rounded-full border-4 border-yellow-400 bg-slate-950 flex items-center justify-center text-3xl font-black text-white" id="scoreValue">
                    0 / 100
                </div>
                <h3 class="text-xl font-bold text-white" id="scoreTitle"></h3>
                <p class="text-xs text-slate-300 max-w-lg mx-auto" id="scoreText"></p>
                <p class="text-[10px] text-slate-500 italic">* Diagnostic indicatif non affilié officiellement à LinkedIn.</p>
                <div class="pt-4">
                    <a href="commander" class="inline-flex items-center gap-2 bg-yellow-400 text-slate-950 font-black text-sm px-8 py-3.5 rounded-xl shadow-lg">
                        <span>Optimiser mon profil avec HireMe</span>
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function runDiagnostic() {
            const inputs = document.querySelectorAll('#diagForm input[type="radio"]:checked');
            if (inputs.length < 7) {
                alert('Veuillez répondre à toutes les questions.');
                return;
            }
            let score = 0;
            inputs.forEach(i => score += parseInt(i.value));

            document.getElementById('resultBox').classList.remove('hidden');
            document.getElementById('scoreValue').innerText = `${score} / 100`;

            const t = document.getElementById('scoreTitle');
            const d = document.getElementById('scoreText');

            if (score <= 45) {
                t.innerText = "Votre profil manque fortement de visibilité.";
                d.innerText = "Votre profil ne ressort pas dans les recherches recruteurs. Une optimisation approfondie permettra de capter l'attention rapidement.";
            } else if (score <= 75) {
                t.innerText = "Votre profil peut être nettement amélioré.";
                d.innerText = "La structure existe, mais la mise en valeur des accomplissements et l'usage des mots-clés stratégiques nécessitent un renforcement.";
            } else {
                t.innerText = "Bonne présence ! Passez au niveau supérieur.";
                d.innerText = "Votre profil est déjà solide. Notre accompagnement permettra d'affiner votre Personal Branding et d'optimiser votre réseau.";
            }

            document.getElementById('resultBox').scrollIntoView({ behavior: 'smooth' });
        }
    </script>
</body>
</html>
