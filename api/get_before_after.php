<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
require_once __DIR__ . '/../includes/cors.php';
require_once __DIR__ . '/../admin/config/db.php';

try {
    $stmt = $pdo->query("SELECT * FROM before_after_cvs WHERE is_active = 1 ORDER BY display_order ASC, id ASC");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $slides = [];
    foreach ($rows as $row) {
        $slides[] = [
            'id' => (int)$row['id'],
            'category' => $row['category'],
            'before' => [
                'tag' => $row['before_tag'],
                'score' => $row['before_score'],
                'img' => $row['before_img'],
                'quote' => $row['before_quote']
            ],
            'after' => [
                'tag' => $row['after_tag'],
                'score' => $row['after_score'],
                'badge' => $row['after_badge'],
                'img' => $row['after_img'],
                'quote' => $row['after_quote']
            ]
        ];
    }

    echo json_encode([
        'success' => true,
        'count' => count($slides),
        'rotation_interval_seconds' => 20,
        'slides' => $slides
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
