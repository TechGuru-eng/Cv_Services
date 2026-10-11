<?php
// reservation.php
require_once 'admin/config/db.php';
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
    <title>Réservez votre CV à l'avance — HireMe CV Services</title>
    <link rel="canonical" href="https://moncvpro.hiremeguide.com/reservation.php" />
    <meta name="description" content="Anticipez votre succès. Commandez votre CV aujourd'hui et recevez-le à la date de votre choix. Paiement à la livraison.">

    <!-- Favicons -->
    <!-- Social Media Preview (Open Graph) -->
    <meta property="og:title" content="Réservez votre CV d'Excellence — HireMe CV" />
    <meta property="og:description" content="Anticipez votre succès. Commandez votre CV aujourd'hui et recevez-le à la date de votre choix. Paiement à la livraison." />
    <meta property="og:image" content="https://moncvpro.hiremeguide.com/assets/reservation-preview.png" />
    <meta property="og:url" content="https://moncvpro.hiremeguide.com/reservation" />
    <meta property="og:type" content="website" />
    
    <!-- Twitter Preview -->
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="Réservez votre CV d'Excellence — HireMe CV" />
    <meta name="twitter:description" content="Commandez votre CV aujourd'hui et recevez-le à la date de votre choix." />
    <meta name="twitter:image" content="https://moncvpro.hiremeguide.com/assets/reservation-preview.png" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        primary: '#0ea5e9',
                        accent: '#22c55e',
                        danger: '#ef4444',
                        neutral: '#111827',
                        bg: '#f8fafc'
                    }
                }
            }
        }
    </script>
    <style>
        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }
        .step-inactive { opacity: 0.5; pointer-events: none; }
    </style>
    <script src="assets/js/site-language.js?v=4" defer></script>

    <!-- Shared HireMe favicon -->
    <link rel="icon" type="image/png" sizes="48x48" href="assets/favicons/favicon-48.png?v=20261011">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicons/favicon-32.png?v=20261011">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/favicons/favicon-16.png?v=20261011">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/favicons/apple-touch-icon-180.png?v=20261011">
</head>

