<?php
require_once '../admin/config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postId = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    
    if ($postId > 0) {
        // Increment likes count
        $stmt = $pdo->prepare("UPDATE blog_posts SET likes_count = likes_count + 1 WHERE id = ?");
        if ($stmt->execute([$postId])) {
            // Fetch new count
            $stmt = $pdo->prepare("SELECT likes_count FROM blog_posts WHERE id = ?");
            $stmt->execute([$postId]);
            $count = $stmt->fetchColumn();
            
            echo json_encode(['success' => true, 'new_count' => $count]);
            exit;
        }
    }
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);
?>
