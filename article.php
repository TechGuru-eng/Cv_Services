<?php
require_once 'admin/config/db.php';
require_once 'includes/tracker.php';

function getReadingTime($content) {
    $wordCount = str_word_count(strip_tags($content));
    $minutes = ceil($wordCount / 200);
    return max(1, $minutes);
}

$publicBase = preg_replace('~/(?:blog/[^/]+|article(?:\.php)?)/?$~', '/', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
if (empty($slug)) {
    header('Location: ' . $publicBase . 'blog.php');
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM blog_posts WHERE slug = ? AND is_published = 1");
    $stmt->execute([$slug]);
    $post = $stmt->fetch();

    if (!$post) {
        header("HTTP/1.0 404 Not Found");
        require 'error.php';
        exit;
    }

    // Update views
    $updateStmt = $pdo->prepare("UPDATE blog_posts SET views_count = views_count + 1 WHERE id = ?");
    $updateStmt->execute([$post['id']]);

    // Format date
    $dateObj = new DateTime($post['created_at']);
    setlocale(LC_TIME, 'fr_FR.UTF8', 'fr.UTF8', 'fr_FR.utf-8', 'fr');
    $formattedDate = strftime("%d %b %Y", $dateObj->getTimestamp());
    $formattedDate = utf8_encode($formattedDate); // Handle encoding if necessary

    // Get next 2 articles
    $nextStmt = $pdo->prepare("SELECT title, slug, image_path, created_at FROM blog_posts WHERE id != ? AND is_published = 1 ORDER BY created_at DESC LIMIT 2");
    $nextStmt->execute([$post['id']]);
    $nextPosts = $nextStmt->fetchAll();

    // Get categories for sidebar
    $catStmt = $pdo->query("SELECT category, COUNT(*) as count FROM blog_posts WHERE is_published = 1 GROUP BY category ORDER BY count DESC LIMIT 5");
    $categories = $catStmt ? $catStmt->fetchAll() : [];

    // Most read for sidebar
    $readStmt = $pdo->prepare("SELECT title, slug, views_count FROM blog_posts WHERE id != ? AND is_published = 1 ORDER BY views_count DESC LIMIT 5");
    $readStmt->execute([$post['id']]);
    $popularPosts = $readStmt->fetchAll();
} catch (PDOException $e) {
    header("HTTP/1.0 503 Service Unavailable");
    die("La base de données du blog est en cours de configuration. Veuillez patienter.");
}

// Prepare SEO metadata
$pageTitle = htmlspecialchars($post['title']) . " | MonCV Blog";
$excerpt = htmlspecialchars(mb_strimwidth(strip_tags($post['content']), 0, 160, "..."));
$pageUrl = "https://moncvpro.hiremeguide.com/blog/" . urlencode($post['slug']);
$imageUrl = $post['image_path'] ? "https://moncvpro.hiremeguide.com/" . $post['image_path'] : "https://moncvpro.hiremeguide.com/assets/images/default_blog_preview.png";

?>
<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
<base href="<?= htmlspecialchars($publicBase, ENT_QUOTES, 'UTF-8') ?>">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <meta name="description" content="<?= $excerpt ?>">
    <meta name="author" content="MonCV">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?= $pageUrl ?>" />
    
    <!-- Open Graph / Social Media -->
    <meta property="og:type" content="article">
    <meta property="og:url" content="<?= $pageUrl ?>">
    <meta property="og:title" content="<?= htmlspecialchars($post['title']) ?>">
    <meta property="og:description" content="<?= $excerpt ?>">
    <meta property="og:image" content="<?= $imageUrl ?>">
    
    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="<?= $pageUrl ?>">
    <meta name="twitter:title" content="<?= htmlspecialchars($post['title']) ?>">
    <meta name="twitter:description" content="<?= $excerpt ?>">
    <meta name="twitter:image" content="<?= $imageUrl ?>">

    <!-- Favicons -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'system-ui', 'sans-serif'],
                        serif: ['Playfair Display', 'serif'],
                        title: ['Outfit', 'sans-serif']
                    },
                    colors: {
                        primary: '#0ea5e9',
                        dark: '#0f172a'
                    }
                }
            }
        }
    </script>
    <style>
        /* Rich Text Content Styling */
        .prose h2 { font-size: 1.875rem; font-weight: 700; margin-top: 2.5rem; margin-bottom: 1.25rem; color: #0f172a; }
        .prose h3 { font-size: 1.5rem; font-weight: 600; margin-top: 2rem; margin-bottom: 1rem; color: #1e293b; }
        .prose p { margin-bottom: 1.25rem; line-height: 1.8; color: #334155; }
        .prose ul { list-style-type: disc; padding-left: 1.5rem; margin-bottom: 1.5rem; }
        .prose ol { list-style-type: decimal; padding-left: 1.5rem; margin-bottom: 1.5rem; }
        .prose li { margin-bottom: 0.5rem; color: #334155; }
        .prose a { color: #0ea5e9; text-decoration: underline; font-weight: 500; }
        .prose a:hover { color: #0369a1; }
        .prose blockquote { border-left: 4px solid #0ea5e9; padding-left: 1rem; font-style: italic; color: #475569; margin: 2rem 0; background: #f8fafc; padding: 1.5rem; border-radius: 0 0.5rem 0.5rem 0; }
        .prose img { border-radius: 0.75rem; max-width: 100%; height: auto; margin: 2rem auto; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); }
        .prose strong { color: #0f172a; font-weight: 700; }
    </style>
    <script src="assets/js/site-language.js?v=4" defer></script>

    <!-- Shared HireMe favicon -->
    <link rel="icon" type="image/png" sizes="48x48" href="assets/favicons/favicon-48.png?v=20261011">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicons/favicon-32.png?v=20261011">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/favicons/favicon-16.png?v=20261011">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/favicons/apple-touch-icon-180.png?v=20261011">
</head>
<body class="bg-white antialiased text-gray-800">

    <!-- Header -->
    <header class="bg-white border-b border-gray-100 sticky top-0 z-50">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <a href="blog.php" class="flex items-center gap-2 text-gray-500 hover:text-primary transition font-bold text-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Retour au Blog
            </a>
            
            <!-- Quick Share Header -->
            <div class="flex items-center gap-3">
                <a href="https://wa.me/?text=<?= urlencode("Regarde cet article très intéressant sur le CV : " . $pageTitle . " " . $pageUrl) ?>" target="_blank" class="w-8 h-8 flex items-center justify-center rounded-full bg-[#25D366]/10 text-[#25D366] hover:bg-[#25D366] hover:text-white transition">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 0 0-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z"/></svg>
                </a>
                <a href="https://www.linkedin.com/shareArticle?mini=true&url=<?= urlencode($pageUrl) ?>&title=<?= urlencode($pageTitle) ?>" target="_blank" class="w-8 h-8 flex items-center justify-center rounded-full bg-[#0077b5]/10 text-[#0077b5] hover:bg-[#0077b5] hover:text-white transition">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 0 1-2.063-2.065 2.064 2.064 0 1 1 2.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                </a>
            </div>
        </div>
    </header>

    <main class="max-w-3xl mx-auto px-4 sm:px-6 py-12">
        <!-- Article Header -->
        <header class="mb-10 text-center">
            <div class="flex items-center justify-center gap-4 text-xs font-bold text-gray-400 uppercase tracking-wider mb-6">
                <span><?= date('d M, Y', strtotime($post['created_at'])) ?></span>
                <span>•</span>
                <span class="flex items-center gap-1"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg> <?= $post['views_count'] ?> VUES</span>
                <span>•</span>
                <span>⏱️ <?= getReadingTime($post['content']) ?> MIN DE LECTURE</span>
            </div>
            
            <h1 class="text-4xl md:text-5xl font-black text-dark leading-[1.1] mb-8 font-serif tracking-tight">
                <?= htmlspecialchars($post['title']) ?>
            </h1>

            <div class="flex items-center justify-center gap-4 mb-10 border-b border-gray-100 pb-10">
                <?php
                $authorImg = $post['author_image_path'] ?? 'https://ui-avatars.com/api/?name=NHN&background=0f172a&color=fff';
                $finalImgUrl = (strpos($authorImg, 'http') === 0) ? $authorImg : '../' . $authorImg;
                ?>
                <img src="<?= htmlspecialchars($finalImgUrl) ?>" class="w-12 h-12 rounded-full object-cover shadow-sm bg-gray-200" alt="<?= htmlspecialchars($post['author_name'] ?? 'N.H.N') ?>">
                <div class="text-left">
                    <p class="font-bold text-gray-900"><?= htmlspecialchars($post['author_name'] ?? 'N.H.N') ?></p>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Talent Sourcing</p>
                </div>
            </div>

            <?php if($post['image_path']): ?>
                <div class="w-full aspect-video rounded-3xl overflow-hidden shadow-2xl mb-12">
                    <img src="<?= htmlspecialchars($post['image_path']) ?>" alt="<?= htmlspecialchars($post['title']) ?>" class="w-full h-full object-cover">
                </div>
            <?php else: ?>
                <div class="w-full aspect-video rounded-3xl overflow-hidden shadow-2xl mb-12">
                    <img src="assets/images/default_blog_preview.png" alt="MonCV Blog" class="w-full h-full object-cover">
                </div>
            <?php endif; ?>
        </header>

        <!-- Article Content -->
        <div id="articleContainer" class="relative">
            <article id="articleContent" class="prose prose-lg mx-auto overflow-hidden relative max-h-[800px] transition-all duration-1000">
                <?php
                    $content = $post['content'];
                    // Convert plain text URLs to clickable links, ensuring we don't mess up existing hrefs
                    $content = preg_replace('~(?<!href=["\'])(?<!src=["\'])(https?://[^\s<]+)~i', '<a href="$1" target="_blank" rel="noopener noreferrer" class="text-primary hover:underline">$1</a>', $content);
                    echo $content;
                ?>
                
                <!-- Blur overlay -->
                <div id="blurOverlay" class="absolute bottom-0 left-0 w-full h-64 bg-gradient-to-t from-white to-transparent pointer-events-none z-10"></div>
            </article>

            <!-- Subscription Paywall Popup -->
            <div id="paywallPopup" class="absolute bottom-10 left-0 w-full z-20 flex justify-center px-4">
                <div class="bg-white p-8 rounded-3xl shadow-[0_20px_50px_rgba(8,_112,_184,_0.15)] border border-blue-50 max-w-lg w-full text-center relative overflow-hidden">
                    <!-- Decorative element -->
                    <div class="absolute top-0 right-0 -mr-8 -mt-8 w-32 h-32 rounded-full bg-primary/10 blur-2xl pointer-events-none"></div>
                    
                    <h3 class="text-2xl font-black text-dark mb-3">Débloquez cet article 🔓</h3>
                    <p class="text-gray-500 text-sm mb-6">Inscrivez-vous gratuitement pour lire la suite et recevoir nos meilleurs conseils carrière.</p>
                    
                    <form id="subscribeForm" class="space-y-4">
                        <input type="text" id="subName" required placeholder="Votre prénom" class="w-full px-5 py-3 bg-gray-50 border border-gray-100 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:bg-white transition-all text-sm font-medium">
                        <input type="email" id="subEmail" required placeholder="Votre email" class="w-full px-5 py-3 bg-gray-50 border border-gray-100 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:bg-white transition-all text-sm font-medium">
                        <input type="tel" id="subPhone" required placeholder="Votre numéro de téléphone" class="w-full px-5 py-3 bg-gray-50 border border-gray-100 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:bg-white transition-all text-sm font-medium">
                        
                        <button type="submit" id="subBtn" class="w-full bg-primary hover:bg-sky-600 text-white font-bold py-4 rounded-xl shadow-lg shadow-sky-200 transition-all transform hover:-translate-y-1">
                            Continuer la lecture
                        </button>
                        <p id="subError" class="text-red-500 text-xs font-bold hidden"></p>
                    </form>
                </div>
            </div>
        </div>

        <!-- Interactive Footer (Like & Share) -->
        <div class="mt-16 pt-8 border-t border-gray-100 flex flex-col md:flex-row items-center justify-between gap-6">
            
            <!-- Like Button -->
            <button id="likeBtn" data-id="<?= $post['id'] ?>" class="flex items-center gap-3 px-6 py-3 rounded-full border-2 border-pink-100 bg-pink-50 text-pink-600 font-bold hover:bg-pink-100 hover:border-pink-200 transition group">
                <svg id="likeIcon" class="w-6 h-6 transform group-hover:scale-125 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg>
                <span id="likeCount"><?= $post['likes_count'] ?> J'aime</span>
            </button>

            <!-- Share Text -->
            <div class="flex items-center gap-4">
                <span class="text-sm font-bold text-gray-400">PARTAGER :</span>
                <!-- WhatsApp -->
                <a href="https://wa.me/?text=<?= urlencode("Regarde cet article : " . $pageTitle . " " . $pageUrl) ?>" target="_blank" class="w-10 h-10 flex items-center justify-center rounded-full bg-gray-100 text-gray-500 hover:bg-[#25D366] hover:text-white transition shadow-sm hover:shadow-md">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 0 0-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z"/></svg>
                </a>
                <!-- Facebook -->
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($pageUrl) ?>" target="_blank" class="w-10 h-10 flex items-center justify-center rounded-full bg-gray-100 text-gray-500 hover:bg-[#1877F2] hover:text-white transition shadow-sm hover:shadow-md">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                </a>
                <!-- X (Twitter) -->
                <a href="https://twitter.com/intent/tweet?url=<?= urlencode($pageUrl) ?>&text=<?= urlencode($pageTitle) ?>" target="_blank" class="w-10 h-10 flex items-center justify-center rounded-full bg-gray-100 text-gray-500 hover:bg-black hover:text-white transition shadow-sm hover:shadow-md">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                </a>
            </div>
        </div>
    </main>

    <!-- Read Next Section -->
    <?php if(!empty($relatedPosts)): ?>
    <section class="bg-gray-50 py-16 mt-12 border-t border-gray-200">
        <div class="max-w-4xl mx-auto px-4 sm:px-6">
            <h3 class="text-2xl font-black text-dark mb-8">À lire ensuite</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <?php foreach($relatedPosts as $rel): ?>
                    <a href="blog/<?= htmlspecialchars(rawurlencode($rel['slug'])) ?>" class="group bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition flex flex-col md:flex-row h-full">
                        <div class="w-full md:w-1/3 aspect-video md:aspect-auto bg-gray-200 relative overflow-hidden shrink-0">
                            <?php if($rel['image_path']): ?>
                                <img src="<?= htmlspecialchars($rel['image_path']) ?>" class="w-full h-full object-cover transform group-hover:scale-110 transition duration-500">
                            <?php else: ?>
                                <img src="assets/images/default_blog_preview.png" class="w-full h-full object-cover transform group-hover:scale-110 transition duration-500">
                            <?php endif; ?>
                        </div>
                        <div class="p-6 flex flex-col justify-center">
                            <h4 class="font-bold text-dark mb-2 leading-snug group-hover:text-primary transition line-clamp-2"><?= htmlspecialchars($rel['title']) ?></h4>
                            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Lien l'article →</span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <script>
        // Handle Like Button logic
        const likeBtn = document.getElementById('likeBtn');
        const likeCount = document.getElementById('likeCount');
        const likeIcon = document.getElementById('likeIcon');
        let hasLiked = localStorage.getItem('liked_post_<?= $post['id'] ?>');

        if (hasLiked) {
            // Already liked state
            likeBtn.classList.add('bg-pink-500', 'text-white', 'border-pink-500');
            likeBtn.classList.remove('bg-pink-50', 'text-pink-600');
            likeIcon.setAttribute('fill', 'currentColor');
        }

        likeBtn.addEventListener('click', async () => {
            if (hasLiked) return; // Prevent multiple likes

            const postId = likeBtn.getAttribute('data-id');
            
            try {
                // Call API
                const formData = new FormData();
                formData.append('post_id', postId);
                const response = await fetch('api/like_article.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                if (data.success) {
                    // Update UI
                    likeCount.textContent = data.new_count + (data.new_count > 1 ? " J'aime" : " J'aime");
                    likeBtn.classList.add('bg-pink-500', 'text-white', 'border-pink-500');
                    likeBtn.classList.remove('bg-pink-50', 'text-pink-600');
                    likeIcon.setAttribute('fill', 'currentColor');
                    
                    // Save to local storage so they don't vote twice on refresh
                    localStorage.setItem('liked_post_' + postId, 'true');
                    hasLiked = true;
                }
            } catch (error) {
                console.error("Error liking post", error);
            }
        });

        // Subscription Paywall Logic
        const articleContent = document.getElementById('articleContent');
        const blurOverlay = document.getElementById('blurOverlay');
        const paywallPopup = document.getElementById('paywallPopup');
        const subscribeForm = document.getElementById('subscribeForm');
        const subBtn = document.getElementById('subBtn');
        const subError = document.getElementById('subError');

        function unlockArticle() {
            articleContent.classList.remove('max-h-[800px]', 'overflow-hidden');
            articleContent.classList.add('max-h-none');
            if(blurOverlay) blurOverlay.remove();
            if(paywallPopup) paywallPopup.remove();
        }

        // Check if already subscribed
        if (localStorage.getItem('blog_subscribed') === 'true') {
            unlockArticle();
        } else {
            // Only show paywall if content is long enough
            if (articleContent.scrollHeight < 900) {
                unlockArticle();
            }
        }

        if(subscribeForm) {
            subscribeForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                subBtn.textContent = 'Déblocage...';
                subBtn.disabled = true;
                subError.classList.add('hidden');

                const formData = new FormData();
                formData.append('name', document.getElementById('subName').value);
                formData.append('email', document.getElementById('subEmail').value);
                formData.append('phone', document.getElementById('subPhone').value);

                try {
                    const response = await fetch('api/subscribe_blog.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await response.json();

                    if (data.success) {
                        localStorage.setItem('blog_subscribed', 'true');
                        unlockArticle();
                    } else {
                        subError.textContent = data.message;
                        subError.classList.remove('hidden');
                        subBtn.textContent = 'Continuer la lecture';
                        subBtn.disabled = false;
                    }
                } catch (err) {
                    subError.textContent = "Une erreur est survenue.";
                    subError.classList.remove('hidden');
                    subBtn.textContent = 'Continuer la lecture';
                    subBtn.disabled = false;
                }
            });
        }
    </script>
</body>
</html>
