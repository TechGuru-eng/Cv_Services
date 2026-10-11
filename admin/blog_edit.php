<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}
require_once 'config/db.php';

$postId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$post = [
    'title' => '',
    'slug' => '',
    'content' => '',
    'image_path' => '',
    'is_published' => 1
];

if ($postId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM blog_posts WHERE id = ?");
    $stmt->execute([$postId]);
    $fetchedPost = $stmt->fetch();
    if ($fetchedPost) {
        $post = $fetchedPost;
    }
}

$pageTitle = $postId > 0 ? "Éditer l'article" : "Nouvel Article";
?>
<?php include 'includes/header.php'; ?>
<body class="bg-gray-50">
    <!-- Include Quill CSS -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    
    <title><?= $pageTitle ?> - HireMe Admin</title>
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <?php include 'includes/nav.php'; ?>

        <!-- Main Content -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
            <div class="flex justify-between items-center mb-6">
                <div class="flex items-center gap-4">
                    <a href="blog.php" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    </a>
                    <h1 class="text-3xl font-bold text-gray-800"><?= $pageTitle ?></h1>
                </div>
            </div>

            <?php if (isset($_GET['error'])): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                    <p><?= htmlspecialchars($_GET['error']) ?></p>
                </div>
            <?php endif; ?>

            <div class="bg-white rounded-xl shadow-sm overflow-hidden p-8 max-w-4xl">
                <form action="api/save_blog_post.php" method="POST" enctype="multipart/form-data" id="blogForm">
                    <input type="hidden" name="post_id" value="<?= $postId ?>">
                    
                    <!-- Title & Slug -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Titre de l'article *</label>
                            <input type="text" name="title" id="titleInput" required value="<?= htmlspecialchars($post['title']) ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">URL (Slug) *</label>
                            <input type="text" name="slug" id="slugInput" required value="<?= htmlspecialchars($post['slug']) ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 outline-none bg-gray-50">
                            <p class="text-xs text-gray-500 mt-1">Généré automatiquement (ex: comment-reussir-son-cv)</p>
                        </div>
                    </div>

                    <!-- Image Upload -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Image de couverture (Aperçu) <?= $postId == 0 ? '*' : '' ?></label>
                        <?php if($post['image_path']): ?>
                            <div class="mb-3">
                                <img src="../<?= htmlspecialchars($post['image_path']) ?>" class="h-32 rounded shadow-sm">
                            </div>
                        <?php endif; ?>
                        <input type="file" name="cover_image" accept="image/*" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" <?= $postId == 0 ? 'required' : '' ?>>
                        <p class="text-xs text-gray-500 mt-1">Recommandé : 1200x630 pixels pour un bel aperçu sur WhatsApp/LinkedIn.</p>
                    </div>

                    <!-- Status -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Statut</label>
                        <select name="is_published" class="w-full md:w-1/3 border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
                            <option value="1" <?= $post['is_published'] == 1 ? 'selected' : '' ?>>Publié (Visible)</option>
                            <option value="0" <?= $post['is_published'] == 0 ? 'selected' : '' ?>>Brouillon (Caché)</option>
                        </select>
                    </div>

                    <!-- Rich Text Content -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Contenu de l'article *</label>
                        <div id="editor-container" class="h-96 rounded-b-lg"></div>
                        <input type="hidden" name="content" id="contentInput">
                    </div>

                    <div class="flex justify-end gap-4 mt-8">
                        <a href="blog.php" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium">Annuler</a>
                        <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium shadow-md shadow-blue-500/30">Enregistrer l'article</button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <!-- Include Quill JS -->
    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
    <script>
        // Initialize Quill editor
        var quill = new Quill('#editor-container', {
            theme: 'snow',
            modules: {
                toolbar: [
                    [{ 'header': [2, 3, 4, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    ['blockquote'],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    [{ 'color': [] }, { 'background': [] }],
                    ['link', 'image', 'video'],
                    ['clean']
                ]
            }
        });

        // Load existing content if editing
        const existingContent = <?= json_encode($post['content']) ?>;
        if(existingContent) {
            quill.clipboard.dangerouslyPasteHTML(existingContent);
        }

        // Auto-generate slug from title
        const titleInput = document.getElementById('titleInput');
        const slugInput = document.getElementById('slugInput');

        titleInput.addEventListener('input', function() {
            // Only auto-generate if we are creating a new post, or if the user hasn't manually altered it much
            // For simplicity, we just generate it on input if it's a new post or if they type
            let slug = this.value.toLowerCase().trim()
                .replace(/[^\w\s-]/g, '') // Remove non-word chars
                .replace(/[\s_-]+/g, '-') // Swap spaces for hyphens
                .replace(/^-+|-+$/g, ''); // Trim hyphens
            slugInput.value = slug;
        });

        // Sync Quill content to hidden input before submit
        document.getElementById('blogForm').addEventListener('submit', function(e) {
            const html = quill.root.innerHTML;
            if(quill.getText().trim().length === 0 && !html.includes('<img')) {
                e.preventDefault();
                alert('Veuillez écrire du contenu pour cet article.');
                return;
            }
            document.getElementById('contentInput').value = html;
        });
    </script>
</body>
</html>
