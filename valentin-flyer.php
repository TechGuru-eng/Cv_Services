<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flyer Valentin - MonCv Pro</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;800;900&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        serif: ['Playfair Display', 'serif'],
                    }
                }
            }
        }
    </script>
    <style>
        .romantic-gradient {
            background: linear-gradient(135deg, #fff1f2 0%, #fff7ed 100%);
        }
        .gloss {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.4);
        }
        .text-glow {
            text-shadow: 0 0 15px rgba(225, 29, 72, 0.3);
        }
    </style>
    <script src="assets/js/site-language.js?v=4" defer></script>

    <!-- Shared HireMe favicon -->
    <link rel="icon" type="image/png" sizes="48x48" href="assets/favicons/favicon-48.png?v=20261011">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicons/favicon-32.png?v=20261011">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/favicons/favicon-16.png?v=20261011">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/favicons/apple-touch-icon-180.png?v=20261011">
</head>
<body class="romantic-gradient min-h-screen flex items-center justify-center p-4 font-sans">

    <!-- Flyer Container (Portrait Aspect Ratio) -->
    <div class="w-full max-w-[450px] aspect-[9/16] bg-white rounded-[3rem] shadow-2xl overflow-hidden relative border border-rose-100 flex flex-col">
        
        <!-- Header Image Area -->
        <div class="relative h-[45%] overflow-hidden">
            <img src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?q=80&w=1200&auto=format&fit=crop" class="w-full h-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-t from-white via-transparent to-transparent"></div>
            
            <!-- Branding -->
            <div class="absolute top-8 left-8">
                <div class="flex items-center gap-2">
                    <span class="text-2xl">🚀</span>
                    <span class="font-black text-slate-800 text-lg tracking-tighter">MonCv <span class="text-rose-600">Pro</span></span>
                </div>
            </div>
            
            <!-- Badge -->
            <div class="absolute top-8 right-8 bg-rose-600 text-white font-black text-[10px] uppercase py-2 px-4 rounded-full shadow-lg">
                Offre Limitée ❤️
            </div>
        </div>

        <!-- Content Area -->
        <div class="flex-1 px-8 pb-10 flex flex-col">
            
            <!-- Main Title -->
            <div class="text-center mb-6">
                <h1 class="font-serif text-4xl font-black text-slate-900 leading-tight mb-2">
                    Valentine <br> <span class="text-rose-600 text-glow">Career Deal</span>
                </h1>
                <p class="text-slate-500 font-bold text-xs uppercase tracking-[0.2em]">Bâtissez votre avenir ensemble</p>
            </div>

            <!-- Packs -->
            <div class="grid grid-cols-2 gap-4 mb-8">
                <div class="bg-rose-50 border border-rose-100 p-4 rounded-3xl text-center">
                    <p class="text-[10px] font-bold text-rose-400 uppercase mb-1">Pack Solo</p>
                    <div class="text-xl font-black text-rose-600">8.500 <small class="text-[8px]">XAF</small></div>
                </div>
                <div class="bg-slate-900 p-4 rounded-3xl text-center shadow-lg shadow-rose-100">
                    <p class="text-[10px] font-bold text-slate-400 uppercase mb-1 text-white opacity-60">Pack Couple</p>
                    <div class="text-xl font-black text-rose-500">14.500 <small class="text-[8px]">XAF</small></div>
                </div>
            </div>

            <!-- Inclusions -->
            <div class="bg-white/50 border border-slate-100 rounded-3xl p-5 mb-8">
                <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4 text-center">Ce que vous recevez :</h3>
                <ul class="space-y-3">
                    <li class="flex items-center gap-3 text-xs font-bold text-slate-700">
                        <span class="text-rose-500">✓</span> CV Professionnel (FR & EN)
                    </li>
                    <li class="flex items-center gap-3 text-xs font-bold text-slate-700">
                        <span class="text-rose-500">✓</span> Lettre de Motivation (FR & EN)
                    </li>
                    <li class="flex items-center gap-3 text-xs font-bold text-slate-700">
                        <span class="text-rose-500">✓</span> Top 20 Entreprises 🇨🇲
                    </li>
                </ul>
            </div>

            <!-- CTA -->
            <div class="mt-auto">
                <div class="text-center mb-4">
                    <p class="text-[10px] font-bold text-slate-400">Scanner pour commander</p>
                    <div class="w-16 h-16 bg-slate-100 rounded-xl mx-auto mt-2 flex items-center justify-center border border-slate-200">
                        <span class="text-2xl">📱</span>
                    </div>
                </div>
                
                <div class="bg-rose-600 text-white text-center py-4 rounded-2xl font-black text-xs uppercase tracking-widest shadow-xl shadow-rose-200">
                    moncvpro.hiremeguide.com/valentin.php
                </div>
            </div>

        </div>

        <!-- Decorative hearts -->
        <div class="absolute bottom-4 right-4 text-2xl animate-pulse opacity-20">❤️</div>
        <div class="absolute top-[40%] left-4 text-xl animate-pulse delay-75 opacity-20">❤️</div>
    </div>

    <!-- Instruction for Admin -->
    <div class="fixed bottom-6 right-6 md:right-12 bg-slate-900 text-white p-4 rounded-2xl shadow-2xl max-w-[200px] text-center">
        <p class="text-[10px] font-bold opacity-60 uppercase mb-2">Note pour l'Admin</p>
        <p class="text-xs">Faites une capture d'écran de ce flyer pour vos statuts WhatsApp ! 📸</p>
    </div>

</body>
</html>
