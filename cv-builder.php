<?php
/**
 * $1000 Premium CV Builder (V2 Refactored App)
 * Featuring: Drag&Drop (SortableJS), Image Cropping (Cropper.js), Rich Text (Quill), Custom Sections, and Top-Notch Templates.
 */
session_start();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Générateur de CV Premium V2 - HireMe</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,600;0,700;1,400&family=Playfair+Display:ital,wght@0,600;0,700;0,800;1,600&family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Fira+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Cropper.js for Image Cropping -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>

    <!-- Sortable.js for Drag & Drop -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>

    <!-- Quill.js for Rich Text Editing -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
    
    <!-- Tailwind CSS (Static - no JIT CDN to prevent browser freeze) -->
    <link href="output.css" rel="stylesheet">

    <style>
        :root {
            --cv-primary: #0284c7;
            --cv-dark: #0f172a;
            --cv-light: #f8fafc;
        }

        /* Scrollbar styles */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #0f172a; }
        ::-webkit-scrollbar-thumb { background: #334155; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #475569; }

        /* Sortable Ghost (when dragging) */
        .sortable-ghost { opacity: 0.4; background-color: rgba(56, 189, 248, 0.1); border: 2px dashed #38bdf8; }
        .drag-handle { cursor: grab; }
        .drag-handle:active { cursor: grabbing; }

        /* Custom Quill Editor dark mode overrides */
        .ql-toolbar.ql-snow {
            border: 1px solid #334155;
            background: #1e293b;
            border-top-left-radius: 0.5rem;
            border-top-right-radius: 0.5rem;
            padding: 4px;
        }
        .ql-container.ql-snow {
            border: 1px solid #334155;
            background: #0f172a;
            border-bottom-left-radius: 0.5rem;
            border-bottom-right-radius: 0.5rem;
            color: white;
            min-height: 80px;
            font-size: 0.75rem;
        }
        .ql-snow .ql-stroke { stroke: #cbd5e1; }
        .ql-snow .ql-fill, .ql-snow .ql-stroke.ql-fill { fill: #cbd5e1; }

        /* CV Paper Styling */
        .cv-paper {
            width: 210mm;
            min-height: 297mm;
            background: white;
            color: #1e293b;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            transition: transform 0.3s ease;
            box-sizing: border-box;
            word-wrap: break-word;
            position: relative;
        }

        /* Spacing Presets */
        .cv-spacing-compact { padding: 10mm !important; }
        .cv-spacing-normal { padding: 15mm !important; }
        .cv-spacing-airy { padding: 20mm !important; }

        /* Typography Pairings */
        .font-pair-modern { font-family: 'Inter', sans-serif; }
        .font-pair-classic { font-family: 'Lora', serif; }
        .font-pair-classic h1, .font-pair-classic h2, .font-pair-classic .cv-name { font-family: 'Playfair Display', serif; }
        .font-pair-startup { font-family: 'Fira Sans', sans-serif; }
        .font-pair-startup h1, .font-pair-startup h2, .font-pair-startup .cv-name { font-family: 'Outfit', sans-serif; }

        /* Color Presets */
        .theme-blue { --cv-primary: #0ea5e9; --cv-dark: #0f172a; }
        .theme-emerald { --cv-primary: #10b981; --cv-dark: #064e3b; }
        .theme-violet { --cv-primary: #8b5cf6; --cv-dark: #2e1065; }
        .theme-crimson { --cv-primary: #e11d48; --cv-dark: #4c0519; }
        .theme-charcoal { --cv-primary: #334155; --cv-dark: #020617; }

        /* Theme Utility Classes to prevent Tailwind JIT crash */
        .text-cv-primary { color: var(--cv-primary) !important; }
        .bg-cv-primary { background-color: var(--cv-primary) !important; }
        .border-cv-primary { border-color: var(--cv-primary) !important; }
        .bg-cv-dark { background-color: var(--cv-dark) !important; }
        .bg-cv-gradient { background-image: linear-gradient(to bottom, var(--cv-primary), var(--cv-dark)) !important; }
        
        /* Print formatting */
        @media print {
            @page { margin: 0; }
            body { background: white !important; margin: 0 !important; padding: 0 !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            .no-print { display: none !important; }
            .print-only-container { display: block !important; position: absolute; left: 0; top: 0; width: 100% !important; }
            .cv-paper { box-shadow: none !important; width: 210mm !important; min-height: 297mm !important; max-height: 297mm !important; margin: 0 !important; padding: 0 !important; overflow: hidden !important; page-break-after: avoid !important; page-break-inside: avoid !important; border:none;}
        }

        /* Cropper Modal Background */
        #cropper-modal { backdrop-filter: blur(8px); }
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
    <header class="no-print bg-slate-900 border-b border-slate-800 sticky top-0 z-40 px-4 md:px-6 py-3 flex items-center justify-between shadow-md">
        <div class="flex items-center gap-3">
            <a href="index.html" class="text-xl md:text-2xl font-black tracking-tight text-white flex items-center gap-2">
                <span class="bg-gradient-to-r from-sky-400 to-indigo-500 bg-clip-text text-transparent">HireMe</span>
                <span class="text-[10px] md:text-xs bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 px-2.5 py-0.5 rounded-full font-bold hidden sm:inline">PRO BUILDER V2</span>
            </a>
        </div>
        <div class="flex items-center gap-2 md:gap-4">
            <a href="cover-letter-builder.php" class="text-[10px] md:text-xs text-indigo-400 hover:text-white bg-indigo-500/10 border border-indigo-500/20 hover:bg-indigo-500/20 px-3 md:px-4 py-2 rounded-xl transition-all font-bold flex items-center gap-1.5">
                <i class="fa-solid fa-envelope-open-text"></i> <span class="hidden sm:inline">Lettre de Motivation</span>
            </a>
            <button onclick="triggerDownload()" class="text-[10px] md:text-xs bg-gradient-to-r from-emerald-500 to-teal-600 text-white font-black py-2 md:py-2.5 px-4 md:px-5 rounded-xl shadow-lg hover:scale-105 transition-all flex items-center gap-1.5">
                <i class="fa-solid fa-download"></i> <span class="hidden sm:inline">Télécharger</span>
            </button>
        </div>
    </header>

    <!-- Main Workspace -->
    <div class="flex-1 flex flex-col lg:flex-row h-[calc(100vh-61px)] overflow-hidden relative">
        
        <!-- Left Side: Interactive Forms -->
        <div id="left-form-panel" class="w-full lg:w-[45%] bg-slate-950 border-r border-slate-800 flex flex-col h-full overflow-hidden transition-all duration-300">
            
            <!-- Step Navigation -->
            <div class="no-print flex border-b border-slate-800 bg-slate-900/50 p-2 overflow-x-auto gap-2 scrollbar-hide">
                <button onclick="switchStep(1)" id="btn-step-1" class="step-btn flex-1 min-w-[90px] py-2 px-3 rounded-xl text-xs font-bold text-center transition-all bg-sky-600 text-white shadow-lg shadow-sky-500/20">
                    <i class="fa-solid fa-user mr-1"></i> Profil
                </button>
                <button onclick="switchStep(2)" id="btn-step-2" class="step-btn flex-1 min-w-[90px] py-2 px-3 rounded-xl text-xs font-bold text-center transition-all text-slate-400 hover:text-white hover:bg-slate-800">
                    <i class="fa-solid fa-briefcase mr-1"></i> Parcours
                </button>
                <button onclick="switchStep(3)" id="btn-step-3" class="step-btn flex-1 min-w-[90px] py-2 px-3 rounded-xl text-xs font-bold text-center transition-all text-slate-400 hover:text-white hover:bg-slate-800">
                    <i class="fa-solid fa-shapes mr-1"></i> Blocs Libres
                </button>
                <button onclick="switchStep(4)" id="btn-step-4" class="step-btn flex-1 min-w-[90px] py-2 px-3 rounded-xl text-xs font-bold text-center transition-all text-slate-400 hover:text-white hover:bg-slate-800">
                    <i class="fa-solid fa-wand-magic-sparkles mr-1"></i> Design
                </button>
            </div>

            <!-- Form Content -->
            <div class="flex-1 overflow-y-auto p-4 md:p-6 space-y-6 pb-24 lg:pb-6">
                
                <!-- STEP 1: Personal Details & Photo -->
                <div id="step-1" class="step-pane space-y-5">
                    <div>
                        <h2 class="text-lg font-bold text-white mb-1">Détails Personnels</h2>
                        <p class="text-[11px] text-slate-400">Ajoutez votre plus belle photo et présentez-vous.</p>
                    </div>

                    <!-- Advanced Photo Upload Card -->
                    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 flex items-center gap-4 shadow-sm relative overflow-hidden group">
                        <div class="absolute inset-0 bg-gradient-to-r from-sky-500/5 to-indigo-500/5 pointer-events-none"></div>
                        <div class="relative w-20 h-20 rounded-full bg-slate-800 border-2 border-slate-700 overflow-hidden flex items-center justify-center shrink-0 shadow-inner">
                            <img id="avatar-preview-form" class="w-full h-full object-cover hidden">
                            <i id="avatar-placeholder-form" class="fa-solid fa-user-astronaut text-slate-500 text-2xl"></i>
                        </div>
                        <div class="flex-1 space-y-2 z-10">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Photo de profil</label>
                            <div class="flex flex-wrap gap-2">
                                <label class="bg-sky-600 hover:bg-sky-500 text-white px-3 py-1.5 rounded-lg text-xs font-bold cursor-pointer transition-all shadow-md">
                                    <i class="fa-solid fa-upload mr-1"></i> Ajouter photo
                                    <input type="file" id="photo-file" accept="image/*" class="hidden" onchange="openCropper(event)">
                                </label>
                                <button type="button" onclick="removePhoto()" id="btn-remove-photo" class="hidden bg-slate-800 text-red-400 hover:bg-slate-700 px-3 py-1.5 rounded-lg text-xs font-bold transition-all">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Core Inputs -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Nom Complet *</label>
                            <input type="text" id="in-name" onkeyup="updateLiveCV()" placeholder="ex: Nndintek Handson" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-500 transition-all">
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Titre du Poste *</label>
                            <input type="text" id="in-title" onkeyup="updateLiveCV()" placeholder="ex: Senior Fullstack Developer" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-500 transition-all">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Téléphone / WhatsApp *</label>
                            <input type="tel" id="in-whatsapp" placeholder="ex: +237 690 034 617" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-sky-500 transition-all">
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Email Professionnel *</label>
                            <input type="email" id="in-email" placeholder="ex: contact@handson.dev" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-sky-500 transition-all">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Localisation / Ville</label>
                            <input type="text" id="in-location" onkeyup="updateLiveCV()" placeholder="ex: Douala, Littoral" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-sky-500 transition-all">
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Lien (LinkedIn, Portfolio)</label>
                            <input type="text" id="in-link" onkeyup="updateLiveCV()" placeholder="ex: linkedin.com/in/handson" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-sky-500 transition-all">
                        </div>
                    </div>

                    <!-- AI Magic Writer Profil -->
                    <div class="space-y-1.5 bg-indigo-900/10 border border-indigo-500/20 p-4 rounded-xl relative">
                        <div class="flex justify-between items-center mb-2">
                            <label class="text-[10px] font-black text-indigo-300 uppercase tracking-wider">Accroche Professionnelle</label>
                            <button type="button" onclick="generateAIProfile()" id="btn-ai-profile" class="text-[10px] font-black uppercase tracking-widest text-white bg-gradient-to-r from-indigo-500 to-purple-600 hover:scale-105 px-3 py-1.5 rounded-lg shadow-lg shadow-indigo-500/20 transition-transform flex items-center gap-1.5">
                                <i class="fa-solid fa-wand-magic-sparkles text-amber-300"></i>
                                <span>Magic Writer</span>
                            </button>
                        </div>
                        <textarea id="in-summary" onkeyup="updateLiveCV()" rows="4" placeholder="Résumé percutant de votre profil..." class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-indigo-500 resize-none"></textarea>
                    </div>
                </div>

                <!-- STEP 2: Work & Education (Drag & Drop) -->
                <div id="step-2" class="step-pane hidden space-y-6">
                    <!-- Experiences -->
                    <div>
                        <div class="flex justify-between items-center mb-3">
                            <h2 class="text-sm font-bold text-white uppercase tracking-wider"><i class="fa-solid fa-briefcase text-sky-500 mr-2"></i>Expériences</h2>
                            <button onclick="addExperienceBlock()" class="text-[10px] font-bold text-sky-400 bg-sky-500/10 px-3 py-1.5 rounded-lg hover:bg-sky-500/20 transition-all">
                                <i class="fa-solid fa-plus mr-1"></i> Ajouter
                            </button>
                        </div>
                        <div id="experience-list" class="space-y-4">
                            <!-- Drag & Drop items will be injected here -->
                        </div>
                    </div>

                    <!-- Education -->
                    <div class="pt-4 border-t border-slate-800">
                        <div class="flex justify-between items-center mb-3">
                            <h2 class="text-sm font-bold text-white uppercase tracking-wider"><i class="fa-solid fa-graduation-cap text-emerald-500 mr-2"></i>Formations</h2>
                            <button onclick="addEducationBlock()" class="text-[10px] font-bold text-emerald-400 bg-emerald-500/10 px-3 py-1.5 rounded-lg hover:bg-emerald-500/20 transition-all">
                                <i class="fa-solid fa-plus mr-1"></i> Ajouter
                            </button>
                        </div>
                        <div id="education-list" class="space-y-4">
                            <!-- Drag & Drop items will be injected here -->
                        </div>
                    </div>
                </div>

                <!-- STEP 3: Skills, Languages & Custom Sections -->
                <div id="step-3" class="step-pane hidden space-y-6">
                    <div>
                        <h2 class="text-lg font-bold text-white mb-1">Blocs Additionnels</h2>
                        <p class="text-[11px] text-slate-400">Compétences, langues et sections libres.</p>
                    </div>

                    <!-- Skills (Visual Tag Input Simulation) -->
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Compétences (séparées par une virgule)</label>
                        <input type="text" id="in-skills" onkeyup="updateLiveCV()" placeholder="ex: Figma, ReactJS, Management, SEO" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-sky-500 transition-all">
                    </div>

                    <!-- Languages -->
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Langues (séparées par une virgule)</label>
                        <input type="text" id="in-languages" onkeyup="updateLiveCV()" placeholder="ex: Français (Maternel), Anglais (C1)" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-sky-500 transition-all">
                    </div>

                    <!-- Custom Sections Builder -->
                    <div class="pt-4 border-t border-slate-800">
                        <div class="flex justify-between items-center mb-3">
                            <h2 class="text-sm font-bold text-white uppercase tracking-wider"><i class="fa-solid fa-puzzle-piece text-violet-500 mr-2"></i>Sections Libres</h2>
                            <button onclick="addCustomSectionBlock()" class="text-[10px] font-bold text-violet-400 bg-violet-500/10 px-3 py-1.5 rounded-lg hover:bg-violet-500/20 transition-all">
                                <i class="fa-solid fa-plus mr-1"></i> Créer Section
                            </button>
                        </div>
                        <p class="text-[10px] text-slate-500 mb-3">Créez des blocs sur mesure (ex: Certifications, Bénévolat, Projets...).</p>
                        <div id="custom-sections-list" class="space-y-4">
                            <!-- Custom blocks injected here -->
                        </div>
                    </div>
                </div>

                <!-- STEP 4: Advanced Styling (1000$ Suite) -->
                <div id="step-4" class="step-pane hidden space-y-6">
                    <div>
                        <h2 class="text-lg font-bold text-white mb-1"><i class="fa-solid fa-paint-roller text-sky-500 mr-2"></i>Design Studio</h2>
                        <p class="text-[11px] text-slate-400">Contrôle visuel total du document.</p>
                    </div>

                    <!-- Top Notch Templates -->
                    <div class="space-y-2">
                        <label class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Architecture (Templates)</label>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <button onclick="setTemplate('executive')" id="tpl-btn-executive" class="border-2 border-sky-500 bg-slate-900 text-white p-3 rounded-xl text-center transition-all relative overflow-hidden group">
                                <div class="absolute inset-0 bg-gradient-to-r from-sky-500/10 to-indigo-500/10"></div>
                                <i class="fa-solid fa-building-columns text-sky-400 text-xl mb-1"></i>
                                <div class="text-xs font-black">Executive</div>
                                <div class="text-[9px] text-slate-400">Pro & Épuré</div>
                            </button>
                            <button onclick="setTemplate('startup')" id="tpl-btn-startup" class="border border-slate-800 bg-slate-900/50 text-slate-400 hover:text-white p-3 rounded-xl text-center transition-all">
                                <i class="fa-solid fa-rocket text-slate-500 text-xl mb-1"></i>
                                <div class="text-xs font-black">Startup</div>
                                <div class="text-[9px] text-slate-500">Coloré & Vecteur</div>
                            </button>
                            <button onclick="setTemplate('minimalist')" id="tpl-btn-minimalist" class="border border-slate-800 bg-slate-900/50 text-slate-400 hover:text-white p-3 rounded-xl text-center transition-all">
                                <i class="fa-solid fa-cube text-slate-500 text-xl mb-1"></i>
                                <div class="text-xs font-black">Minimaliste</div>
                                <div class="text-[9px] text-slate-500">Grille Suisse</div>
                            </button>
                        </div>
                    </div>

                    <!-- Color Accent -->
                    <div class="space-y-2 pt-2">
                        <label class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Couleur Dominante</label>
                        <div class="flex flex-wrap gap-3">
                            <button onclick="setAccentColor('blue')" class="w-10 h-10 rounded-full bg-sky-500 border-2 border-slate-900 ring-2 ring-transparent hover:ring-sky-500 active-color-ring" id="col-btn-blue"></button>
                            <button onclick="setAccentColor('emerald')" class="w-10 h-10 rounded-full bg-emerald-500 border-2 border-slate-900 ring-2 ring-transparent hover:ring-emerald-500" id="col-btn-emerald"></button>
                            <button onclick="setAccentColor('violet')" class="w-10 h-10 rounded-full bg-violet-500 border-2 border-slate-900 ring-2 ring-transparent hover:ring-violet-500" id="col-btn-violet"></button>
                            <button onclick="setAccentColor('crimson')" class="w-10 h-10 rounded-full bg-rose-600 border-2 border-slate-900 ring-2 ring-transparent hover:ring-rose-500" id="col-btn-crimson"></button>
                            <button onclick="setAccentColor('charcoal')" class="w-10 h-10 rounded-full bg-slate-800 border-2 border-slate-900 ring-2 ring-transparent hover:ring-slate-500" id="col-btn-charcoal"></button>
                        </div>
                    </div>

                    <!-- Spacing / Padding select -->
                    <div class="space-y-2 pt-2">
                        <label class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Densité d'impression</label>
                        <div class="grid grid-cols-3 gap-2 bg-slate-900 p-1 rounded-xl border border-slate-800">
                            <button onclick="setSpacing('compact')" id="spc-btn-compact" class="text-[11px] font-bold py-2 rounded-lg text-slate-400 hover:text-white transition-all">Compacte</button>
                            <button onclick="setSpacing('normal')" id="spc-btn-normal" class="bg-slate-700 text-[11px] font-bold py-2 rounded-lg text-white shadow transition-all">Moyenne</button>
                            <button onclick="setSpacing('airy')" id="spc-btn-airy" class="text-[11px] font-bold py-2 rounded-lg text-slate-400 hover:text-white transition-all">Aérée</button>
                        </div>
                    </div>

                    <!-- Font pairing selector -->
                    <div class="space-y-2 pt-2">
                        <label class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Combinaison Typographique</label>
                        <div class="grid grid-cols-3 gap-2">
                            <button onclick="setFontPairing('modern')" id="font-btn-modern" class="bg-sky-600/20 border border-sky-500/50 text-[10px] font-bold py-2.5 rounded-lg text-sky-400 transition-all font-sans">Inter</button>
                            <button onclick="setFontPairing('classic')" id="font-btn-classic" class="bg-slate-900 border border-slate-800 text-[10px] font-bold py-2.5 rounded-lg text-slate-400 hover:text-white transition-all font-serif">Playfair</button>
                            <button onclick="setFontPairing('startup')" id="font-btn-startup" class="bg-slate-900 border border-slate-800 text-[10px] font-bold py-2.5 rounded-lg text-slate-400 hover:text-white transition-all" style="font-family: 'Outfit', sans-serif">Outfit</button>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Right Side: Real-Time Live Preview Workspace -->
        <div id="preview-panel" class="flex-grow bg-slate-800/50 flex items-start justify-center overflow-y-scroll p-4 lg:p-8 h-full scroll-smooth hidden lg:flex shadow-inner">
            <div id="cv-scale-wrapper" class="origin-top transition-transform duration-200">
                <!-- PRINT CONTAINER -->
                <div id="print-zone" class="print-only-container">
                    <div id="live-cv-paper" class="cv-paper theme-blue cv-spacing-normal font-pair-modern text-left flex flex-col select-none pointer-events-none bg-white">
                        <div id="cv-content-renderer" class="w-full flex-1 flex flex-col relative z-10">
                            <!-- Populated dynamically by JS -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile view toggle (Bottom Sheet style) -->
    <div class="no-print lg:hidden fixed bottom-4 left-4 right-4 bg-slate-900/95 backdrop-blur-md border border-slate-700/50 rounded-full p-1.5 flex z-50 shadow-[0_10px_40px_-10px_rgba(0,0,0,0.5)]">
        <button id="mobile-toggle-edit" onclick="toggleMobileView('edit')" class="flex-1 py-3 text-xs font-bold text-center rounded-full bg-sky-500 text-white shadow-lg transition-all">
            <i class="fa-solid fa-pen-nib mr-1.5"></i> Créer
        </button>
        <button id="mobile-toggle-preview" onclick="toggleMobileView('preview')" class="flex-1 py-3 text-xs font-bold text-center rounded-full text-slate-400 hover:text-white transition-all">
            <i class="fa-solid fa-eye mr-1.5"></i> Aperçu
        </button>
    </div>

    <!-- MODAL: IMAGE CROPPER -->
    <div id="cropper-modal" class="no-print hidden fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-950/80">
        <div class="bg-slate-900 border border-slate-700 rounded-3xl w-full max-w-md max-h-[90vh] overflow-hidden shadow-2xl flex flex-col">
            <div class="p-4 border-b border-slate-800 flex justify-between items-center bg-slate-950/50 shrink-0">
                <h3 class="font-bold text-white"><i class="fa-solid fa-crop-simple mr-2"></i>Recadrer l'image</h3>
                <button onclick="closeCropperModal()" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>
            <div class="w-full bg-black/50 relative h-[60vh] min-h-[300px] max-h-[500px] shrink-0">
                <div class="absolute inset-0 p-4 flex items-center justify-center">
                    <img id="cropper-image" src="" alt="Picture to crop" class="max-w-full max-h-full hidden">
                </div>
            </div>
            <div class="p-4 border-t border-slate-800 flex gap-3 shrink-0">
                <button onclick="closeCropperModal()" class="flex-1 py-2.5 rounded-xl text-xs font-bold text-slate-400 hover:text-white hover:bg-slate-800 transition-all">Annuler</button>
                <button onclick="applyCrop()" class="flex-1 py-2.5 rounded-xl text-xs font-bold text-white bg-sky-600 hover:bg-sky-500 shadow-lg transition-all">Appliquer</button>
            </div>
        </div>
    </div>

    <script>
        // Global State
        let cvState = {
            step: 1,
            theme: 'blue',
            template: 'executive',
            spacing: 'normal',
            font: 'modern',
            photoDataUrl: '',
            experiences: [],
            education: [],
            customSections: []
        };

        let expCounter = 0;
        let eduCounter = 0;
        let cstCounter = 0;
        
        let cropperInstance = null;
        let quillInstances = {}; // Store quill references

        // Initialization
        window.addEventListener('DOMContentLoaded', () => {
            initSortable();
            // Add initial empty blocks
            addExperienceBlock();
            addEducationBlock();
            
            // Re-render CV on any typing event in static inputs
            const inputs = ['in-name', 'in-title', 'in-whatsapp', 'in-email', 'in-location', 'in-link', 'in-skills', 'in-languages'];
            inputs.forEach(id => {
                const el = document.getElementById(id);
                if(el) el.addEventListener('input', debounce(updateLiveCV, 300));
            });

            window.addEventListener('resize', debounce(scaleCVPreview, 100));
            scaleCVPreview();

            // Set initial color ring
            document.getElementById('col-btn-blue').classList.replace('ring-transparent', 'ring-sky-500');
        });

        // -----------------------------------------------------
        // UTILS
        // -----------------------------------------------------
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => { clearTimeout(timeout); func(...args); };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        // -----------------------------------------------------
        // CROPPER.JS INTEGRATION
        // -----------------------------------------------------
        function openCropper(event) {
            const file = event.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = (e) => {
                const img = document.getElementById('cropper-image');
                img.src = e.target.result;
                img.classList.remove('hidden');
                document.getElementById('cropper-modal').classList.remove('hidden');
                
                if (cropperInstance) cropperInstance.destroy();
                cropperInstance = new Cropper(img, {
                    aspectRatio: 1,
                    viewMode: 1,
                    dragMode: 'move',
                    autoCropArea: 0.8,
                    restore: false,
                    guides: true,
                    center: true,
                    highlight: false,
                    cropBoxMovable: true,
                    cropBoxResizable: true,
                    toggleDragModeOnDblclick: false,
                });
            };
            reader.readAsDataURL(file);
        }

        function closeCropperModal() {
            document.getElementById('cropper-modal').classList.add('hidden');
            if (cropperInstance) cropperInstance.destroy();
            document.getElementById('photo-file').value = '';
        }

        function applyCrop() {
            if (!cropperInstance) return;
            const canvas = cropperInstance.getCroppedCanvas({ width: 300, height: 300, imageSmoothingEnabled: true, imageSmoothingQuality: 'high' });
            cvState.photoDataUrl = canvas.toDataURL('image/jpeg', 0.9);
            
            // Update UI
            const preview = document.getElementById('avatar-preview-form');
            preview.src = cvState.photoDataUrl;
            preview.classList.remove('hidden');
            document.getElementById('avatar-placeholder-form').classList.add('hidden');
            document.getElementById('btn-remove-photo').classList.remove('hidden');
            
            closeCropperModal();
            updateLiveCV();
        }

        function removePhoto() {
            cvState.photoDataUrl = '';
            document.getElementById('photo-file').value = '';
            const preview = document.getElementById('avatar-preview-form');
            preview.src = '';
            preview.classList.add('hidden');
            document.getElementById('avatar-placeholder-form').classList.remove('hidden');
            document.getElementById('btn-remove-photo').classList.add('hidden');
            updateLiveCV();
        }

        // -----------------------------------------------------
        // SORTABLE.JS & QUILL.JS DYNAMIC BLOCKS
        // -----------------------------------------------------
        function initSortable() {
            const expList = document.getElementById('experience-list');
            const eduList = document.getElementById('education-list');
            const cstList = document.getElementById('custom-sections-list');

            const sortOptions = {
                animation: 150,
                handle: '.drag-handle',
                ghostClass: 'sortable-ghost',
                onEnd: () => { updateLiveCV(); }
            };

            new Sortable(expList, sortOptions);
            new Sortable(eduList, sortOptions);
            new Sortable(cstList, sortOptions);
        }

        function addExperienceBlock() {
            expCounter++;
            const id = `exp-${expCounter}`;
            const html = `
                <div id="${id}" class="bg-slate-900 border border-slate-800 rounded-xl p-3 flex gap-3 group relative transition-colors">
                    <div class="drag-handle text-slate-600 hover:text-white flex items-center justify-center w-6 opacity-50 group-hover:opacity-100 transition-opacity">
                        <i class="fa-solid fa-grip-vertical"></i>
                    </div>
                    <div class="flex-1 space-y-3">
                        <div class="flex justify-between items-start">
                            <input type="text" placeholder="Poste (ex: Chef de Projet)" class="w-full bg-transparent border-b border-slate-700 text-sm text-white focus:outline-none focus:border-sky-500 font-bold mb-1 input-exp-title" onkeyup="debounce(updateLiveCV, 300)()">
                            <button onclick="removeBlock('${id}')" class="text-slate-500 hover:text-red-400 ml-2"><i class="fa-solid fa-xmark"></i></button>
                        </div>
                        <div class="flex gap-2">
                            <input type="text" placeholder="Entreprise" class="w-1/2 bg-slate-950 rounded border border-slate-800 px-2 py-1 text-xs text-white focus:border-sky-500 input-exp-company" onkeyup="debounce(updateLiveCV, 300)()">
                            <input type="text" placeholder="Dates (ex: 2021-2023)" class="w-1/2 bg-slate-950 rounded border border-slate-800 px-2 py-1 text-xs text-white focus:border-sky-500 input-exp-dates" onkeyup="debounce(updateLiveCV, 300)()">
                        </div>
                        <div>
                            <div id="quill-${id}" class="bg-slate-950 text-white quill-editor"></div>
                        </div>
                    </div>
                </div>
            `;
            document.getElementById('experience-list').insertAdjacentHTML('beforeend', html);
            
            // Init Quill for this block
            const quill = new Quill(`#quill-${id}`, {
                theme: 'snow',
                modules: { toolbar: [ ['bold', 'italic'], [{'list': 'bullet'}] ] },
                placeholder: 'Décrivez vos missions et accomplissements...'
            });
            quill.on('text-change', debounce(updateLiveCV, 400));
            quillInstances[id] = quill;
        }

        function addEducationBlock() {
            eduCounter++;
            const id = `edu-${eduCounter}`;
            const html = `
                <div id="${id}" class="bg-slate-900 border border-slate-800 rounded-xl p-3 flex gap-3 group relative transition-colors">
                    <div class="drag-handle text-slate-600 hover:text-white flex items-center justify-center w-6 opacity-50 group-hover:opacity-100">
                        <i class="fa-solid fa-grip-vertical"></i>
                    </div>
                    <div class="flex-1 space-y-2">
                        <div class="flex justify-between items-start">
                            <input type="text" placeholder="Diplôme (ex: Master Marketing)" class="w-full bg-transparent border-b border-slate-700 text-sm text-white focus:outline-none focus:border-emerald-500 font-bold mb-1 input-edu-degree" onkeyup="debounce(updateLiveCV, 300)()">
                            <button onclick="removeBlock('${id}')" class="text-slate-500 hover:text-red-400 ml-2"><i class="fa-solid fa-xmark"></i></button>
                        </div>
                        <div class="flex gap-2">
                            <input type="text" placeholder="École / Établissement" class="w-2/3 bg-slate-950 rounded border border-slate-800 px-2 py-1 text-xs text-white focus:border-emerald-500 input-edu-school" onkeyup="debounce(updateLiveCV, 300)()">
                            <input type="text" placeholder="Année" class="w-1/3 bg-slate-950 rounded border border-slate-800 px-2 py-1 text-xs text-white focus:border-emerald-500 input-edu-dates" onkeyup="debounce(updateLiveCV, 300)()">
                        </div>
                    </div>
                </div>
            `;
            document.getElementById('education-list').insertAdjacentHTML('beforeend', html);
        }

        function addCustomSectionBlock() {
            cstCounter++;
            const id = `cst-${cstCounter}`;
            const html = `
                <div id="${id}" class="bg-slate-900 border border-slate-800 rounded-xl p-3 flex gap-3 group relative transition-colors">
                    <div class="drag-handle text-slate-600 hover:text-white flex items-center justify-center w-6 opacity-50 group-hover:opacity-100">
                        <i class="fa-solid fa-grip-vertical"></i>
                    </div>
                    <div class="flex-1 space-y-2">
                        <div class="flex justify-between items-center mb-1">
                            <input type="text" placeholder="Titre Section (ex: Bénévolat)" class="w-full bg-transparent border-b border-slate-700 text-sm text-violet-400 focus:outline-none focus:border-violet-500 font-bold uppercase tracking-wider input-cst-title" onkeyup="debounce(updateLiveCV, 300)()">
                            <button onclick="removeBlock('${id}')" class="text-slate-500 hover:text-red-400 ml-2"><i class="fa-solid fa-xmark"></i></button>
                        </div>
                        <div id="quill-${id}" class="bg-slate-950 text-white quill-editor"></div>
                    </div>
                </div>
            `;
            document.getElementById('custom-sections-list').insertAdjacentHTML('beforeend', html);
            
            const quill = new Quill(`#quill-${id}`, {
                theme: 'snow',
                modules: { toolbar: [ ['bold', 'italic'], [{'list': 'bullet'}] ] },
                placeholder: 'Contenu de la section...'
            });
            quill.on('text-change', debounce(updateLiveCV, 400));
            quillInstances[id] = quill;
        }

        function removeBlock(id) {
            const el = document.getElementById(id);
            if(el) {
                el.remove();
                if(quillInstances[id]) delete quillInstances[id];
                updateLiveCV();
            }
        }

        // -----------------------------------------------------
        // STYLING CONTROLS
        // -----------------------------------------------------
        function switchStep(step) {
            document.querySelectorAll('.step-pane').forEach(el => el.classList.add('hidden'));
            document.querySelectorAll('.step-btn').forEach(btn => {
                btn.className = "step-btn flex-1 min-w-[90px] py-2 px-3 rounded-xl text-xs font-bold text-center transition-all text-slate-400 hover:text-white hover:bg-slate-800";
            });
            document.getElementById(`step-${step}`).classList.remove('hidden');
            const active = document.getElementById(`btn-step-${step}`);
            active.className = "step-btn flex-1 min-w-[90px] py-2 px-3 rounded-xl text-xs font-bold text-center transition-all bg-sky-600 text-white shadow-lg shadow-sky-500/20";
        }

        function setTemplate(t) {
            cvState.template = t;
            ['executive', 'startup', 'minimalist'].forEach(x => {
                const btn = document.getElementById(`tpl-btn-${x}`);
                btn.classList.remove('border-sky-500', 'bg-slate-900', 'text-white');
                btn.classList.add('border-slate-800', 'bg-slate-900/50', 'text-slate-400');
                if(btn.querySelector('.absolute')) btn.querySelector('.absolute').remove();
            });
            const active = document.getElementById(`tpl-btn-${t}`);
            active.classList.add('border-sky-500', 'bg-slate-900', 'text-white');
            active.classList.remove('border-slate-800', 'bg-slate-900/50', 'text-slate-400');
            active.insertAdjacentHTML('afterbegin', '<div class="absolute inset-0 bg-gradient-to-r from-sky-500/10 to-indigo-500/10"></div>');
            updateLiveCV();
        }

        function setAccentColor(c) {
            cvState.theme = c;
            // Manage UI rings
            document.querySelectorAll('.active-color-ring').forEach(el => {
                el.classList.remove('active-color-ring', `ring-${el.id.replace('col-btn-','')}-500`);
                el.classList.add('ring-transparent');
            });
            const btn = document.getElementById(`col-btn-${c}`);
            btn.classList.add('active-color-ring', `ring-${c==='charcoal'?'slate':(c==='crimson'?'rose':c)}-500`);
            btn.classList.remove('ring-transparent');
            
            document.getElementById('live-cv-paper').className = `cv-paper theme-${cvState.theme} cv-spacing-${cvState.spacing} font-pair-${cvState.font} text-left flex flex-col bg-white`;
        }

        function setSpacing(s) {
            cvState.spacing = s;
            ['compact', 'normal', 'airy'].forEach(x => {
                const btn = document.getElementById(`spc-btn-${x}`);
                btn.className = "text-[11px] font-bold py-2 rounded-lg text-slate-400 hover:text-white transition-all";
            });
            document.getElementById(`spc-btn-${s}`).className = "bg-slate-700 text-[11px] font-bold py-2 rounded-lg text-white shadow transition-all";
            document.getElementById('live-cv-paper').className = `cv-paper theme-${cvState.theme} cv-spacing-${cvState.spacing} font-pair-${cvState.font} text-left flex flex-col bg-white`;
        }

        function setFontPairing(f) {
            cvState.font = f;
            ['modern', 'classic', 'startup'].forEach(x => {
                const btn = document.getElementById(`font-btn-${x}`);
                btn.classList.remove('bg-sky-600/20', 'border-sky-500/50', 'text-sky-400');
                btn.classList.add('bg-slate-900', 'border-slate-800', 'text-slate-400');
            });
            const active = document.getElementById(`font-btn-${f}`);
            active.classList.add('bg-sky-600/20', 'border-sky-500/50', 'text-sky-400');
            active.classList.remove('bg-slate-900', 'border-slate-800', 'text-slate-400');
            document.getElementById('live-cv-paper').className = `cv-paper theme-${cvState.theme} cv-spacing-${cvState.spacing} font-pair-${cvState.font} text-left flex flex-col bg-white`;
        }

        // -----------------------------------------------------
        // MASTER RENDERER ($1000 TEMPLATES)
        // -----------------------------------------------------
        function updateLiveCV() {
            try {
                // Harvest Data
                const data = {
                    name: document.getElementById('in-name').value || 'Nom Complet',
                    title: document.getElementById('in-title').value || 'Titre du poste',
                    whatsapp: document.getElementById('in-whatsapp').value,
                    email: document.getElementById('in-email').value,
                    location: document.getElementById('in-location').value,
                    link: document.getElementById('in-link').value,
                    summary: document.getElementById('in-summary').value,
                    photo: cvState.photoDataUrl,
                    skills: (document.getElementById('in-skills').value || '').split(',').map(s=>s.trim()).filter(Boolean),
                    languages: (document.getElementById('in-languages').value || '').split(',').map(s=>s.trim()).filter(Boolean),
                    exp: [],
                    edu: [],
                    cst: []
                };

                document.querySelectorAll('#experience-list > div').forEach(el => {
                    const id = el.id;
                    data.exp.push({
                        title: el.querySelector('.input-exp-title').value,
                        company: el.querySelector('.input-exp-company').value,
                        dates: el.querySelector('.input-exp-dates').value,
                        desc: quillInstances[id] ? quillInstances[id].root.innerHTML : ''
                    });
                });

                document.querySelectorAll('#education-list > div').forEach(el => {
                    data.edu.push({
                        degree: el.querySelector('.input-edu-degree').value,
                        school: el.querySelector('.input-edu-school').value,
                        dates: el.querySelector('.input-edu-dates').value
                    });
                });

                document.querySelectorAll('#custom-sections-list > div').forEach(el => {
                    const id = el.id;
                    data.cst.push({
                        title: el.querySelector('.input-cst-title').value || 'Section',
                        html: quillInstances[id] ? quillInstances[id].root.innerHTML : ''
                    });
                });

                const renderer = document.getElementById('cv-content-renderer');
                if(cvState.template === 'executive') renderer.innerHTML = renderExecutive(data);
                else if(cvState.template === 'startup') renderer.innerHTML = renderStartup(data);
                else renderer.innerHTML = renderMinimalist(data);

                scaleCVPreview();
            } catch (err) {
                console.error("updateLiveCV Error: ", err);
                alert("JS ERROR: " + err.message);
            }
        }

        // SVG Icons Helper
        const icnPhone = `<svg class="w-3 h-3 fill-current" viewBox="0 0 512 512"><path d="M164.9 24.6c-7.7-18.6-28-28.5-47.4-23.2l-88 24C12.1 30.2 0 46 0 64C0 311.4 200.6 512 448 512c18 0 33.8-12.1 38.6-29.5l24-88c5.3-19.4-4.6-39.7-23.2-47.4l-96-40c-16.3-6.8-35.2-2.1-46.3 11.6L304.7 368C234.3 334.7 177.3 277.7 144 207.3L193.3 167c13.7-11.2 18.4-30 11.6-46.3l-40-96z"/></svg>`;
        const icnMail = `<svg class="w-3 h-3 fill-current" viewBox="0 0 512 512"><path d="M48 64C21.5 64 0 85.5 0 112c0 15.1 7.1 29.3 19.2 38.4L236.8 313.6c11.4 8.5 27 8.5 38.4 0L492.8 150.4c12.1-9.1 19.2-23.3 19.2-38.4c0-26.5-21.5-48-48-48H48zM0 176V384c0 35.3 28.7 64 64 64H448c35.3 0 64-28.7 64-64V176L294.4 339.2c-22.8 17.1-54 17.1-76.8 0L0 176z"/></svg>`;
        const icnLoc = `<svg class="w-3 h-3 fill-current" viewBox="0 0 384 512"><path d="M215.7 499.2C267 435 384 279.4 384 192C384 86 298 0 192 0S0 86 0 192c0 87.4 117 243 168.3 307.2c12.3 15.3 35.1 15.3 47.4 0zM192 128a64 64 0 1 1 0 128 64 64 0 1 1 0-128z"/></svg>`;
        const icnLink = `<svg class="w-3 h-3 fill-current" viewBox="0 0 640 512"><path d="M579.8 267.7c56.5-56.5 56.5-148 0-204.5c-50-50-128.8-56.5-186.3-15.4l-1.6 1.1c-14.4 10.3-17.7 30.3-7.4 44.6s30.3 17.7 44.6 7.4l1.6-1.1c32.1-22.9 76-19.3 103.8 8.6c31.5 31.5 31.5 82.5 0 114L422.3 334.8c-31.5 31.5-82.5 31.5-114 0c-27.9-27.9-31.5-71.8-8.6-103.8l1.1-1.6c10.3-14.4 6.9-34.4-7.4-44.6s-34.4-6.9-44.6 7.4l-1.1 1.6C206.5 251.2 213 330 263 380c56.5 56.5 148 56.5 204.5 0L579.8 267.7zM60.2 244.3c-56.5 56.5-56.5 148 0 204.5c50 50 128.8 56.5 186.3 15.4l1.6-1.1c14.4-10.3 17.7-30.3 7.4-44.6s-30.3-17.7-44.6-7.4l-1.6 1.1c-32.1 22.9-76 19.3-103.8-8.6C74 372 74 321 105.5 289.5L217.7 177.2c31.5-31.5 82.5-31.5 114 0c27.9 27.9 31.5 71.8 8.6 103.9l-1.1 1.6c-10.3 14.4-6.9 34.4 7.4 44.6s34.4 6.9 44.6-7.4l1.1-1.6C433.5 260.8 427 182 377 132c-56.5-56.5-148-56.5-204.5 0L60.2 244.3z"/></svg>`;

        // TEMPLATE 1: EXECUTIVE (Structured, Header Top, 2 columns body)
        function renderExecutive(d) {
            let photo = d.photo ? `<div class="w-24 h-24 rounded-full overflow-hidden border-2 border-white shadow-md shrink-0"><img src="${d.photo}" class="w-full h-full object-cover"></div>` : '';
            
            let contacts = [];
            if(d.whatsapp) contacts.push(`<div class="flex items-center gap-1.5"><span class="text-[var(--cv-primary)]">${icnPhone}</span> ${d.whatsapp}</div>`);
            if(d.email) contacts.push(`<div class="flex items-center gap-1.5"><span class="text-[var(--cv-primary)]">${icnMail}</span> ${d.email}</div>`);
            if(d.location) contacts.push(`<div class="flex items-center gap-1.5"><span class="text-[var(--cv-primary)]">${icnLoc}</span> ${d.location}</div>`);
            if(d.link) contacts.push(`<div class="flex items-center gap-1.5"><span class="text-[var(--cv-primary)]">${icnLink}</span> ${d.link}</div>`);

            let expHtml = d.exp.map(e => `
                <div class="mb-4">
                    <div class="flex justify-between items-baseline">
                        <div class="font-bold text-[13px] text-slate-800">${e.title}</div>
                        <div class="text-[10px] text-cv-primary font-bold bg-cv-primary/10 px-2 py-0.5 rounded">${e.dates}</div>
                    </div>
                    <div class="text-[11px] font-semibold text-slate-500 mb-1">${e.company}</div>
                    <div class="text-[11px] text-slate-600 leading-snug">${e.desc}</div>
                </div>
            `).join('');

            let eduHtml = d.edu.map(e => `
                <div class="mb-3">
                    <div class="font-bold text-[12px] text-slate-800 leading-tight">${e.degree}</div>
                    <div class="text-[11px] text-slate-500">${e.school}</div>
                    <div class="text-[10px] text-slate-400 font-semibold">${e.dates}</div>
                </div>
            `).join('');

            let skillsHtml = d.skills.map(s => `<span class="inline-block border border-slate-200 text-slate-600 text-[10px] font-semibold px-2 py-0.5 rounded-md mr-1.5 mb-1.5 bg-slate-50">${s}</span>`).join('');
            let langHtml = d.languages.map(l => `<div class="text-[11px] font-medium text-slate-600 mb-1 border-b border-slate-100 pb-1">${l}</div>`).join('');
            
            let cstHtml = d.cst.map(c => `
                <div class="mt-5">
                    <h3 class="text-[11px] font-black uppercase text-cv-primary tracking-widest border-b-2 border-slate-100 pb-1 mb-2.5">${c.title}</h3>
                    <div class="text-[11px] text-slate-600 leading-snug">${c.html}</div>
                </div>
            `).join('');

            return `
                <div class="bg-cv-dark text-white p-8 flex items-center gap-6">
                    ${photo}
                    <div class="flex-1">
                        <h1 class="cv-name text-3xl font-bold tracking-tight uppercase leading-none mb-1.5">${d.name}</h1>
                        <div class="text-sm font-medium text-cv-primary uppercase tracking-widest mb-3">${d.title}</div>
                        <div class="flex flex-wrap gap-x-4 gap-y-1.5 text-[10px] text-slate-300 font-medium">
                            ${contacts.join('')}
                        </div>
                    </div>
                </div>
                <div class="flex p-8 gap-8">
                    <div class="w-2/3 space-y-6">
                        ${d.summary ? `
                        <div>
                            <h3 class="text-[11px] font-black uppercase text-cv-primary tracking-widest border-b-2 border-cv-primary pb-1 mb-2.5 inline-block">Profil</h3>
                            <p class="text-[11px] text-slate-600 leading-relaxed text-justify">${d.summary.replace(/\n/g, '<br>')}</p>
                        </div>` : ''}
                        
                        ${expHtml ? `
                        <div>
                            <h3 class="text-[11px] font-black uppercase text-cv-primary tracking-widest border-b-2 border-cv-primary pb-1 mb-3 inline-block">Expérience Professionnelle</h3>
                            ${expHtml}
                        </div>` : ''}
                    </div>
                    
                    <div class="w-1/3 space-y-6">
                        ${eduHtml ? `
                        <div>
                            <h3 class="text-[11px] font-black uppercase text-cv-primary tracking-widest border-b-2 border-slate-100 pb-1 mb-3">Formation</h3>
                            ${eduHtml}
                        </div>` : ''}
                        
                        ${skillsHtml ? `
                        <div>
                            <h3 class="text-[11px] font-black uppercase text-cv-primary tracking-widest border-b-2 border-slate-100 pb-1 mb-2.5">Compétences</h3>
                            <div class="flex flex-wrap">${skillsHtml}</div>
                        </div>` : ''}

                        ${langHtml ? `
                        <div>
                            <h3 class="text-[11px] font-black uppercase text-cv-primary tracking-widest border-b-2 border-slate-100 pb-1 mb-2.5">Langues</h3>
                            ${langHtml}
                        </div>` : ''}

                        ${cstHtml}
                    </div>
                </div>
            `;
        }

        // TEMPLATE 2: STARTUP (Left Sidebar, Progress bars, Vector vibes)
        function renderStartup(d) {
            let photo = d.photo ? `<div class="w-28 h-28 mx-auto rounded-2xl overflow-hidden mb-6 shadow-xl"><img src="${d.photo}" class="w-full h-full object-cover"></div>` : '';
            
            let contacts = [];
            if(d.whatsapp) contacts.push(`<div class="flex items-center gap-2 mb-2"><div class="w-5 h-5 rounded bg-white/10 flex items-center justify-center">${icnPhone}</div> ${d.whatsapp}</div>`);
            if(d.email) contacts.push(`<div class="flex items-center gap-2 mb-2"><div class="w-5 h-5 rounded bg-white/10 flex items-center justify-center">${icnMail}</div> <span class="truncate">${d.email}</span></div>`);
            if(d.location) contacts.push(`<div class="flex items-center gap-2 mb-2"><div class="w-5 h-5 rounded bg-white/10 flex items-center justify-center">${icnLoc}</div> ${d.location}</div>`);
            if(d.link) contacts.push(`<div class="flex items-center gap-2 mb-2"><div class="w-5 h-5 rounded bg-white/10 flex items-center justify-center">${icnLink}</div> ${d.link}</div>`);

            let expHtml = d.exp.map(e => `
                <div class="mb-5 relative pl-4 border-l-2 border-cv-primary">
                    <div class="absolute w-2.5 h-2.5 bg-cv-primary rounded-full -left-[6px] top-1.5 ring-4 ring-white"></div>
                    <div class="font-extrabold text-[13px] text-slate-800">${e.title}</div>
                    <div class="flex gap-2 items-center mb-1.5 text-[10px] font-bold">
                        <span class="text-cv-primary">${e.company}</span>
                        <span class="text-slate-300">•</span>
                        <span class="text-slate-400">${e.dates}</span>
                    </div>
                    <div class="text-[11px] text-slate-600 leading-relaxed">${e.desc}</div>
                </div>
            `).join('');

            let eduHtml = d.edu.map(e => `
                <div class="mb-3 bg-slate-50 p-2.5 rounded-lg border border-slate-100">
                    <div class="font-bold text-[11px] text-slate-800">${e.degree}</div>
                    <div class="text-[10px] text-slate-500">${e.school}</div>
                    <div class="text-[9px] text-cv-primary font-bold mt-0.5">${e.dates}</div>
                </div>
            `).join('');

            // Skill Progress Bars
            let skillsHtml = d.skills.map((s, i) => {
                const width = 100 - (i * 5 % 30); // Randomish width for aesthetic
                return `<div class="mb-2">
                    <div class="flex justify-between text-[10px] font-bold mb-1"><span>${s}</span></div>
                    <div class="h-1.5 w-full bg-black/20 rounded-full overflow-hidden">
                        <div class="h-full bg-white rounded-full" style="width: ${width}%"></div>
                    </div>
                </div>`;
            }).join('');

            let langHtml = d.languages.map(l => `<span class="inline-block bg-white/10 px-2 py-1 rounded text-[10px] font-semibold mr-1 mb-1">${l}</span>`).join('');
            
            let cstHtml = d.cst.map(c => `
                <div class="mt-6">
                    <h3 class="text-[12px] font-black uppercase text-slate-800 tracking-wider mb-3 flex items-center gap-2"><span class="w-1 h-4 bg-cv-primary rounded-full"></span>${c.title}</h3>
                    <div class="text-[11px] text-slate-600 leading-snug">${c.html}</div>
                </div>
            `).join('');

            return `
                <div class="w-full flex-1 flex">
                    <div class="w-1/3 bg-cv-gradient text-white p-6 flex flex-col">
                        ${photo}
                        
                        <div class="mb-8 text-center border-b border-white/10 pb-6">
                            <h1 class="cv-name text-2xl font-black uppercase leading-tight mb-1">${d.name}</h1>
                            <div class="text-[10px] font-bold text-white/80 tracking-widest uppercase">${d.title}</div>
                        </div>

                        <div class="space-y-6">
                            <div>
                                <h3 class="text-[10px] font-black uppercase tracking-widest text-white/50 mb-3">Contact</h3>
                                <div class="text-[10px] text-white/90">${contacts.join('')}</div>
                            </div>

                            ${skillsHtml ? `
                            <div>
                                <h3 class="text-[10px] font-black uppercase tracking-widest text-white/50 mb-3">Expertise</h3>
                                ${skillsHtml}
                            </div>` : ''}

                            ${langHtml ? `
                            <div>
                                <h3 class="text-[10px] font-black uppercase tracking-widest text-white/50 mb-3">Langues</h3>
                                ${langHtml}
                            </div>` : ''}
                        </div>
                    </div>
                    
                    <div class="w-2/3 p-8 flex flex-col">
                        ${d.summary ? `
                        <div class="mb-6 bg-slate-50 p-4 rounded-xl border border-slate-100">
                            <h3 class="text-[12px] font-black uppercase text-slate-800 tracking-wider mb-2 flex items-center gap-2"><span class="w-1 h-4 bg-cv-primary rounded-full"></span>Profil</h3>
                            <p class="text-[11px] text-slate-600 leading-relaxed">${d.summary.replace(/\n/g, '<br>')}</p>
                        </div>` : ''}
                        
                        <div class="flex gap-6">
                            <div class="flex-1">
                                ${expHtml ? `
                                <div>
                                    <h3 class="text-[12px] font-black uppercase text-slate-800 tracking-wider mb-4 flex items-center gap-2"><span class="w-1 h-4 bg-cv-primary rounded-full"></span>Expérience</h3>
                                    ${expHtml}
                                </div>` : ''}
                                ${cstHtml}
                            </div>
                            
                            ${eduHtml ? `
                            <div class="w-[30%]">
                                <h3 class="text-[12px] font-black uppercase text-slate-800 tracking-wider mb-4 flex items-center gap-2"><span class="w-1 h-4 bg-cv-primary rounded-full"></span>Études</h3>
                                ${eduHtml}
                            </div>` : ''}
                        </div>
                    </div>
                </div>
            `;
        }

        // TEMPLATE 3: MINIMALIST (Clean Grid, High End)
        function renderMinimalist(d) {
            let photo = d.photo ? `<div class="w-20 h-20 bg-slate-100 shrink-0 border border-slate-200 p-1"><img src="${d.photo}" class="w-full h-full object-cover grayscale"></div>` : '';
            
            let contacts = [];
            if(d.whatsapp) contacts.push(`<span class="font-semibold">${d.whatsapp}</span>`);
            if(d.email) contacts.push(`<span class="font-semibold">${d.email}</span>`);
            if(d.location) contacts.push(`<span class="font-semibold">${d.location}</span>`);
            if(d.link) contacts.push(`<span class="font-semibold">${d.link}</span>`);

            let expHtml = d.exp.map(e => `
                <div class="mb-4 grid grid-cols-4 gap-4 border-t border-slate-100 pt-3">
                    <div class="col-span-1 text-[10px] text-slate-400 font-bold tracking-widest mt-0.5">${e.dates}</div>
                    <div class="col-span-3">
                        <div class="font-bold text-[12px] text-slate-900 uppercase">${e.title}</div>
                        <div class="text-[11px] font-bold text-cv-primary mb-1.5">${e.company}</div>
                        <div class="text-[11px] text-slate-600 leading-relaxed">${e.desc}</div>
                    </div>
                </div>
            `).join('');

            let eduHtml = d.edu.map(e => `
                <div class="mb-3 grid grid-cols-4 gap-4 border-t border-slate-100 pt-2">
                    <div class="col-span-1 text-[10px] text-slate-400 font-bold tracking-widest mt-0.5">${e.dates}</div>
                    <div class="col-span-3">
                        <div class="font-bold text-[11px] text-slate-900 uppercase">${e.degree}</div>
                        <div class="text-[10px] text-slate-500 font-medium">${e.school}</div>
                    </div>
                </div>
            `).join('');

            let skillsHtml = d.skills.length > 0 ? `<div class="text-[11px] font-medium text-slate-700 leading-relaxed border-t border-slate-100 pt-2">${d.skills.join(' &nbsp;•&nbsp; ')}</div>` : '';
            let langHtml = d.languages.length > 0 ? `<div class="text-[11px] font-medium text-slate-700 leading-relaxed border-t border-slate-100 pt-2">${d.languages.join(' &nbsp;•&nbsp; ')}</div>` : '';
            
            let cstHtml = d.cst.map(c => `
                <div class="mt-6">
                    <h3 class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-2">${c.title}</h3>
                    <div class="border-t border-slate-100 pt-2 text-[11px] text-slate-600 leading-snug">${c.html}</div>
                </div>
            `).join('');

            return `
                <div class="p-10">
                    <div class="flex justify-between items-start mb-8 pb-6 border-b-2 border-slate-900">
                        <div class="flex-1 pr-6">
                            <h1 class="cv-name text-3xl font-black uppercase tracking-tighter text-slate-900 mb-1 leading-none">${d.name}</h1>
                            <div class="text-sm font-bold text-cv-primary uppercase tracking-widest mb-4">${d.title}</div>
                            <div class="flex flex-wrap gap-x-3 gap-y-1 text-[10px] text-slate-500 uppercase tracking-wider">
                                ${contacts.join(' <span class="text-slate-300">|</span> ')}
                            </div>
                        </div>
                        ${photo}
                    </div>

                    ${d.summary ? `
                    <div class="mb-8">
                        <h3 class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-2">Profil</h3>
                        <p class="text-[11px] text-slate-700 leading-relaxed text-justify border-t border-slate-100 pt-2">${d.summary.replace(/\n/g, '<br>')}</p>
                    </div>` : ''}

                    ${expHtml ? `
                    <div class="mb-8">
                        <h3 class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-2">Expérience Professionnelle</h3>
                        ${expHtml}
                    </div>` : ''}

                    ${eduHtml ? `
                    <div class="mb-8">
                        <h3 class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-2">Formation Académique</h3>
                        ${eduHtml}
                    </div>` : ''}

                    <div class="grid grid-cols-2 gap-8">
                        ${skillsHtml ? `
                        <div>
                            <h3 class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-2">Expertise</h3>
                            ${skillsHtml}
                        </div>` : ''}
                        
                        ${langHtml ? `
                        <div>
                            <h3 class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-2">Langues</h3>
                            ${langHtml}
                        </div>` : ''}
                    </div>

                    ${cstHtml}
                </div>
            `;
        }

        // -----------------------------------------------------
        // AI MAGIC WRITER
        // -----------------------------------------------------
        function generateAIProfile() {
            const title = document.getElementById('in-title').value;
            const btn = document.getElementById('btn-ai-profile');
            const summary = document.getElementById('in-summary');

            if (!title) {
                alert("Veuillez indiquer le 'Titre du Poste' avant de générer.");
                return;
            }

            const originHtml = btn.innerHTML;
            btn.innerHTML = `<i class="fa-solid fa-circle-notch fa-spin text-white"></i><span>Magie en cours...</span>`;
            btn.disabled = true;

            fetch('api/generate-ai-profile.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ job_title: title })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success && data.text) {
                    let text = data.text;
                    summary.value = "";
                    let i = 0;
                    function type() {
                        if (i < text.length) {
                            summary.value += text.charAt(i);
                            i++;
                            if(i%3===0) updateLiveCV();
                            setTimeout(type, 15);
                        } else {
                            btn.innerHTML = originHtml;
                            btn.disabled = false;
                            updateLiveCV();
                        }
                    }
                    type();
                } else {
                    alert("Erreur de l'IA: " + (data.error || "Réponse invalide."));
                    btn.innerHTML = originHtml;
                    btn.disabled = false;
                }
            }).catch(e => {
                console.error(e);
                alert("Erreur serveur IA.");
                btn.innerHTML = originHtml;
                btn.disabled = false;
            });
        }

        // -----------------------------------------------------
        // RESPONSIVE / MOBILE
        // -----------------------------------------------------
        function scaleCVPreview() {
            const wrapper = document.getElementById('cv-scale-wrapper');
            const panel = document.getElementById('preview-panel');
            if (!wrapper || !panel) return;
            const panelWidth = panel.clientWidth - 40;
            const paperWidth = 794; 
            if (panelWidth < paperWidth) {
                const scale = panelWidth / paperWidth;
                wrapper.style.transform = `scale(${scale})`;
            } else {
                wrapper.style.transform = 'scale(1)';
            }
        }

        function toggleMobileView(view) {
            const lP = document.getElementById('left-form-panel');
            const rP = document.getElementById('preview-panel');
            if (view === 'edit') {
                lP.classList.remove('hidden'); rP.classList.add('hidden'); rP.classList.remove('flex');
            } else {
                lP.classList.add('hidden'); rP.classList.remove('hidden'); rP.classList.add('flex');
                // scaleCVPreview();
            }
        }

        function triggerDownload() {
            // Minimalist save logic for now, then print
            window.print();
        }

    </script>
</body>
</html>
