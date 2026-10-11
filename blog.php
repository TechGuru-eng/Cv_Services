<?php
require_once 'admin/config/db.php';
require_once 'includes/tracker.php';

function getReadingTime($content) {
    $wordCount = str_word_count(strip_tags($content));
    $minutes = ceil($wordCount / 200);
    return max(1, $minutes);
}

try {
    // Fetch initial posts (1 featured + 4 grid)
    $stmt = $pdo->query("SELECT * FROM blog_posts WHERE is_published = 1 ORDER BY created_at DESC LIMIT 5");
    $posts = $stmt ? $stmt->fetchAll() : [];

    // Extract the featured post (the latest one)
    $featuredPost = null;
    if (!empty($posts)) {
        $featuredPost = array_shift($posts);
    }

    // Fetch most read posts for sidebar
    $stmtMostRead = $pdo->query("SELECT * FROM blog_posts WHERE is_published = 1 ORDER BY views_count DESC LIMIT 5");
    $popularPosts = $stmtMostRead ? $stmtMostRead->fetchAll() : [];
} catch (PDOException $e) {
    // Log the error or display a user-friendly message
    // If the table doesn't exist, this will prevent a fatal error 500
    $posts = [];
    $featuredPost = null;
    $popularPosts = [];
    $dbError = "Le blog est en cours de maintenance. La base de données n'est pas encore initialisée.";
}
?>
<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Le Mag' Carrière | MonCV</title>
    <meta name="description" content="Découvrez nos meilleurs conseils, astuces et guides pour optimiser votre CV, réussir vos entretiens et booster votre carrière.">
    <meta name="keywords" content="blog carriere, conseils cv, reussir entretien, guide emploi">
    <meta name="author" content="MonCV">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://moncvpro.hiremeguide.com/blog.php" />
    
    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://moncvpro.hiremeguide.com/blog.php">
    <meta property="og:title" content="Le Mag' Carrière | MonCV">
    <meta property="og:description" content="Découvrez nos meilleurs conseils pour booster votre carrière.">
    <meta property="og:image" content="https://moncvpro.hiremeguide.com/assets/images/default_blog_preview.png">
    
    <!-- Favicons -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        primary: '#0ea5e9',
                        dark: '#0f172a'
                    }
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
<body class="bg-white antialiased text-gray-900 font-sans selection:bg-black selection:text-white">

    <!-- Minimalist Premium Header -->
    <header class="fixed top-0 w-full z-50 bg-white/90 backdrop-blur-md border-b border-gray-100 transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            <a href="index.php" class="flex items-center gap-2 group">
                <span class="text-2xl font-extrabold tracking-tight text-gray-900">Mon<span class="text-gray-400 font-light">CV</span></span>
                <span class="text-sm font-semibold tracking-widest uppercase text-gray-500 ml-2 mt-1">Blog</span>
            </a>
            <a href="index.php" class="hidden sm:flex items-center gap-2 text-sm font-bold text-gray-600 hover:text-black transition">
                <span>Retour au site</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
            </a>
        </div>
    </header>

    <!-- Hero Section (Ramp/ClickUp Inspired) -->
    <section class="pt-32 pb-16 lg:pt-40 lg:pb-20 overflow-hidden bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col lg:flex-row items-center gap-12 lg:gap-20">
                <!-- Left: Typography & Subscription -->
                <div class="w-full lg:w-1/2">
                    <h1 class="text-5xl lg:text-7xl font-extrabold text-gray-900 tracking-tight leading-[1.1] mb-6">
                        Des conseils <br>
                        pour propulser <br>
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-gray-900 to-gray-400">votre carrière.</span>
                    </h1>
                    <p class="text-lg text-gray-500 font-medium mb-10 max-w-lg leading-relaxed">
                        Rejoignez des milliers de professionnels qui reçoivent nos astuces exclusives pour optimiser leur CV et réussir leurs entretiens.
                    </p>
                    <?php if (isset($dbError)): ?>
                        <div class="bg-red-50 border border-red-200 text-red-600 p-4 rounded-xl mb-8 max-w-md">
                            <p class="font-bold"><i class="fa-solid fa-triangle-exclamation mr-2"></i> Erreur Base de données</p>
                            <p class="text-sm mt-1"><?= htmlspecialchars($dbError) ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <form id="newsletterForm" class="flex flex-col sm:flex-row gap-3 max-w-md">
                        <input type="email" id="nlEmail" placeholder="Votre adresse email" class="flex-1 px-5 py-4 rounded-xl bg-gray-50 border border-gray-200 text-gray-900 focus:outline-none focus:ring-2 focus:ring-black focus:border-transparent transition placeholder-gray-400" required>
                        <button type="submit" id="nlBtn" class="px-8 py-4 bg-black text-white font-bold rounded-xl hover:bg-gray-800 transition shadow-lg shadow-gray-200 whitespace-nowrap">
                            S'abonner
                        </button>
                    </form>
                    <div id="nlSuccess" class="hidden mt-4 p-4 bg-[#25D366]/10 text-[#128C7E] rounded-xl border border-[#25D366]/20 flex items-center gap-3 max-w-md">
                        <svg class="w-6 h-6 text-[#25D366] shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                        <span class="text-sm font-bold">Merci ! Vous êtes bien inscrit(e) à notre newsletter.</span>
                    </div>
                </div>
                
                <!-- Right: Featured Article -->
                <div class="w-full lg:w-1/2">
                    <?php if ($featuredPost): ?>
                    <article class="group cursor-pointer block relative rounded-3xl overflow-hidden bg-gray-50 transition-all duration-500 hover:-translate-y-2 hover:shadow-2xl hover:shadow-gray-200/50" onclick="window.location.href='blog/<?= htmlspecialchars($featuredPost['slug']) ?>'">
                        <div class="aspect-video w-full relative overflow-hidden">
                            <?php if($featuredPost['image_path']): ?>
                                <img src="<?= htmlspecialchars($featuredPost['image_path']) ?>" alt="<?= htmlspecialchars($featuredPost['title']) ?>" class="w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-700 ease-in-out">
                            <?php else: ?>
                                <img src="assets/images/default_blog_preview.png" alt="MonCV Blog" class="w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-700 ease-in-out">
                            <?php endif; ?>
                        </div>
                        <div class="w-full bg-white p-8">
                            <div class="flex items-center gap-3 mb-4">
                                <span class="px-3 py-1 bg-black text-white text-xs font-bold uppercase tracking-wider rounded-full">À la une</span>
                                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider"><?= date('d M Y', strtotime($featuredPost['created_at'])) ?></span>
                                <span class="text-gray-300">&bull;</span>
                                <span class="text-xs font-bold text-gray-400 tracking-wider">⏱️ <?= getReadingTime($featuredPost['content']) ?> min de lecture</span>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-900 mb-2 leading-snug line-clamp-2">
                                <?= htmlspecialchars($featuredPost['title']) ?>
                            </h3>
                            <p class="text-gray-500 mb-6 line-clamp-3 leading-relaxed">
                                <?= htmlspecialchars(mb_strimwidth(strip_tags($featuredPost['content']), 0, 150, "...")) ?>
                            </p>
                            
                            <div class="mt-auto flex items-center gap-3 pt-4 border-t border-gray-100">
                                <img src="<?= htmlspecialchars($featuredPost['author_image_path'] ?? 'https://ui-avatars.com/api/?name=NHN&background=0f172a&color=fff') ?>" class="w-10 h-10 rounded-full object-cover bg-gray-200" alt="<?= htmlspecialchars($featuredPost['author_name'] ?? 'N.H.N') ?>">
                                <div class="flex flex-col">
                                    <span class="text-sm font-bold text-gray-900"><?= htmlspecialchars($featuredPost['author_name'] ?? 'N.H.N') ?></span>
                                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Talent Sourcing</span>
                                </div>
                            </div>
                        </div>
                    </article>
                    <?php else: ?>
                    <!-- Placeholder -->
                    <div class="aspect-[4/3] w-full rounded-3xl bg-gray-100 flex items-center justify-center">
                        <span class="text-gray-400 font-medium">Aucun article à la une</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content Grid -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 border-t border-gray-100">
        <div class="flex flex-col lg:flex-row gap-16">
            
            <!-- Latest Posts -->
            <div class="w-full lg:w-2/3">
                <div class="mb-10">
                    <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Derniers articles</h2>
                </div>
                
                <?php if (empty($posts)): ?>
                    <p class="text-gray-500">Aucun autre article pour le moment.</p>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 lg:gap-10" id="postsGrid">
                        <?php foreach($posts as $post): ?>
                            <article class="group cursor-pointer flex flex-col" onclick="window.location.href='blog/<?= htmlspecialchars($post['slug']) ?>'">
                                <div class="overflow-hidden rounded-2xl mb-5 bg-gray-100 aspect-[16/10]">
                                    <?php if($post['image_path']): ?>
                                        <img src="<?= htmlspecialchars($post['image_path']) ?>" alt="<?= htmlspecialchars($post['title']) ?>" class="w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-700 ease-out">
                                    <?php else: ?>
                                        <img src="assets/images/default_blog_preview.png" class="w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-700 ease-out">
                                    <?php endif; ?>
                                </div>
                                <div class="flex items-center gap-3 mb-3">
                                    <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider"><?= date('d M Y', strtotime($post['created_at'])) ?></span>
                                    <span class="text-gray-300">&bull;</span>
                                    <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                        <?= $post['views_count'] ?>
                                    </span>
                                    <span class="text-gray-300">&bull;</span>
                                    <span class="text-[11px] font-bold text-gray-400 tracking-wider">⏱️ <?= getReadingTime($post['content']) ?> min</span>
                                </div>
                                <h3 class="text-xl font-bold text-gray-900 mb-2 leading-snug group-hover:text-black transition-colors">
                                    <?= htmlspecialchars($post['title']) ?>
                                </h3>
                                <p class="text-gray-500 text-sm leading-relaxed line-clamp-2 mb-4">
                                    <?= htmlspecialchars(mb_strimwidth(strip_tags($post['content']), 0, 100, "...")) ?>
                                </p>
                                <div class="mt-auto flex items-center gap-3 pt-4 border-t border-gray-100">
                                    <img src="<?= htmlspecialchars($post['author_image_path'] ?? 'https://ui-avatars.com/api/?name=NHN&background=0f172a&color=fff') ?>" class="w-8 h-8 rounded-full object-cover bg-gray-200" alt="<?= htmlspecialchars($post['author_name'] ?? 'N.H.N') ?>">
                                    <div class="flex flex-col">
                                        <span class="text-xs font-bold text-gray-900"><?= htmlspecialchars($post['author_name'] ?? 'N.H.N') ?></span>
                                        <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wider">Talent Sourcing</span>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if(count($posts) == 4): ?>
                    <div class="mt-12 text-center">
                        <button id="loadMoreBtn" class="px-8 py-4 bg-gray-100 text-gray-900 font-bold rounded-xl hover:bg-gray-200 transition">
                            Charger plus d'articles
                        </button>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Sidebar (Most Popular & CTA) -->
            <aside class="w-full lg:w-1/3">
                <div class="sticky top-32 space-y-12">
                    
                    <?php if (!empty($popularPosts)): ?>
                    <div>
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-6 pb-4 border-b border-gray-100">Les plus lus</h3>
                        <div class="space-y-6">
                            <?php foreach($popularPosts as $index => $popular): ?>
                                <a href="blog/<?= htmlspecialchars($popular['slug']) ?>" class="flex gap-4 group items-center">
                                    <div class="text-3xl font-extrabold text-gray-200 group-hover:text-gray-300 transition-colors w-8">
                                        <?= str_pad($index + 1, 2, '0', STR_PAD_LEFT) ?>
                                    </div>
                                    <div class="flex-1">
                                        <h4 class="font-bold text-gray-900 text-sm leading-snug group-hover:underline decoration-2 decoration-gray-300 underline-offset-4 mb-1">
                                            <?= htmlspecialchars($popular['title']) ?>
                                        </h4>
                                        <p class="text-xs font-medium text-gray-400"><?= $popular['views_count'] ?> vues</p>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Minimalist Premium Ad Box -->
                    <div class="bg-gray-50 rounded-3xl p-8 border border-gray-100 text-center">
                        <div class="w-12 h-12 bg-black text-white rounded-2xl mx-auto flex items-center justify-center mb-5">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 mb-2">Passez au niveau supérieur</h3>
                        <p class="text-sm text-gray-500 mb-6 font-medium">Des experts rédigent votre CV pour maximiser vos chances d'entretien.</p>
                        <a href="index.php" class="block w-full py-3 bg-white border border-gray-200 text-black text-sm font-bold rounded-xl hover:bg-gray-50 transition shadow-sm">
                            Découvrir le service
                        </a>
                    </div>
                </div>
            </aside>

        </div>
    </main>

    <!-- Footer Simple -->
    <footer class="bg-white border-t border-gray-100 py-12">
        <div class="max-w-7xl mx-auto px-4 text-center flex flex-col sm:flex-row justify-between items-center gap-4">
            <p class="text-gray-400 text-sm font-medium">© <?= date('Y') ?> MonCV. Tous droits réservés.</p>
            <div class="flex items-center gap-4">
                <!-- WhatsApp -->
                <a href="https://whatsapp.com/channel/0029Vb6XRzI1t90bk6USq33g" target="_blank" rel="noopener noreferrer" class="text-gray-400 hover:text-[#25D366] transition">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12.031 0C5.385 0 .003 5.382.003 12.028c0 2.124.553 4.195 1.605 6.014L.026 24l6.103-1.601c1.76.953 3.738 1.455 5.888 1.455h.005c6.645 0 12.025-5.383 12.025-12.029S18.675 0 12.031 0zm0 21.848h-.004c-1.796 0-3.555-.483-5.097-1.396l-.365-.216-3.784.992.99-3.69-.236-.376A9.972 9.972 0 011.996 12.03c0-5.518 4.49-10.007 10.038-10.007s10.034 4.49 10.034 10.007-4.49 10.005-10.037 10.005zm5.503-7.518c-.302-.151-1.785-.882-2.062-.983-.277-.101-.479-.151-.68.151-.202.302-.782.983-.958 1.185-.176.202-.353.227-.655.076-2.127-1.066-3.543-2.083-4.9-4.382-.202-.345.202-.321.498-.916.101-.202.051-.378-.025-.529-.076-.151-.68-1.638-.932-2.242-.246-.59-.496-.51-.68-.52h-.579c-.202 0-.529.076-.806.378-.277.302-1.058 1.033-1.058 2.519s1.083 2.924 1.234 3.125c.151.202 2.13 3.254 5.161 4.56.721.31 1.282.494 1.721.633.722.228 1.38.196 1.898.119.584-.087 1.785-.73 2.037-1.436.252-.705.252-1.31.176-1.436-.076-.126-.277-.202-.579-.353z"/></svg>
                </a>
                <!-- LinkedIn -->
                <a href="https://www.linkedin.com/feed/update/urn:li:activity:7472143072412487680" target="_blank" rel="noopener noreferrer" class="text-gray-400 hover:text-[#0077b5] transition">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg>
                </a>
            </div>
        </div>
    </footer>

    <script>
        const nlForm = document.getElementById('newsletterForm');
        const nlSuccess = document.getElementById('nlSuccess');

        if(nlForm) {
            // If already subscribed on this device, hide form and show success message
            if (localStorage.getItem('newsletter_subscribed') === 'true') {
                nlForm.classList.add('hidden');
                nlSuccess.classList.remove('hidden');
            }

            nlForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const btn = document.getElementById('nlBtn');
                btn.textContent = 'Patientez...';
                btn.disabled = true;
                
                const email = document.getElementById('nlEmail').value;
                const formData = new FormData();
                formData.append('email', email);

                try {
                    const response = await fetch('api/subscribe_newsletter.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await response.json();

                    if(data.success) {
                        // Remember the subscription
                        localStorage.setItem('newsletter_subscribed', 'true');
                        nlForm.classList.add('hidden');
                        nlSuccess.classList.remove('hidden');
                    } else {
                        btn.textContent = "S'abonner";
                        btn.disabled = false;
                        alert(data.message || "Une erreur est survenue.");
                    }
                } catch(err) {
                    console.error("Error subscribing", err);
                    btn.textContent = "S'abonner";
                    btn.disabled = false;
                    alert("Erreur de connexion.");
                }
            });
        }

        // Load More Logic
        const loadMoreBtn = document.getElementById('loadMoreBtn');
        const postsGrid = document.getElementById('postsGrid');
        let currentOffset = 5; // 1 featured + 4 grid

        if(loadMoreBtn && postsGrid) {
            loadMoreBtn.addEventListener('click', async () => {
                const originalText = loadMoreBtn.textContent;
                loadMoreBtn.textContent = 'Chargement...';
                loadMoreBtn.disabled = true;

                try {
                    const response = await fetch(`api/get_more_posts.php?offset=${currentOffset}`);
                    const data = await response.json();

                    if(data.success && data.html) {
                        postsGrid.insertAdjacentHTML('beforeend', data.html);
                        currentOffset += 4;

                        if(!data.has_more) {
                            loadMoreBtn.remove();
                        } else {
                            loadMoreBtn.textContent = originalText;
                            loadMoreBtn.disabled = false;
                        }
                    } else {
                        loadMoreBtn.textContent = originalText;
                        loadMoreBtn.disabled = false;
                    }
                } catch(e) {
                    console.error("Error loading posts", e);
                    loadMoreBtn.textContent = "Erreur. Réessayer";
                    loadMoreBtn.disabled = false;
                }
            });
        }
    </script>
</body>
</html>
