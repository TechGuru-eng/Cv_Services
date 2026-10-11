<?php
$code = $_SERVER['REDIRECT_STATUS'] ?? 404;
$title = "Oups ! Une erreur est survenue";
$message = "Il semble que quelque chose ne se soit pas passé comme prévu.";
$badge = "ERREUR $code";

switch ($code) {
    case 403:
        $title = "Accès Interdit ⛔";
        $message = "Désolé, vous n'avez pas la permission d'accéder à cette page.";
        break;
    case 404:
        $title = "Page Introuvable 🔍";
        $message = "La page que vous recherchez semble avoir disparu ou n'a jamais existé.";
        break;
    case 500:
        $title = "Erreur Serveur 💥";
        $message = "Oups, notre serveur rencontre un petit problème. Nos experts sont dessus !";
        break;
}
?>
<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?> – HireMe CV Services</title>
    <!-- Favicons -->
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] },
                    colors: { primary: '#0ea5e9', accent: '#22c55e', neutral: '#111827', bg: '#f8fafc', hireorange: '#f97316' },
                    animation: { 'bounce-slow': 'bounce 3s infinite' }
                }
            }
        }
    </script>
    <script src="assets/js/site-language.js?v=4" defer></script>

    <!-- Shared HireMe favicon -->
    <link rel="icon" type="image/png" sizes="48x48" href="assets/favicons/favicon-48.png?v=20261011">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicons/favicon-32.png?v=20261011">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/favicons/favicon-16.png?v=20261011">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/favicons/apple-touch-icon-180.png?v=20261011">
</head>

<body class="bg-bg text-neutral h-full flex flex-col items-center justify-center p-4 relative overflow-hidden">
    <!-- Watermark -->
    <div class="absolute inset-0 flex items-center justify-center opacity-5 pointer-events-none z-0">
        <h1 class="text-[15rem] font-black text-gray-900 leading-none"><?php echo $code; ?></h1>
    </div>

    <div class="z-10 w-full max-w-2xl text-center space-y-8">
        <!-- Icon -->
        <div class="inline-flex items-center justify-center w-24 h-24 bg-white rounded-full shadow-lg border border-gray-100 mb-4 animate-bounce-slow">
            <svg class="w-12 h-12 text-hireorange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
        </div>

        <!-- Message -->
        <div>
            <span class="inline-block px-3 py-1 bg-red-100 text-red-600 rounded-full text-xs font-bold mb-4 tracking-wider"><?php echo $badge; ?></span>
            <h1 class="text-3xl md:text-5xl font-extrabold text-neutral mb-4"><?php echo $title; ?></h1>
            <p class="text-lg text-gray-600 max-w-md mx-auto"><?php echo $message; ?></p>
        </div>

        <!-- Actions -->
        <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
            <a href="index" class="w-full sm:w-auto px-8 py-3 bg-primary text-white font-bold rounded-full shadow-lg hover:bg-sky-600 transition-all transform hover:scale-105 flex items-center justify-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Retour à l'accueil
            </a>
            <a href="https://wa.me/237654250688" class="w-full sm:w-auto px-8 py-3 bg-white text-gray-700 font-bold rounded-full border border-gray-200 hover:border-green-500 hover:text-green-600 transition-all flex items-center justify-center group">
                <svg class="w-5 h-5 mr-2 text-gray-400 group-hover:text-green-500 transition-colors" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.52.151-.174.2-.298.3-.495.099-.198.05-.372-.025-.52-.075-.149-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
                </svg>
                Contacter le support
            </a>
        </div>
        
        <!-- Divider -->
        <div class="w-16 h-1 bg-gray-100 mx-auto rounded-full"></div>

        <!-- Footer / Community -->
        <div class="space-y-4">
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-widest">Rejoignez la communauté HireMe</h3>
            <div class="flex flex-wrap justify-center gap-4">
               <a href="https://hiremeguide.com" target="_blank" class="p-2 bg-white rounded-full shadow-sm hover:shadow-md hover:text-primary transition-all text-gray-500" aria-label="Newsletter">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"></path></svg>
                </a>
                <a href="https://whatsapp.com/channel/0029Vb6XRzI1t90bk6USq33g" target="_blank" class="p-2 bg-white rounded-full shadow-sm hover:shadow-md hover:text-green-500 transition-all text-gray-500" aria-label="WhatsApp">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.52.151-.174.2-.298.3-.495.099-.198.05-.372-.025-.52-.075-.149-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                </a>
            </div>
        </div>
    </div>
</body>
</html>
