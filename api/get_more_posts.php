<?php
require_once '../admin/config/db.php';

header('Content-Type: application/json');

$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$limit = 4; // Number of posts to load each time

function getReadingTime($content) {
    $wordCount = str_word_count(strip_tags($content));
    $minutes = ceil($wordCount / 200);
    return max(1, $minutes);
}

// Fetch posts
$stmt = $pdo->prepare("SELECT * FROM blog_posts WHERE is_published = 1 ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$posts = $stmt->fetchAll();

$html = '';
foreach ($posts as $post) {
    $date = date('d M Y', strtotime($post['created_at']));
    $readingTime = getReadingTime($post['content']);
    $views = $post['views_count'];
    $title = htmlspecialchars($post['title']);
    $slug = htmlspecialchars($post['slug']);
    $excerpt = htmlspecialchars(mb_strimwidth(strip_tags($post['content']), 0, 100, "..."));
    $imagePath = $post['image_path'] ? htmlspecialchars($post['image_path']) : 'assets/images/default_blog_preview.png';
    $authorName = htmlspecialchars($post['author_name'] ?? 'N.H.N');
    $authorImage = htmlspecialchars($post['author_image_path'] ?? 'https://ui-avatars.com/api/?name=NHN&background=0f172a&color=fff');

    $html .= <<<HTML
    <article class="group cursor-pointer flex flex-col" onclick="window.location.href='blog/{$slug}'">
        <div class="overflow-hidden rounded-2xl mb-5 bg-gray-100 aspect-[16/10]">
            <img src="{$imagePath}" alt="{$title}" class="w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-700 ease-out">
        </div>
        <div class="flex items-center gap-3 mb-3">
            <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">{$date}</span>
            <span class="text-gray-300">&bull;</span>
            <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                {$views}
            </span>
            <span class="text-gray-300">&bull;</span>
            <span class="text-[11px] font-bold text-gray-400 tracking-wider">⏱️ {$readingTime} min</span>
        </div>
        <h3 class="text-xl font-bold text-gray-900 mb-2 leading-snug group-hover:text-black transition-colors">
            {$title}
        </h3>
        <p class="text-gray-500 text-sm leading-relaxed line-clamp-2 mb-4">
            {$excerpt}
        </p>
        <div class="mt-auto flex items-center gap-3 pt-4 border-t border-gray-100">
            <img src="{$authorImage}" class="w-8 h-8 rounded-full object-cover bg-gray-200" alt="{$authorName}">
            <div class="flex flex-col">
                <span class="text-xs font-bold text-gray-900">{$authorName}</span>
                <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wider">Talent Sourcing</span>
            </div>
        </div>
    </article>
HTML;
}

echo json_encode([
    'success' => true,
    'html' => $html,
    'has_more' => count($posts) === $limit
]);
?>
