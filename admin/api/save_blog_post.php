<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: ../login.php');
    exit;
}
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../blog.php');
    exit;
}

$postId = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
$title = trim($_POST['title']);
$slug = trim($_POST['slug']);
$content = $_POST['content'];
$isPublished = intval($_POST['is_published']);

if(empty($title) || empty($slug) || empty($content)) {
    header("Location: ../blog_edit.php?id=$postId&error=" . urlencode("Veuillez remplir tous les champs obligatoires."));
    exit;
}

// Check for slug uniqueness
$stmt = $pdo->prepare("SELECT id FROM blog_posts WHERE slug = ? AND id != ?");
$stmt->execute([$slug, $postId]);
if ($stmt->rowCount() > 0) {
    // Append a random number to slug if it already exists
    $slug = $slug . '-' . rand(1000, 9999);
}

$imagePath = '';
if ($postId > 0) {
    // Keep existing image if not uploading a new one
    $stmt = $pdo->prepare("SELECT image_path FROM blog_posts WHERE id = ?");
    $stmt->execute([$postId]);
    $existing = $stmt->fetch();
    $imagePath = $existing ? $existing['image_path'] : '';
}

// Handle file upload
if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = '../../assets/images/blog/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $fileInfo = pathinfo($_FILES['cover_image']['name']);
    $extension = strtolower($fileInfo['extension']);
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    
    if (in_array($extension, $allowed)) {
        $newFilename = uniqid('blog_') . '.' . $extension;
        $destination = $uploadDir . $newFilename;
        
        if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $destination)) {
            // Delete old image if updating
            if (!empty($imagePath) && file_exists('../../' . $imagePath)) {
                unlink('../../' . $imagePath);
            }
            $imagePath = 'assets/images/blog/' . $newFilename;
        }
    } else {
        header("Location: ../blog_edit.php?id=$postId&error=" . urlencode("Format d'image non supporté."));
        exit;
    }
}

// Enforce image on creation
if ($postId == 0 && empty($imagePath)) {
    header("Location: ../blog_edit.php?id=$postId&error=" . urlencode("L'image de couverture est obligatoire pour un nouvel article."));
    exit;
}

try {
    if ($postId > 0) {
        // Update
        $stmt = $pdo->prepare("UPDATE blog_posts SET title = ?, slug = ?, content = ?, image_path = ?, is_published = ? WHERE id = ?");
        $stmt->execute([$title, $slug, $content, $imagePath, $isPublished, $postId]);
    } else {
        // Insert
        $stmt = $pdo->prepare("INSERT INTO blog_posts (title, slug, content, image_path, is_published) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$title, $slug, $content, $imagePath, $isPublished]);
    }
    header("Location: ../blog.php?success=1");
    exit;
} catch (PDOException $e) {
    header("Location: ../blog_edit.php?id=$postId&error=" . urlencode("Erreur base de données: " . $e->getMessage()));
    exit;
}
?>