<body class="bg-bg text-neutral antialiased font-sans flex flex-col min-h-screen">

    <header class="bg-white border-b border-gray-100 sticky top-0 z-50 glass-effect">
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

    <main class="flex-grow py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto">
            <div class="text-center mb-12">
                <h1 class="text-3xl sm:text-4xl font-extrabold text-neutral mb-4">Commandez votre CV en avance</h1>
                <p class="text-lg text-gray-500 max-w-2xl mx-auto">
                    Vous avez besoin de votre CV pour une date précise ? Anticipez et laissez nos experts travailler pour vous. <br>
                    <span class="text-primary font-semibold">Paiement de 50% d'acompte requis à la commande pour validation.</span>
                </p>
            </div>

            <form id="reservationForm" class="space-y-12" enctype="multipart/form-data">
                
                <!-- STEP 1: Package & Date -->
                <section class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 md:p-8">
                    <h2 class="text-xl font-bold mb-6 flex items-center">
                        <span class="w-8 h-8 bg-primary text-white rounded-full flex items-center justify-center mr-3 text-sm">1</span>
                        Choix du Pack & Date
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-4">Sélectionnez votre Pack</label>
                            <div class="grid grid-cols-1 gap-3">
                                <label class="relative flex items-center p-4 border rounded-xl cursor-pointer hover:border-primary transition-all group">
                                    <input type="radio" name="package" value="Standard" data-price="2600" checked class="peer sr-only">
                                    <div class="flex-grow">
                                        <div class="font-bold text-gray-900 group-hover:text-primary">Pack Standard</div>
                                        <div class="text-xs text-gray-500">Pour les besoins essentiels</div>
                                    </div>
                                    <div class="text-lg font-extrabold text-primary">2 600 FCFA</div>
                                    <div class="absolute inset-0 border-2 border-primary rounded-xl opacity-0 peer-checked:opacity-100 pointer-events-none"></div>
                                </label>
                                
                                <label class="relative flex items-center p-4 border rounded-xl cursor-pointer hover:border-primary transition-all group">
                                    <input type="radio" name="package" value="Pro" data-price="5100" class="peer sr-only">
                                    <div class="flex-grow">
                                        <div class="font-bold text-gray-900 group-hover:text-primary">Pack Pro</div>
                                        <div class="text-xs text-gray-500">Idéal pour booster sa carrière</div>
                                    </div>
                                    <div class="text-lg font-extrabold text-primary">5 100 FCFA</div>
                                    <div class="absolute inset-0 border-2 border-primary rounded-xl opacity-0 peer-checked:opacity-100 pointer-events-none"></div>
                                </label>

                                <label class="relative flex items-center p-4 border rounded-xl cursor-pointer hover:border-primary transition-all group">
                                    <input type="radio" name="package" value="Senior" data-price="12500" class="peer sr-only">
                                    <div class="flex-grow">
                                        <div class="font-bold text-gray-900 group-hover:text-primary">Pack Senior</div>
                                        <div class="text-xs text-gray-500">Expertise confirmée</div>
                                    </div>
                                    <div class="text-lg font-extrabold text-primary">12 500 FCFA</div>
                                    <div class="absolute inset-0 border-2 border-primary rounded-xl opacity-0 peer-checked:opacity-100 pointer-events-none"></div>
                                </label>

                                <label class="relative flex items-center p-4 border rounded-xl cursor-pointer hover:border-primary transition-all group">
                                    <input type="radio" name="package" value="Expert" data-price="35500" class="peer sr-only">
                                    <div class="flex-grow">
                                        <div class="font-bold text-gray-900 group-hover:text-primary">Pack Expert</div>
                                        <div class="text-xs text-gray-500">Carrière internationale</div>
                                    </div>
                                    <div class="text-lg font-extrabold text-primary">35 500 FCFA</div>
                                    <div class="absolute inset-0 border-2 border-primary rounded-xl opacity-0 peer-checked:opacity-100 pointer-events-none"></div>
                                </label>
                            </div>
                        </div>

                        <div>
                            <label for="dueDate" class="block text-sm font-semibold text-gray-700 mb-4">Quand souhaitez-vous votre CV ?</label>
                            <input type="date" id="dueDate" name="due_date" required min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                                class="w-full rounded-xl border-gray-200 border p-4 text-lg font-medium focus:ring-primary focus:border-primary">
                            <p class="mt-4 text-sm text-gray-500 italic">
                                💡 Un expert vous contactera sur WhatsApp pour confirmer le délai.
                            </p>
                        </div>
                    </div>
                </section>

                <!-- STEP 2: Client Info -->
                <section class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 md:p-8">
                    <h2 class="text-xl font-bold mb-6 flex items-center">
                        <span class="w-8 h-8 bg-primary text-white rounded-full flex items-center justify-center mr-3 text-sm">2</span>
                        Vos Coordonnées
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nom complet</label>
                            <input type="text" name="name" required class="w-full rounded-lg border-gray-300 border p-2.5 focus:ring-primary focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input type="email" name="email" required class="w-full rounded-lg border-gray-300 border p-2.5 focus:ring-primary focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">WhatsApp <span class="text-xs text-gray-400">(Ex: 654250688)</span></label>
                            <input type="tel" name="whatsapp" required class="w-full rounded-lg border-gray-300 border p-2.5 focus:ring-primary focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Ville & Pays</label>
                            <input type="text" name="location" required placeholder="Ex: Douala, Cameroun" class="w-full rounded-lg border-gray-300 border p-2.5 focus:ring-primary focus:border-primary">
                        </div>
                    </div>
                </section>

                <!-- STEP 3: CV Content -->
                <section class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 md:p-8">
                    <h2 class="text-xl font-bold mb-6 flex items-center">
                        <span class="w-8 h-8 bg-primary text-white rounded-full flex items-center justify-center mr-3 text-sm">3</span>
                        Détails de votre parcours
                    </h2>
                    
                    <div class="space-y-8">
                        <div>
                            <p class="text-sm font-medium text-gray-700 mb-4">Avez-vous déjà un CV ?</p>
                            <div class="flex space-x-6">
                                <label class="flex items-center space-x-2 cursor-pointer">
                                    <input type="radio" name="hasCv" value="yes" onchange="toggleCvFlow('yes')" class="text-primary focus:ring-primary">
                                    <span class="text-sm">Oui, j'ai un ancien CV</span>
                                </label>
                                <label class="flex items-center space-x-2 cursor-pointer">
                                    <input type="radio" name="hasCv" value="no" checked onchange="toggleCvFlow('no')" class="text-primary focus:ring-primary">
                                    <span class="text-sm">Non, je pars de zéro</span>
                                </label>
                            </div>
                        </div>

                        <!-- Upload Flow -->
                        <div id="flowUpload" class="hidden">
                             <div class="bg-blue-50 p-6 rounded-xl border border-blue-100">
                                <label class="block text-sm font-bold text-blue-900 mb-2">Téléchargez votre CV actuel</label>
                                <input type="file" name="cvFile" id="cvFile" accept=".pdf,.doc,.docx" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-100 file:text-blue-700 hover:file:bg-blue-200">
                                <p class="text-xs text-gray-500 mt-2 italic">Format PDF, Word. Max 5Mo.</p>
                            </div>
                            <div class="mt-6">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Commentaires additionnels</label>
                                <textarea name="instructions" rows="4" class="w-full rounded-lg border-gray-300 border p-3 focus:ring-primary focus:border-primary" placeholder="Instructions particulières..."></textarea>
                            </div>
                        </div>

                        <!-- Manual Entry Flow -->
                        <div id="flowManual" class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Poste ou Domaine Visé</label>
                                <input type="text" name="targetJob" class="w-full rounded-lg border-gray-300 border p-2.5 focus:ring-primary focus:border-primary" placeholder="Ex: Comptable Senior, Développeur Web...">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Expériences Professionnelles</label>
                                <textarea name="experience" rows="5" class="w-full rounded-lg border-gray-300 border p-3 focus:ring-primary focus:border-primary" placeholder="Décrivez vos postes précédents..."></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Éducation & Diplômes</label>
                                <textarea name="education" rows="3" class="w-full rounded-lg border-gray-300 border p-3 focus:ring-primary focus:border-primary" placeholder="Listez vos diplômes..."></textarea>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Compétences</label>
                                    <textarea name="skills" rows="2" class="w-full rounded-lg border-gray-300 border p-2.5 focus:ring-primary focus:border-primary" placeholder="Excel, Management, etc."></textarea>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Langues</label>
                                    <input type="text" name="languages" class="w-full rounded-lg border-gray-300 border p-2.5 focus:ring-primary focus:border-primary" placeholder="Français, Anglais...">
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Profil LinkedIn (Optionnel)</label>
                            <input type="url" name="linkedin" placeholder="https://linkedin.com/in/..." class="w-full rounded-lg border-gray-300 border p-2.5 focus:ring-primary focus:border-primary">
                        </div>
                    </div>
                </section>

                <div class="pt-8">
                    <button type="submit" id="submitBtn" class="w-full bg-primary text-white font-bold py-5 rounded-2xl shadow-xl hover:bg-blue-600 transition-all transform hover:scale-[1.01] flex items-center justify-center text-xl">
                        Confirmer ma Réservation
                        <svg class="w-6 h-6 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </button>
                    <p class="text-center text-gray-500 text-sm mt-6">
                        🔒 En validant, vous acceptez d'être recontacté par nos services sur WhatsApp.
                    </p>
                </div>

            </form>
        </div>
    </main>

    <footer class="bg-white border-t border-gray-100 py-12">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <p class="text-gray-400 text-sm">HireMe CV Services — Propulsez votre carrière pour 2026.</p>
        </div>
    </footer>

    <script>
        window.addEventListener('load', async () => {
            try {
                const response = await fetch('api/get_visitor_config.php');
                const config = await response.json();
                const whatsappInput = document.querySelector('input[name="whatsapp"]');
                if (whatsappInput && !whatsappInput.value && config.prefix) {
                    whatsappInput.value = config.prefix;
                }
            } catch (e) {
                console.log("Config load failed", e);
            }
        });

        function toggleCvFlow(hasCv) {
            const flowUpload = document.getElementById('flowUpload');
            const flowManual = document.getElementById('flowManual');
            const fileInput = document.getElementById('cvFile');

            if (hasCv === 'yes') {
                flowUpload.classList.remove('hidden');
                flowManual.classList.add('hidden');
                fileInput.required = true;
            } else {
                flowUpload.classList.add('hidden');
                flowManual.classList.remove('hidden');
                fileInput.required = false;
            }
        }

        document.getElementById('reservationForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const btn = document.getElementById('submitBtn');
            const originalContent = btn.innerHTML;
            
            btn.disabled = true;
            btn.innerHTML = `<svg class="animate-spin h-5 w-5 text-white mr-3" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Enregistrement...`;

            const formData = new FormData(e.target);

            try {
                const response = await fetch('save-reservation.php', {
                    method: 'POST',
                    body: formData
                });

                // Check content type
                const contentType = response.headers.get("content-type");
                if (!contentType || !contentType.includes("application/json")) {
                    const text = await response.text();
                    console.error("Non-JSON Response:", text);
                    // Show a preview of the error (first 100 chars)
                    throw new Error("Réponse serveur invalide (Non-JSON): " + text.substring(0, 100));
                }

                if (!response.ok) {
                    throw new Error(`Erreur HTTP: ${response.status}`);
                }

                const result = await response.json();

                if (result.success) {
                    window.location.href = `reservation-payment.php?ref=${encodeURIComponent(result.order_ref)}`;
                } else {
                    alert(result.message || "Une erreur est survenue.");
                    btn.disabled = false;
                    btn.innerHTML = originalContent;
                }
            } catch (err) {
                console.error(err);
                alert("Erreur: " + err.message);
                btn.disabled = false;
                btn.innerHTML = originalContent;
            }
        });
    </script>
</body>
</html>
