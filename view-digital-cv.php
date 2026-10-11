<?php
/**
 * Digital CV Public Viewer
 * Renders the CV Builder JSON data as a clean, responsive web page.
 */
require_once 'admin/config/db.php';

$slug = $_GET['slug'] ?? '';
if (empty($slug)) {
    die("Lien CV invalide.");
}

$stmt = $pdo->prepare("SELECT * FROM cv_builder_leads WHERE slug = ? LIMIT 1");
$stmt->execute([$slug]);
$lead = $stmt->fetch();

if (!$lead || empty($lead['cv_data'])) {
    die("Ce CV n'existe pas ou a été supprimé.");
}

require_once __DIR__ . '/includes/digital-cv-data.php';
try {
    $cvJson = json_encode(digital_cv_data($lead['cv_data']), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    http_response_code(422);
    exit('Les données de ce CV sont invalides.');
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CV de <?= htmlspecialchars($lead['name']) ?> - HireMe</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,600;0,700;1,400&family=Playfair+Display:ital,wght@0,600;0,700;0,800;1,600&family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            --cv-dark: #1e293b;
            --cv-gray: #64748b;
        }

        body {
            background-color: #f1f5f9;
        }

        .cv-paper {
            width: 100%;
            max-width: 210mm; /* A4 max width */
            background: white;
            color: #1e293b;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            margin: 0 auto;
            position: relative;
            word-wrap: break-word;
        }

        @media (min-width: 794px) {
            .cv-paper {
                min-height: 297mm;
                margin-top: 2rem;
                margin-bottom: 2rem;
                border-radius: 8px;
                overflow: hidden;
            }
        }

        /* Spacing Presets */
        .cv-spacing-compact { padding: 10mm; }
        .cv-spacing-compact .cv-section-title { margin-bottom: 8px; }
        .cv-spacing-compact .cv-section { margin-bottom: 12px; }
        .cv-spacing-compact .cv-item { margin-bottom: 6px; }

        .cv-spacing-normal { padding: 15mm; }
        .cv-spacing-normal .cv-section-title { margin-bottom: 14px; }
        .cv-spacing-normal .cv-section { margin-bottom: 20px; }
        .cv-spacing-normal .cv-item { margin-bottom: 12px; }

        .cv-spacing-airy { padding: 20mm; }
        .cv-spacing-airy .cv-section-title { margin-bottom: 20px; }
        .cv-spacing-airy .cv-section { margin-bottom: 28px; }
        .cv-spacing-airy .cv-item { margin-bottom: 18px; }

        /* Font Pairings */
        .font-pair-modern { font-family: 'Plus Jakarta Sans', sans-serif; }
        
        .font-pair-classic { font-family: 'Lora', serif; }
        .font-pair-classic h1, .font-pair-classic h2, .font-pair-classic h3, .font-pair-classic .cv-name, .font-pair-classic .cv-section-title { font-family: 'Playfair Display', serif; }
        
        .font-pair-elegant { font-family: 'Inter', sans-serif; }
        .font-pair-elegant h1, .font-pair-elegant h2, .font-pair-elegant h3, .font-pair-elegant .cv-name, .font-pair-elegant .cv-section-title { font-family: 'Outfit', sans-serif; }

        /* Color presets */
        .theme-blue { --cv-primary: #0284c7; }
        .theme-green { --cv-primary: #10b981; }
        .theme-purple { --cv-primary: #8b5cf6; }
        .theme-charcoal { --cv-primary: #1e293b; }
        .theme-burgundy { --cv-primary: #881337; }
    </style>
    <script src="assets/js/site-language.js?v=4" defer></script>

    <!-- Shared HireMe favicon -->
    <link rel="icon" type="image/png" sizes="48x48" href="assets/favicons/favicon-48.png?v=20261011">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicons/favicon-32.png?v=20261011">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/favicons/favicon-16.png?v=20261011">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/favicons/apple-touch-icon-180.png?v=20261011">
</head>
<body class="flex flex-col min-h-screen">

    <!-- Sticky Header Banner -->
    <div class="bg-slate-900 text-white text-center py-3 px-4 shadow-md sticky top-0 z-50 flex justify-between items-center">
        <div class="text-xs font-bold text-slate-300">
            Profil Numérique <span class="hidden sm:inline">vérifié de <?= htmlspecialchars($lead['name']) ?></span>
        </div>
        <a href="cv-builder.php" class="bg-gradient-to-r from-sky-500 to-indigo-600 hover:from-sky-400 hover:to-indigo-500 text-white px-4 py-1.5 rounded-full text-[11px] font-black shadow-lg transition-all">
            Créez le vôtre sur HireMe ✨
        </a>
    </div>

    <!-- CV Paper Container -->
    <div class="flex-grow flex items-start justify-center p-0 sm:p-4">
        <div id="cv-root" class="cv-paper"></div>
    </div>

    <!-- Footer Action -->
    <div class="bg-white border-t border-slate-200 py-6 text-center">
        <p class="text-xs text-slate-500 mb-3">Ce profil vous intéresse ? Contactez ce candidat directement :</p>
        <div class="flex justify-center gap-3">
            <a href="mailto:<?= htmlspecialchars($lead['email']) ?>" class="bg-sky-50 text-sky-600 border border-sky-200 hover:bg-sky-100 px-4 py-2 rounded-lg text-xs font-bold transition-all"><i class="fa-solid fa-envelope mr-1.5"></i> Email</a>
            <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $lead['whatsapp']) ?>" target="_blank" class="bg-emerald-50 text-emerald-600 border border-emerald-200 hover:bg-emerald-100 px-4 py-2 rounded-lg text-xs font-bold transition-all"><i class="fa-brands fa-whatsapp mr-1.5"></i> WhatsApp</a>
        </div>
    </div>

    <script>
        const cvData = <?= $cvJson ?>;
        
        // Render Functions exactly like cv-builder.php
        function renderParisTemplate(data) {
            let expHtml = data.experiences.map(exp => `
                <div class="cv-item mb-4">
                    <div class="flex justify-between items-baseline mb-0.5">
                        <span class="font-extrabold text-sm text-slate-800">${exp.title}</span>
                        <span class="text-[10px] text-slate-400 font-semibold">${exp.years}</span>
                    </div>
                    <div class="text-xs font-bold text-[var(--cv-primary)] mb-1">${exp.company}</div>
                    <p class="text-[11px] text-slate-600 leading-relaxed whitespace-pre-line">${exp.desc}</p>
                </div>
            `).join('');

            let eduHtml = data.education.map(edu => `
                <div class="cv-item mb-3">
                    <div class="flex justify-between items-baseline mb-0.5">
                        <span class="font-bold text-xs text-slate-800">${edu.degree}</span>
                        <span class="text-[10px] text-slate-400 font-semibold">${edu.years}</span>
                    </div>
                    <div class="text-[11px] font-semibold text-slate-600">${edu.school}</div>
                </div>
            `).join('');

            let skillsHtml = data.skills.map(s => `<span class="inline-block bg-slate-100 text-slate-800 text-[10px] font-bold px-2 py-1 rounded mb-1.5 mr-1.5">${s}</span>`).join('');
            let langHtml = data.languages.map(l => `<div class="text-xs text-slate-600 font-medium mb-1"><i class="fa-solid fa-circle-chevron-right text-[10px] text-[var(--cv-primary)] mr-1.5"></i>${l}</div>`).join('');
            let photoHtml = data.photo ? `<div class="w-24 h-24 rounded-full border-4 border-[var(--cv-primary)] overflow-hidden shrink-0 mx-auto mb-4 shadow-md"><img src="${data.photo}" class="w-full h-full object-cover"></div>` : '';

            return `
                <div class="flex-grow flex flex-col sm:flex-row gap-8">
                    <div class="w-full sm:w-64 bg-slate-50 p-8 sm:-ml-16 sm:-my-16 sm:pt-12 flex flex-col border-r border-slate-100">
                        ${photoHtml}
                        <div class="space-y-6 mt-4">
                            <div>
                                <h3 class="cv-section-title text-xs font-black uppercase text-[var(--cv-primary)] tracking-wider border-b border-slate-200 pb-1 mb-2.5">Coordonnées</h3>
                                <div class="space-y-2 text-slate-600 text-[11px]">
                                    <div class="flex items-center gap-2"><i class="fa-solid fa-location-dot text-[var(--cv-primary)] shrink-0"></i>${data.location}</div>
                                </div>
                            </div>
                            <div><h3 class="cv-section-title text-xs font-black uppercase text-[var(--cv-primary)] tracking-wider border-b border-slate-200 pb-1 mb-2.5">Formations</h3><div class="space-y-3">${eduHtml}</div></div>
                            <div><h3 class="cv-section-title text-xs font-black uppercase text-[var(--cv-primary)] tracking-wider border-b border-slate-200 pb-1 mb-2.5">Compétences</h3><div class="flex flex-wrap">${skillsHtml}</div></div>
                            <div><h3 class="cv-section-title text-xs font-black uppercase text-[var(--cv-primary)] tracking-wider border-b border-slate-200 pb-1 mb-2.5">Langues</h3><div class="space-y-1">${langHtml}</div></div>
                        </div>
                    </div>
                    <div class="flex-grow sm:pl-4 p-6 sm:p-0">
                        <div class="pb-6 mb-6 border-b border-slate-100 mt-2">
                            <h1 class="cv-name text-3xl font-extrabold text-slate-900 tracking-tight uppercase leading-none mb-1.5">${data.name}</h1>
                            <div class="text-sm font-bold text-[var(--cv-primary)] uppercase tracking-wider">${data.title}</div>
                        </div>
                        <div class="space-y-6">
                            <div class="cv-section"><h3 class="cv-section-title text-xs font-black uppercase text-slate-400 tracking-wider mb-2.5">Profil Professionnel</h3><p class="text-xs text-slate-600 leading-relaxed">${data.summary}</p></div>
                            <div class="cv-section"><h3 class="cv-section-title text-xs font-black uppercase text-slate-400 tracking-wider mb-3.5">Parcours Professionnel</h3><div class="space-y-4">${expHtml}</div></div>
                        </div>
                    </div>
                </div>
            `;
        }

        function renderLondonTemplate(data) {
            let expHtml = data.experiences.map(exp => `
                <div class="cv-item mb-4">
                    <div class="flex justify-between items-baseline mb-0.5"><span class="font-extrabold text-sm text-slate-800">${exp.title}</span><span class="text-[10px] text-slate-400 font-semibold">${exp.years}</span></div>
                    <div class="text-xs font-bold text-[var(--cv-primary)] mb-1">${exp.company}</div>
                    <p class="text-[11px] text-slate-600 leading-relaxed whitespace-pre-line">${exp.desc}</p>
                </div>
            `).join('');

            let eduHtml = data.education.map(edu => `<div class="cv-item mb-3"><div class="flex justify-between items-baseline mb-0.5"><span class="font-bold text-xs text-slate-800">${edu.degree}</span><span class="text-[10px] text-slate-400 font-semibold">${edu.years}</span></div><div class="text-[11px] font-semibold text-slate-600">${edu.school}</div></div>`).join('');
            let skillsHtml = data.skills.map(s => `<span class="inline-block bg-slate-100 text-slate-800 text-[10px] font-bold px-2.5 py-1 rounded-full mb-1.5 mr-1.5">${s}</span>`).join('');
            let langHtml = data.languages.map(l => `<span class="inline-block border border-slate-200 text-slate-600 text-[10px] font-semibold px-2 py-0.5 rounded mr-1.5 mb-1.5">${l}</span>`).join('');
            let photoHtml = data.photo ? `<div class="w-20 h-20 rounded-full border border-slate-200 overflow-hidden shrink-0 shadow-sm"><img src="${data.photo}" class="w-full h-full object-cover"></div>` : '';

            return `
                <div class="p-6 sm:p-0 flex flex-col h-full">
                    <div class="flex flex-col sm:flex-row items-center sm:items-start gap-6 pb-6 mb-6 border-b-2 border-slate-100 text-center sm:text-left">
                        ${photoHtml}
                        <div class="flex-grow">
                            <h1 class="cv-name text-3xl font-extrabold text-slate-900 tracking-tight leading-none mb-1">${data.name}</h1>
                            <div class="text-xs font-bold text-[var(--cv-primary)] uppercase tracking-widest mb-2">${data.title}</div>
                            <div class="flex flex-wrap justify-center sm:justify-start gap-x-4 gap-y-1 text-[11px] text-slate-500">
                                <span><i class="fa-solid fa-location-dot text-slate-400 mr-1.5"></i>${data.location}</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex-grow flex flex-col sm:flex-row gap-8">
                        <div class="flex-1 space-y-6">
                            <div class="cv-section"><h3 class="cv-section-title text-xs font-black uppercase text-[var(--cv-primary)] tracking-wider border-b border-slate-100 pb-1 mb-2.5">Profil Professionnel</h3><p class="text-xs text-slate-600 leading-relaxed">${data.summary}</p></div>
                            <div class="cv-section"><h3 class="cv-section-title text-xs font-black uppercase text-[var(--cv-primary)] tracking-wider border-b border-slate-100 pb-1 mb-3.5">Parcours Professionnel</h3><div class="space-y-4">${expHtml}</div></div>
                        </div>
                        <div class="w-full sm:w-64 space-y-6">
                            <div class="cv-section"><h3 class="cv-section-title text-xs font-black uppercase text-[var(--cv-primary)] tracking-wider border-b border-slate-100 pb-1 mb-2.5">Formations</h3><div class="space-y-3">${eduHtml}</div></div>
                            <div class="cv-section"><h3 class="cv-section-title text-xs font-black uppercase text-[var(--cv-primary)] tracking-wider border-b border-slate-100 pb-1 mb-2.5">Compétences</h3><div class="flex flex-wrap">${skillsHtml}</div></div>
                            <div class="cv-section"><h3 class="cv-section-title text-xs font-black uppercase text-[var(--cv-primary)] tracking-wider border-b border-slate-100 pb-1 mb-2.5">Langues</h3><div class="flex flex-wrap">${langHtml}</div></div>
                        </div>
                    </div>
                </div>
            `;
        }

        function renderBerlinTemplate(data) {
            let expHtml = data.experiences.map(exp => `
                <div class="cv-item mb-4 grid grid-cols-1 sm:grid-cols-4 gap-1 sm:gap-4">
                    <div class="col-span-1 text-[10px] text-slate-400 font-bold tracking-wider">${exp.years}</div>
                    <div class="col-span-3"><span class="font-extrabold text-sm text-slate-800 block">${exp.title}</span><span class="text-xs font-bold text-[var(--cv-primary)] block mb-1">${exp.company}</span><p class="text-[11px] text-slate-600 leading-relaxed whitespace-pre-line">${exp.desc}</p></div>
                </div>
            `).join('');

            let eduHtml = data.education.map(edu => `<div class="cv-item mb-3 grid grid-cols-1 sm:grid-cols-4 gap-1 sm:gap-4"><div class="col-span-1 text-[10px] text-slate-400 font-bold tracking-wider">${edu.years}</div><div class="col-span-3"><span class="font-bold text-xs text-slate-800 block">${edu.degree}</span><span class="text-[11px] font-semibold text-slate-600 block">${edu.school}</span></div></div>`).join('');
            let skillsHtml = data.skills.length > 0 ? data.skills.join('  •  ') : '';
            let langHtml = data.languages.length > 0 ? data.languages.join('  •  ') : '';
            let photoHtml = data.photo ? `<div class="w-20 h-20 border border-slate-100 overflow-hidden shrink-0"><img src="${data.photo}" class="w-full h-full object-cover"></div>` : '';

            return `
                <div class="hidden sm:block absolute inset-4 border border-slate-200 pointer-events-none -m-4"></div>
                <div class="flex-grow p-6 sm:p-4 relative">
                    <div class="flex flex-col sm:flex-row justify-between items-center sm:items-start gap-4 pb-6 mb-6 border-b border-slate-100 text-center sm:text-left">
                        <div>
                            <h1 class="cv-name text-2xl font-bold text-slate-900 tracking-tight mb-1 uppercase">${data.name}</h1>
                            <div class="text-xs font-bold text-[var(--cv-primary)] tracking-widest uppercase mb-3">${data.title}</div>
                            <div class="flex flex-wrap justify-center sm:justify-start gap-x-4 text-[10px] text-slate-500 font-bold uppercase tracking-wider">
                                <span><i class="fa-solid fa-location-dot text-slate-400 mr-1.5"></i>${data.location}</span>
                            </div>
                        </div>
                        ${photoHtml}
                    </div>
                    <div class="space-y-6">
                        <div class="cv-section"><h3 class="cv-section-title text-[10px] font-black uppercase text-[var(--cv-primary)] tracking-widest border-b border-slate-100 pb-1 mb-2.5">Profil</h3><p class="text-xs text-slate-600 leading-relaxed">${data.summary}</p></div>
                        <div class="cv-section"><h3 class="cv-section-title text-[10px] font-black uppercase text-[var(--cv-primary)] tracking-widest border-b border-slate-100 pb-1 mb-3.5">Parcours</h3><div class="space-y-4">${expHtml}</div></div>
                        <div class="cv-section"><h3 class="cv-section-title text-[10px] font-black uppercase text-[var(--cv-primary)] tracking-widest border-b border-slate-100 pb-1 mb-3.5">Formations</h3><div class="space-y-3">${eduHtml}</div></div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-8">
                            <div class="cv-section"><h3 class="cv-section-title text-[10px] font-black uppercase text-[var(--cv-primary)] tracking-widest border-b border-slate-100 pb-1 mb-2">Compétences</h3><p class="text-[11px] text-slate-600 leading-relaxed font-semibold">${skillsHtml}</p></div>
                            <div class="cv-section"><h3 class="cv-section-title text-[10px] font-black uppercase text-[var(--cv-primary)] tracking-widest border-b border-slate-100 pb-1 mb-2">Langues</h3><p class="text-[11px] text-slate-600 leading-relaxed font-semibold">${langHtml}</p></div>
                        </div>
                    </div>
                </div>
            `;
        }

        // Render init
        document.addEventListener('DOMContentLoaded', () => {
            const root = document.getElementById('cv-root');
            root.className = `cv-paper theme-${cvData.theme} cv-spacing-${cvData.spacing} font-pair-${cvData.font} text-left flex flex-col`;
            
            if (cvData.template === 'paris') root.innerHTML = renderParisTemplate(cvData);
            else if (cvData.template === 'london') root.innerHTML = renderLondonTemplate(cvData);
            else root.innerHTML = renderBerlinTemplate(cvData);
        });
    </script>
</body>
</html>
