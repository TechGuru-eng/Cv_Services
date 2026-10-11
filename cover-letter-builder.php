<?php
/**
 * $1500 Premium Cover Letter Builder
 * Features matching templates (Paris, London, Berlin) and AI Generation via Gemini.
 */
session_start();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Générateur de Lettre de Motivation - HireMe</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,600;0,700;1,400&family=Playfair+Display:ital,wght@0,600;0,700;0,800;1,600&family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    <style>
        :root {
            --cv-primary: #0284c7;
        }

        /* Glassmorphism card */
        .glass-panel {
            background: rgba(15, 23, 42, 0.45);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        /* CV Paper Styling */
        .cv-paper {
            width: 210mm;
            min-height: 297mm;
            background: white;
            color: #1e293b;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            transition: all 0.3s ease;
            box-sizing: border-box;
            word-wrap: break-word;
            position: relative;
        }

        /* Spacing Presets */
        .cv-spacing-compact { padding: 10mm !important; }
        .cv-spacing-normal { padding: 15mm !important; }
        .cv-spacing-airy { padding: 20mm !important; }

        /* Font Pairings */
        .font-pair-modern { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-pair-classic { font-family: 'Lora', serif; }
        .font-pair-classic h1, .font-pair-classic .cv-name { font-family: 'Playfair Display', serif; }
        .font-pair-elegant { font-family: 'Inter', sans-serif; }
        .font-pair-elegant h1, .font-pair-elegant .cv-name { font-family: 'Outfit', sans-serif; }

        /* Color presets */
        .theme-blue { --cv-primary: #0284c7; }
        .theme-green { --cv-primary: #10b981; }
        .theme-purple { --cv-primary: #8b5cf6; }
        .theme-charcoal { --cv-primary: #1e293b; }
        .theme-burgundy { --cv-primary: #881337; }

        /* Print overrides */
        @media print {
            @page {
                margin: 0;
            }
            body { 
                background: white !important; 
                margin: 0 !important; 
                padding: 0 !important; 
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .no-print { display: none !important; }
            .print-only-container { display: block !important; position: absolute; left: 0; top: 0; width: 100% !important; }
            .cv-paper { 
                box-shadow: none !important; 
                width: 210mm !important; 
                height: 297mm !important; 
                max-height: 297mm !important; 
                margin: 0 !important; 
                padding: 0 !important; 
                overflow: hidden !important; 
                page-break-after: avoid !important;
                page-break-inside: avoid !important;
            }
        }
    </style>
    <script src="assets/js/site-language.js?v=4" defer></script>

    <!-- Shared HireMe favicon -->
    <link rel="icon" type="image/png" sizes="48x48" href="assets/favicons/favicon-48.png?v=20261011">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicons/favicon-32.png?v=20261011">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/favicons/favicon-16.png?v=20261011">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/favicons/apple-touch-icon-180.png?v=20261011">
</head>
<body class="bg-slate-950 text-slate-100 font-sans min-h-screen flex flex-col overflow-x-hidden">

    <!-- Header bar -->
    <header class="no-print bg-slate-950/80 border-b border-slate-800/80 sticky top-0 z-40 backdrop-blur-md px-6 py-4 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="index.html" class="text-2xl font-black tracking-tight text-white flex items-center gap-2">
                <span class="bg-gradient-to-r from-sky-400 to-indigo-500 bg-clip-text text-transparent">HireMe</span>
                <span class="text-xs bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 px-2.5 py-0.5 rounded-full font-bold">LETTRE PRO</span>
            </a>
        </div>
        <div class="flex items-center gap-4">
            <a href="cv-builder.php" class="text-xs text-sky-400 border border-sky-400/20 px-4 py-2 rounded-xl transition-all font-bold hover:bg-sky-500 hover:text-white">
                <i class="fa-solid fa-file-lines mr-1.5"></i> Générateur de CV
            </a>
        </div>
    </header>

    <!-- Main Workspace -->
    <div class="flex-1 flex flex-col lg:flex-row h-[calc(100vh-73px)] overflow-hidden relative">
        
        <!-- Left Side: Interactive Forms -->
        <div id="left-form-panel" class="w-full lg:w-[45%] bg-slate-950 border-r border-slate-900 flex flex-col h-full overflow-hidden transition-all duration-300">
            
            <!-- Step Navigation -->
            <div class="no-print flex border-b border-slate-900 bg-slate-900/30 p-2 overflow-x-auto gap-2">
                <button onclick="switchStep(1)" id="btn-step-1" class="step-btn flex-1 min-w-[90px] py-2 px-3 rounded-xl text-xs font-bold text-center transition-all bg-sky-600 text-white shadow-lg shadow-sky-500/20">
                    <i class="fa-solid fa-pen mr-1.5"></i> Contenu
                </button>
                <button onclick="switchStep(2)" id="btn-step-2" class="step-btn flex-1 min-w-[90px] py-2 px-3 rounded-xl text-xs font-bold text-center transition-all text-slate-400 hover:text-white hover:bg-slate-900">
                    <i class="fa-solid fa-sliders mr-1.5"></i> Design
                </button>
            </div>

            <!-- Form Content -->
            <div class="flex-1 overflow-y-auto p-6 space-y-6">
                
                <!-- STEP 1: Content -->
                <div id="step-1" class="step-pane space-y-5">
                    <div>
                        <h2 class="text-lg font-bold text-white mb-1"><i class="fa-solid fa-envelope-open-text text-sky-500 mr-2"></i>Contenu de la Lettre</h2>
                        <p class="text-xs text-slate-400">Vos coordonnées et celles de l'employeur.</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-wider">Votre Nom *</label>
                            <input type="text" id="in-name" onkeyup="updateLiveLetter()" placeholder="ex: Nndintek Handson Njai" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-sky-500">
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-wider">Votre Téléphone *</label>
                            <input type="tel" id="in-phone" onkeyup="updateLiveLetter()" placeholder="ex: +237 690 034 617" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-sky-500">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-wider">Votre Email</label>
                            <input type="email" id="in-email" onkeyup="updateLiveLetter()" placeholder="ex: handson@email.com" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-sky-500">
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-wider">Votre Ville</label>
                            <input type="text" id="in-location" onkeyup="updateLiveLetter()" placeholder="ex: Douala, Cameroun" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-sky-500">
                        </div>
                    </div>

                    <div class="border-t border-slate-800/80 pt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-wider">Entreprise Ciblée *</label>
                            <input type="text" id="in-company" onkeyup="updateLiveLetter()" placeholder="ex: MTN Cameroun" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-sky-500">
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-wider">Nom du Destinataire</label>
                            <input type="text" id="in-manager" onkeyup="updateLiveLetter()" placeholder="ex: M. Le Directeur RH" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-sky-500">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-wider">Ville de l'Entreprise</label>
                            <input type="text" id="in-company-location" onkeyup="updateLiveLetter()" placeholder="ex: Douala" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-sky-500">
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-wider">Date</label>
                            <input type="text" id="in-date" onkeyup="updateLiveLetter()" value="<?= date('d/m/Y') ?>" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-sky-500">
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-[11px] font-black text-slate-400 uppercase tracking-wider">Poste Visé (Objet) *</label>
                        <input type="text" id="in-title" onkeyup="updateLiveLetter()" placeholder="ex: Candidature au poste de Développeur Fullstack" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-sky-500">
                    </div>

                    <div class="space-y-1.5 relative border-t border-slate-800/80 pt-4">
                        <div class="flex justify-between items-center mb-2">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-wider">Corps de la Lettre</label>
                            <button type="button" onclick="generateAILetter()" id="btn-ai-letter" class="text-[10px] font-black uppercase tracking-widest text-white bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-500 hover:to-indigo-500 px-3 py-1.5 rounded-lg shadow-lg shadow-indigo-500/20 transition-all flex items-center gap-1.5">
                                <i class="fa-solid fa-wand-magic-sparkles text-amber-300"></i>
                                <span>Générer avec l'IA</span>
                            </button>
                        </div>
                        <textarea id="in-body" onkeyup="updateLiveLetter()" rows="8" placeholder="Rédigez ici le contenu de votre lettre (sans salutations ni signature)..." class="w-full bg-slate-900 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-sky-500 resize-y"></textarea>
                    </div>
                </div>

                <!-- STEP 2: Skills & Advanced Layout Settings -->
                <div id="step-2" class="step-pane hidden space-y-6">
                    <div>
                        <h2 class="text-lg font-bold text-white mb-1"><i class="fa-solid fa-sliders text-sky-500 mr-2"></i>Design & Style</h2>
                        <p class="text-xs text-slate-400">Assortissez le design de la lettre à votre CV.</p>
                    </div>

                    <div class="space-y-5">
                        <h3 class="text-xs font-black text-slate-300 uppercase tracking-wider">Personnalisation Graphique</h3>
                        
                        <!-- Template Select -->
                        <div class="space-y-1.5">
                            <label class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Sélectionner un Modèle</label>
                            <div class="grid grid-cols-3 gap-3">
                                <button onclick="setTemplate('paris')" id="tpl-btn-paris" class="border-2 border-sky-500 bg-sky-500/10 text-white p-3 rounded-xl text-center text-xs font-bold transition-all">
                                    Paris<br><span class="text-[9px] font-normal text-slate-400">Créatif</span>
                                </button>
                                <button onclick="setTemplate('london')" id="tpl-btn-london" class="border-2 border-slate-800 bg-slate-900/40 text-slate-400 hover:text-white p-3 rounded-xl text-center text-xs font-bold transition-all">
                                    London<br><span class="text-[9px] font-normal text-slate-400">Corporate</span>
                                </button>
                                <button onclick="setTemplate('berlin')" id="tpl-btn-berlin" class="border-2 border-slate-800 bg-slate-900/40 text-slate-400 hover:text-white p-3 rounded-xl text-center text-xs font-bold transition-all">
                                    Berlin<br><span class="text-[9px] font-normal text-slate-400">Minimaliste</span>
                                </button>
                            </div>
                        </div>

                        <!-- Accent Color picker -->
                        <div class="space-y-1.5">
                            <label class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Nuance de couleur</label>
                            <div class="flex items-center gap-3">
                                <button onclick="setAccentColor('blue')" class="w-8 h-8 rounded-full bg-sky-500 border-2 border-white/20 hover:scale-110 active:scale-95 transition-transform" title="Bleu"></button>
                                <button onclick="setAccentColor('green')" class="w-8 h-8 rounded-full bg-emerald-500 border-2 border-white/20 hover:scale-110 active:scale-95 transition-transform" title="Vert"></button>
                                <button onclick="setAccentColor('purple')" class="w-8 h-8 rounded-full bg-violet-500 border-2 border-white/20 hover:scale-110 active:scale-95 transition-transform" title="Violet"></button>
                                <button onclick="setAccentColor('charcoal')" class="w-8 h-8 rounded-full bg-slate-800 border-2 border-white/20 hover:scale-110 active:scale-95 transition-transform" title="Charbon"></button>
                                <button onclick="setAccentColor('burgundy')" class="w-8 h-8 rounded-full bg-rose-900 border-2 border-white/20 hover:scale-110 active:scale-95 transition-transform" title="Bordeaux"></button>
                            </div>
                        </div>

                        <!-- Font pairing selector -->
                        <div class="space-y-1.5">
                            <label class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Typographies de caractères</label>
                            <div class="grid grid-cols-3 gap-2">
                                <button onclick="setFontPairing('modern')" id="font-btn-modern" class="bg-sky-600 text-xs font-bold py-2 rounded-lg text-white shadow-md border border-transparent">Moderne (Sans)</button>
                                <button onclick="setFontPairing('classic')" id="font-btn-classic" class="bg-slate-900 text-xs font-bold py-2 rounded-lg text-slate-400 hover:text-white border border-slate-800">Classique (Serif)</button>
                                <button onclick="setFontPairing('elegant')" id="font-btn-elegant" class="bg-slate-900 text-xs font-bold py-2 rounded-lg text-slate-400 hover:text-white border border-slate-800">Élégante (Outfit)</button>
                            </div>
                        </div>

                    </div>

                    <div class="pt-4 flex gap-3">
                        <button onclick="switchStep(1)" class="flex-1 bg-slate-900 text-white font-bold text-xs py-3 rounded-xl border border-slate-800 hover:bg-slate-800 transition-all">
                            <i class="fa-solid fa-arrow-left mr-1"></i> Retour au contenu
                        </button>
                        <button onclick="window.print()" class="flex-1 bg-gradient-to-r from-emerald-500 to-teal-600 text-white font-black text-xs py-3.5 rounded-xl shadow-lg hover:shadow-emerald-500/20 transition-all">
                            <i class="fa-solid fa-download mr-1.5"></i> Télécharger PDF
                        </button>
                    </div>
                </div>

            </div>

        </div>

        <!-- Right Side: Real-Time Live Preview Workspace -->
        <div id="preview-panel" class="flex-grow bg-slate-900 flex items-start justify-center overflow-y-auto p-4 lg:p-10 h-full scroll-smooth hidden lg:flex">
            <div id="cv-scale-wrapper" class="origin-top transition-transform duration-300">
                <div id="live-cv-paper" class="cv-paper theme-blue cv-spacing-normal font-pair-modern text-left flex flex-col select-none pointer-events-none">
                    <div id="cv-content-renderer" class="h-full w-full flex flex-col">
                        <!-- Populated dynamically by JS -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile view toggle -->
    <div class="no-print lg:hidden fixed bottom-4 left-4 right-4 bg-slate-900/90 backdrop-blur-xl border border-slate-800/80 rounded-2xl p-2 flex gap-2 z-50 shadow-2xl">
        <button id="mobile-toggle-edit" onclick="toggleMobileView('edit')" class="flex-1 py-3 text-xs font-bold text-center rounded-xl bg-sky-600 text-white shadow-lg transition-all">
            <i class="fa-solid fa-pen-to-square mr-1.5"></i> Modifier
        </button>
        <button id="mobile-toggle-preview" onclick="toggleMobileView('preview')" class="flex-1 py-3 text-xs font-bold text-center rounded-xl text-slate-400 hover:text-white transition-all">
            <i class="fa-solid fa-eye mr-1.5"></i> Aperçu
        </button>
    </div>

    <script>
        let currentStep = 1;
        let activeTheme = 'blue';
        let activeTemplate = 'paris';
        let activeFont = 'modern';

        window.addEventListener('DOMContentLoaded', () => {
            updateLiveLetter();
            window.addEventListener('resize', scaleCVPreview);
            scaleCVPreview();
        });

        function switchStep(stepNum) {
            document.querySelectorAll('.step-pane').forEach(el => el.classList.add('hidden'));
            document.querySelectorAll('.step-btn').forEach(btn => {
                btn.classList.remove('bg-sky-600', 'text-white', 'shadow-lg', 'shadow-sky-500/20');
                btn.classList.add('text-slate-400', 'hover:text-white', 'hover:bg-slate-900');
            });

            document.getElementById(`step-${stepNum}`).classList.remove('hidden');
            const activeBtn = document.getElementById(`btn-step-${stepNum}`);
            activeBtn.classList.remove('text-slate-400', 'hover:text-white', 'hover:bg-slate-900');
            activeBtn.classList.add('bg-sky-600', 'text-white', 'shadow-lg', 'shadow-sky-500/20');

            currentStep = stepNum;
        }

        function setAccentColor(color) {
            activeTheme = color;
            applyPaperClasses();
        }

        function setTemplate(tplName) {
            activeTemplate = tplName;
            ['paris', 'london', 'berlin'].forEach(t => {
                const btn = document.getElementById(`tpl-btn-${t}`);
                if (t === tplName) {
                    btn.className = "border-2 border-sky-500 bg-sky-500/10 text-white p-3 rounded-xl text-center text-xs font-bold transition-all";
                } else {
                    btn.className = "border-2 border-slate-800 bg-slate-900/40 text-slate-400 hover:text-white p-3 rounded-xl text-center text-xs font-bold transition-all";
                }
            });
            updateLiveLetter();
        }

        function setFontPairing(fontName) {
            activeFont = fontName;
            ['modern', 'classic', 'elegant'].forEach(f => {
                const btn = document.getElementById(`font-btn-${f}`);
                if (f === fontName) {
                    btn.className = "bg-sky-600 text-xs font-bold py-2 rounded-lg text-white shadow-md border border-transparent";
                } else {
                    btn.className = "bg-slate-900 text-xs font-bold py-2 rounded-lg text-slate-400 hover:text-white border border-slate-800";
                }
            });
            applyPaperClasses();
        }

        function applyPaperClasses() {
            const paper = document.getElementById('live-cv-paper');
            paper.className = `cv-paper theme-${activeTheme} cv-spacing-normal font-pair-${activeFont} text-left flex flex-col select-none pointer-events-none`;
        }

        function updateLiveLetter() {
            const name = document.getElementById('in-name').value || 'VOTRE NOM';
            const phone = document.getElementById('in-phone').value || 'Votre Téléphone';
            const email = document.getElementById('in-email').value || 'votre.email@mail.com';
            const location = document.getElementById('in-location').value || 'Votre Ville';
            const company = document.getElementById('in-company').value || 'Nom de l\'entreprise';
            const manager = document.getElementById('in-manager').value || 'Responsable de recrutement';
            const compLocation = document.getElementById('in-company-location').value || 'Ville';
            const date = document.getElementById('in-date').value || 'Date';
            const title = document.getElementById('in-title').value || 'Objet de la candidature';
            const body = document.getElementById('in-body').value || 'Madame, Monsieur,\n\n(Corps de votre lettre de motivation...)';

            const renderer = document.getElementById('cv-content-renderer');

            let headerHtml = '';
            
            // Generate Matching Headers
            if (activeTemplate === 'paris') {
                headerHtml = `
                    <div class="flex-grow flex flex-col sm:flex-row gap-8 min-h-full">
                        <div class="w-full sm:w-64 bg-slate-50 p-8 sm:-ml-16 sm:-my-16 sm:pt-12 flex flex-col border-r border-slate-100">
                            <h1 class="cv-name text-2xl font-extrabold text-slate-900 tracking-tight uppercase leading-none mb-6">${name}</h1>
                            <div class="space-y-4 text-[11px] text-slate-600">
                                <div class="flex items-center gap-2"><i class="fa-solid fa-phone text-[var(--cv-primary)] shrink-0"></i>${phone}</div>
                                <div class="flex items-center gap-2"><i class="fa-solid fa-envelope text-[var(--cv-primary)] shrink-0"></i>${email}</div>
                                <div class="flex items-center gap-2"><i class="fa-solid fa-location-dot text-[var(--cv-primary)] shrink-0"></i>${location}</div>
                            </div>
                        </div>
                        <div class="flex-grow sm:pl-4 p-6 sm:p-0 flex flex-col">
                            ${getLetterBody(manager, company, compLocation, date, title, body, name)}
                        </div>
                    </div>
                `;
            } else if (activeTemplate === 'london') {
                headerHtml = `
                    <div class="p-6 sm:p-0 flex flex-col h-full">
                        <div class="text-center pb-6 border-b-2 border-slate-100 mb-8">
                            <h1 class="cv-name text-3xl font-extrabold text-slate-900 tracking-tight leading-none mb-3">${name}</h1>
                            <div class="flex flex-wrap justify-center gap-x-4 gap-y-1 text-[11px] text-slate-500">
                                <span><i class="fa-solid fa-phone text-slate-400 mr-1.5"></i>${phone}</span>
                                <span><i class="fa-solid fa-envelope text-slate-400 mr-1.5"></i>${email}</span>
                                <span><i class="fa-solid fa-location-dot text-slate-400 mr-1.5"></i>${location}</span>
                            </div>
                        </div>
                        ${getLetterBody(manager, company, compLocation, date, title, body, name)}
                    </div>
                `;
            } else {
                headerHtml = `
                    <div class="hidden sm:block absolute inset-4 border border-slate-200 pointer-events-none -m-4"></div>
                    <div class="flex-grow p-6 sm:p-4 relative flex flex-col h-full">
                        <div class="flex justify-between items-start gap-4 pb-6 mb-8 border-b border-slate-100">
                            <div>
                                <h1 class="cv-name text-2xl font-bold text-slate-900 tracking-tight mb-2 uppercase">${name}</h1>
                                <div class="flex flex-col gap-1 text-[10px] text-slate-500 font-bold tracking-wider">
                                    <span>${phone}</span>
                                    <span>${email}</span>
                                </div>
                            </div>
                        </div>
                        ${getLetterBody(manager, company, compLocation, date, title, body, name)}
                    </div>
                `;
            }

            renderer.innerHTML = headerHtml;
        }

        function getLetterBody(manager, company, compLocation, date, title, body, name) {
            const formattedBody = body.replace(/\n/g, '<br>');
            return `
                <div class="flex-grow flex flex-col text-sm text-slate-700 font-medium">
                    <div class="flex justify-between items-start mb-8">
                        <div></div>
                        <div class="text-right">
                            <div class="font-bold text-slate-900">${company}</div>
                            <div>À l'attention de : ${manager}</div>
                            <div>${compLocation}</div>
                        </div>
                    </div>
                    
                    <div class="mb-8 text-right italic text-xs text-slate-500">Fait le ${date}</div>
                    
                    <div class="mb-8 border-l-2 border-[var(--cv-primary)] pl-3 py-1 bg-slate-50">
                        <span class="font-bold text-slate-900">Objet :</span> ${title}
                    </div>

                    <div class="mb-4">Madame, Monsieur,</div>
                    
                    <div class="leading-loose text-justify mb-8 flex-grow">
                        ${formattedBody}
                    </div>

                    <div class="mt-auto pt-8">
                        <div class="mb-2">Cordialement,</div>
                        <div class="font-bold text-slate-900 text-base font-pair-classic">${name}</div>
                    </div>
                </div>
            `;
        }

        function scaleCVPreview() {
            const wrapper = document.getElementById('cv-scale-wrapper');
            const panel = document.getElementById('preview-panel');
            if (!wrapper || !panel) return;

            const panelWidth = panel.clientWidth - 40;
            const paperWidth = 794; 
            
            if (panelWidth < paperWidth) {
                const scale = panelWidth / paperWidth;
                wrapper.style.transform = `scale(${scale})`;
                wrapper.style.transformOrigin = 'top center';
            } else {
                wrapper.style.transform = 'scale(1)';
            }
        }

        function toggleMobileView(view) {
            const leftPanel = document.getElementById('left-form-panel');
            const rightPanel = document.getElementById('preview-panel');
            const btnEdit = document.getElementById('mobile-toggle-edit');
            const btnPreview = document.getElementById('mobile-toggle-preview');

            if (view === 'edit') {
                leftPanel.classList.remove('hidden');
                rightPanel.classList.add('hidden');
                rightPanel.classList.remove('flex');
                btnEdit.classList.add('bg-sky-600', 'text-white', 'shadow-lg');
                btnEdit.classList.remove('text-slate-400', 'hover:text-white');
                btnPreview.classList.remove('bg-sky-600', 'text-white', 'shadow-lg');
                btnPreview.classList.add('text-slate-400', 'hover:text-white');
            } else {
                leftPanel.classList.add('hidden');
                rightPanel.classList.remove('hidden');
                rightPanel.classList.add('flex');
                btnPreview.classList.add('bg-sky-600', 'text-white', 'shadow-lg');
                btnPreview.classList.remove('text-slate-400', 'hover:text-white');
                btnEdit.classList.remove('bg-sky-600', 'text-white', 'shadow-lg');
                btnEdit.classList.add('text-slate-400', 'hover:text-white');
                scaleCVPreview();
            }
        }

        // AI Generation Logic
        function generateAILetter() {
            const titleInput = document.getElementById('in-title').value;
            const companyInput = document.getElementById('in-company').value;
            const nameInput = document.getElementById('in-name').value;
            const btn = document.getElementById('btn-ai-letter');
            const bodyField = document.getElementById('in-body');

            if (!titleInput || titleInput.trim() === '') {
                alert("Veuillez indiquer le 'Poste Visé (Objet)' pour que l'IA puisse générer la lettre.");
                document.getElementById('in-title').focus();
                return;
            }

            const originalBtnHtml = btn.innerHTML;
            btn.innerHTML = `<i class="fa-solid fa-circle-notch fa-spin text-amber-300"></i><span>Génération...</span>`;
            btn.disabled = true;

            fetch('api/generate-ai-letter.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ job_title: titleInput, company: companyInput, name: nameInput })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.text) {
                    let text = data.text;
                    bodyField.value = "";
                    let i = 0;
                    let speed = 10; 
                    
                    function typeWriter() {
                        if (i < text.length) {
                            bodyField.value += text.charAt(i);
                            i++;
                            if (i % 3 === 0) updateLiveLetter(); 
                            setTimeout(typeWriter, speed);
                        } else {
                            btn.innerHTML = originalBtnHtml;
                            btn.disabled = false;
                            updateLiveLetter();
                        }
                    }
                    typeWriter();
                } else {
                    alert("Erreur de l'IA: " + (data.error || "Réponse invalide."));
                    btn.innerHTML = originalBtnHtml;
                    btn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error generating AI letter:', error);
                alert("Erreur de connexion au serveur IA.");
                btn.innerHTML = originalBtnHtml;
                btn.disabled = false;
            });
        }
    </script>
</body>
</html>
