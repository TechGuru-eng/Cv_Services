<?php
require_once '../admin/config/db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

function getReadingTime($content) {
    $wordCount = str_word_count(strip_tags($content));
    $minutes = ceil($wordCount / 200);
    return max(1, $minutes);
}

$stmt = $pdo->query("SELECT * FROM blog_posts WHERE is_published = 1 ORDER BY created_at DESC LIMIT 3");
$posts = $stmt->fetchAll();

$html = '';
foreach ($posts as $post) {
    $date = date('d M Y', strtotime($post['created_at']));
    $readingTime = getReadingTime($post['content']);
    $title = htmlspecialchars($post['title']);
    $slug = htmlspecialchars($post['slug']);
    $excerpt = htmlspecialchars(mb_strimwidth(strip_tags($post['content']), 0, 100, "..."));
    $imagePath = $post['image_path'] ? htmlspecialchars($post['image_path']) : 'assets/images/default_blog_preview.png';
    $authorName = htmlspecialchars($post['author_name'] ?? 'N.H.N');
    $authorImage = htmlspecialchars($post['author_image_path'] ?? 'https://ui-avatars.com/api/?name=NHN&background=0f172a&color=fff');

    $imagePathFull = (strpos($imagePath, 'http') === 0) ? $imagePath : '/' . ltrim($imagePath, '/');
    $authorImageFull = (strpos($authorImage, 'http') === 0) ? $authorImage : '/' . ltrim($authorImage, '/');
    $articleUrl = '/blog/' . $slug;

    $html .= <<<HTML
    <article class="group cursor-pointer flex flex-col bg-white rounded-3xl overflow-hidden shadow-md hover:shadow-xl transition-all duration-300 transform hover:-translate-y-2 border border-gray-100" onclick="window.location.href='{$articleUrl}'">
        <div class="overflow-hidden bg-gray-100 aspect-[16/10]">
            <img src="{$imagePathFull}" alt="{$title}" class="w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-700 ease-out">
        </div>
        <div class="p-6 flex flex-col flex-1">
            <div class="flex items-center gap-3 mb-3">
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">{$date}</span>
                <span class="text-gray-300">&bull;</span>
                <span class="text-[10px] font-bold text-gray-400 tracking-wider">⏱️ {$readingTime} min</span>
            </div>
            <h3 class="text-lg font-bold text-gray-900 mb-2 leading-snug group-hover:text-primary transition-colors line-clamp-2">
                {$title}
            </h3>
            <p class="text-gray-500 text-sm leading-relaxed line-clamp-2 mb-4">
                {$excerpt}
            </p>
            <div class="mt-auto flex items-center gap-3 pt-4 border-t border-gray-100">
                <img src="{$authorImageFull}" class="w-8 h-8 rounded-full object-cover bg-gray-200" alt="{$authorName}">
                <div class="flex flex-col">
                    <span class="text-xs font-bold text-gray-900">{$authorName}</span>
                    <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wider">Talent Sourcing</span>
                </div>
            </div>
        </div>
    </article>
HTML;
}

echo json_encode([
    'success' => true,
    'html' => $html
]);
?>
